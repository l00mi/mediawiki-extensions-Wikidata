<?php

namespace Wikibase\Repo\Store\Sql;

use Database;
use InvalidArgumentException;
use LoadBalancer;
use ResultWrapper;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Lib\EntityIdComposer;
use Wikibase\Lib\Reporting\ExceptionHandler;
use Wikibase\Lib\Reporting\LogWarningExceptionHandler;
use Wikibase\Lib\Reporting\MessageReporter;
use Wikibase\Lib\Reporting\NullMessageReporter;

/**
 * Implements initial population (priming) for the wb_changes_subscription table,
 * based on the wb_items_per_site. Any wiki linked via the wb_items_per_site table
 * will be considered a subscriber.
 *
 * @license GPL-2.0+
 * @author Daniel Kinzler
 */
class ChangesSubscriptionTableBuilder {

	/**
	 * @var LoadBalancer
	 */
	private $loadBalancer;

	/**
	 * @var EntityIdComposer
	 */
	private $entityIdComposer;

	/**
	 * @var string
	 */
	private $tableName;

	/**
	 * @var int
	 */
	private $batchSize;

	/**
	 * @var ExceptionHandler
	 */
	private $exceptionHandler;

	/**
	 * @var MessageReporter
	 */
	private $progressReporter;

	/**
	 * @var string 'verbose' or 'standard'
	 */
	private $verbosity;

	/**
	 * @param LoadBalancer $loadBalancer
	 * @param EntityIdComposer $entityIdComposer
	 * @param string $tableName
	 * @param int $batchSize
	 * @param string $verbosity Either 'standard' or 'verbose'
	 *
	 * @throws InvalidArgumentException
	 */
	public function __construct(
		LoadBalancer $loadBalancer,
		EntityIdComposer $entityIdComposer,
		$tableName,
		$batchSize,
		$verbosity = 'standard'
	) {
		if ( !is_string( $tableName ) ) {
			throw new InvalidArgumentException( '$tableName must be a string' );
		}

		if ( !is_int( $batchSize ) || $batchSize < 1 ) {
			throw new InvalidArgumentException( '$batchSize must be an integer >= 1' );
		}

		if ( $verbosity !== 'standard' && $verbosity !== 'verbose' ) {
			throw new InvalidArgumentException( '$verbosity must be either "verbose"'
				. ' or "standard".' );
		}

		$this->loadBalancer = $loadBalancer;
		$this->entityIdComposer = $entityIdComposer;
		$this->tableName = $tableName;
		$this->batchSize = $batchSize;
		$this->verbosity = $verbosity;

		$this->exceptionHandler = new LogWarningExceptionHandler();
		$this->progressReporter = new NullMessageReporter();
	}

	/**
	 * @param MessageReporter $progressReporter
	 */
	public function setProgressReporter( MessageReporter $progressReporter ) {
		$this->progressReporter = $progressReporter;
	}

	/**
	 * @param ExceptionHandler $exceptionHandler
	 */
	public function setExceptionHandler( ExceptionHandler $exceptionHandler ) {
		$this->exceptionHandler = $exceptionHandler;
	}

	/**
	 * Fill the subscription table with rows based on entries in wb_items_per_site.
	 *
	 * @param ItemId|null $startItem The item to start with.
	 */
	public function fillSubscriptionTable( ItemId $startItem = null ) {
		$continuation = $startItem === null ? null : array( $startItem->getNumericId(), 0 );

		while ( true ) {
			$count = $this->processSubscriptionBatch( $continuation );

			if ( $count > 0 ) {
				$this->progressReporter->reportMessage( 'Populating subscription table: '
					. "inserted $count subscriptions, continuing at item #{$continuation[0]}." );
			} else {
				break;
			}
		}
	}

	/**
	 * @param array &$continuation
	 *
	 * @return int The number of subscriptions inserted.
	 */
	private function processSubscriptionBatch( &$continuation = array() ) {
		$db = $this->loadBalancer->getConnection( DB_MASTER );

		$subscriptionsPerItemBatch = $this->getSubscriptionsPerItemBatch( $db, $continuation );

		if ( empty( $subscriptionsPerItemBatch ) ) {
			return 0;
		}

		$count = $this->insertSubscriptionBatch( $db, $subscriptionsPerItemBatch );

		$this->loadBalancer->reuseConnection( $db );

		return $count;
	}

	/**
	 * @param Database $db
	 * @param array[] $subscriptionsPerItem
	 *
	 * @return int The number of rows inserted.
	 */
	private function insertSubscriptionBatch( Database $db, array $subscriptionsPerItem ) {
		$db->startAtomic( __METHOD__ );

		$c = 0;
		foreach ( $subscriptionsPerItem as $itemId => $subscribers ) {
			$rows = $this->makeSubscriptionRows( $itemId, $subscribers );

			$db->insert(
				$this->tableName,
				$rows,
				__METHOD__,
				array(
					'IGNORE'
				)
			);

			if ( $this->verbosity === 'verbose' ) {
				$this->progressReporter->reportMessage( 'Inserted ' . $db->affectedRows()
					. ' into wb_changes_subscription' );
			}

			$c += count( $rows );
		}

		$db->endAtomic( __METHOD__ );
		return $c;
	}

	/**
	 * @param Database $db
	 * @param array &$continuation
	 *
	 * @return array[] An associative array mapping item IDs to lists of site IDs.
	 */
	private function getSubscriptionsPerItemBatch( Database $db, &$continuation = array() ) {
		if ( empty( $continuation ) ) {
			$continuationCondition = '1';
		} else {
			list( $fromItemId, $fromRowId ) = $continuation;
			$continuationCondition = 'ips_item_id > ' . (int)$fromItemId
				. ' OR ( '
					. 'ips_item_id = ' . (int)$fromItemId
					. ' AND '
					. 'ips_row_id > ' . $fromRowId
				. ' )';
		}

		$res = $db->select(
			'wb_items_per_site',
			array( 'ips_row_id', 'ips_item_id', 'ips_site_id' ),
			$continuationCondition,
			__METHOD__,
			array(
				'LIMIT' => $this->batchSize,
				'ORDER BY' => 'ips_item_id, ips_row_id'
			)
		);

		if ( $this->verbosity === 'verbose' ) {
			$this->progressReporter->reportMessage( 'Selected ' . $res->numRows() . ' wb_item_per_site records'
				. ' with continuation: ' . $continuationCondition );
		}

		return $this->getSubscriptionsPerItemFromRows( $res, $continuation );
	}

	/**
	 * @param ResultWrapper $res A result set with the ips_item_id and ips_site_id fields
	 *        set for each row.
	 * @param array &$continuation Single item ID => site ID pair or empty.
	 *
	 * @return array[] An associative array mapping item IDs to lists of site IDs.
	 */
	private function getSubscriptionsPerItemFromRows(
		ResultWrapper $res,
		&$continuation = array()
	) {
		$subscriptionsPerItem = array();

		$currentItemId = 0;
		$itemId = null;

		foreach ( $res as $row ) {
			if ( $row->ips_item_id != $currentItemId ) {
				$currentItemId = $row->ips_item_id;
				$itemId = $this->entityIdComposer
					->composeEntityId( '', Item::ENTITY_TYPE, $currentItemId )
					->getSerialization();
			}

			$subscriptionsPerItem[$itemId][] = $row->ips_site_id;
			$continuation = array( $currentItemId, $row->ips_row_id );
		}

		return $subscriptionsPerItem;
	}

	/**
	 * Returns a list of rows for insertion, using Database's multi-row insert mechanism.
	 * Each row is represented as array( $itemId, $subscriber ).
	 *
	 * @param string $itemId
	 * @param string[] $subscribers
	 *
	 * @return array[] rows
	 */
	private function makeSubscriptionRows( $itemId, array $subscribers ) {
		$rows = array();

		foreach ( $subscribers as $subscriber ) {
			$rows[] = array(
				'cs_entity_id' => $itemId,
				'cs_subscriber_id' => $subscriber
			);
		}

		return $rows;
	}

}
