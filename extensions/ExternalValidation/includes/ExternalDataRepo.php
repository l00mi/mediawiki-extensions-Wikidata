<?php

namespace WikibaseQuality\ExternalValidation;

use DBError;
use Wikibase\DataModel\Entity\PropertyId;
use Wikimedia\Assert\Assert;

/**
 * @package WikibaseQuality\ExternalValidation
 * @author BP2014N1
 * @license GNU GPL v2+
 */
class ExternalDataRepo {

	const TABLE_NAME = 'wbqev_external_data';

	/**
	 * Gets external data for specified properties from database that matches any
	 * combination of given dump and external ids
	 *
	 * @param string[] $dumpIds
	 * @param string[] $externalIds
	 * @param PropertyId[] $propertyIds
	 * @return array
	 */
	public function getExternalData( array $dumpIds, array $externalIds, array $propertyIds ) {
		Assert::parameterElementType( 'string', $dumpIds, '$dumpIds' );
		Assert::parameterElementType( 'string', $externalIds, '$externalIds' );
		Assert::parameterElementType( 'Wikibase\DataModel\Entity\PropertyId', $propertyIds, '$propertyIds' );
		Assert::parameter( count( $dumpIds ) > 0, '$dumpIds', '$dumpIds has to contain at least one element.' );
		Assert::parameter( count( $externalIds ) > 0, '$externalIds', '$externalIds has to contain at least one element.' );

		$conditions = array(
			'dump_id' => $dumpIds,
			'external_id' => $externalIds
		);
		if ( $propertyIds ) {
			$conditions['pid'] = $propertyIds;
		}

		$externalData = array();
		$db = wfGetDB( DB_SLAVE );
		$result = $db->select(
			self::TABLE_NAME,
			array(
				'dump_id',
				'external_id',
				'pid',
				'external_value'
			),
			$conditions
		);

		foreach ( $result as $row ) {
			$externalData[$row->dump_id][$row->external_id][$row->pid][] = $row->external_value;
		}

		return $externalData;
	}

	/**
	 * Inserts single external data.
	 *
	 * @param string $dumpId
	 * @param string $externalId
	 * @param PropertyId $propertyId
	 * @param string $externalValue
	 * @return bool
	 */
	public function insert( $dumpId, $externalId, PropertyId $propertyId, $externalValue ) {
		Assert::parameterType( 'string', $dumpId, '$dumpId' );
		Assert::parameterType( 'string', $externalId, '$externalId' );
		Assert::parameterType( 'string', $externalValue, '$externalValue' );

		$externalDataBatch = array( func_get_args() );
		return $this->insertBatch( $externalDataBatch );
	}

	/**
	 * Inserts a batch of external data.
	 *
	 * @param array $externalDataBatch
	 * @throws DBError
	 * @return bool
	 */
	public function insertBatch( array $externalDataBatch ) {
		$db = wfGetDB( DB_MASTER );
		$accumulator = array_map(
			function ( $externalData ) use ( $db ) {
				return array(
					'dump_id' => $externalData[0],
					'external_id' => $externalData[1],
					'pid' => $externalData[2],
					'external_value' => $externalData[3]
				);
			},
			$externalDataBatch
		);

		try {
			$db->begin();
			$result = $db->insert( self::TABLE_NAME, $accumulator );
			$db->commit();
		}
		catch( DBError $ex ) {
			$db->rollback();
			throw $ex;
		}

		return $result;
	}

	/**
	 * Deletes all external data of dump with given id.
	 *
	 * @param string $dumpId
	 * @param int $batchSize
	 * @throws \DBUnexpectedError
	 */
	public function deleteOfDump( $dumpId, $batchSize = 1000 ) {
		Assert::parameterType( 'string', $dumpId, '$dumpId' );
		Assert::parameterType( 'integer', $batchSize, 'batchSize' );

		$db = wfGetDB( DB_MASTER );
		if ( $db->getType() === 'sqlite' ) {
			$db->delete( self::TABLE_NAME, array( 'dump_id' => $dumpId ) );
		} else {
			do {
				$db->commit( __METHOD__, 'flush' );
				wfWaitForSlaves();
				$table = $db->tableName( self::TABLE_NAME );
				$condition = 'dump_id = ' . $db->addQuotes( $dumpId );
				$db->query( sprintf( 'DELETE FROM %s WHERE %s LIMIT %s', $table, $condition, $batchSize ) );
			} while ( $db->affectedRows() > 0 );
		}
	}

}
