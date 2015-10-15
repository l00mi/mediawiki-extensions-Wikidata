<?php

namespace WikibaseQuality\ExternalValidation\DumpMetaInformation;

use DatabaseBase;
use InvalidArgumentException;
use UnexpectedValueException;
use ResultWrapper;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\PropertyId;

/**
 * Implements access to the actual database table that stores the dump information.
 *
 * @package WikibaseQuality\ExternalValidation\DumpMetaInformation
 * @author BP2014N1
 * @license GNU GPL v2+
 */
class SqlDumpMetaInformationRepo implements DumpMetaInformationLookup, DumpMetaInformationStore {

	const META_TABLE_NAME = 'wbqev_dump_information';
	const IDENTIFIER_PROPERTIES_TABLE_NAME = 'wbqev_identifier_properties';

	/**
	 * Gets DumpMetaInformation for specific dump id from database.
	 *
	 * @param $dumpId
	 * @return DumpMetaInformation
	 * @throws InvalidArgumentException
	 */
	public function getWithId( $dumpId ) {
		if ( !is_string( $dumpId ) ) {
			throw new InvalidArgumentException( '$dumpId must be string.' );
		}

		$dumpMetaInformation = $this->getWithIds( array( $dumpId ) );

		return reset( $dumpMetaInformation );
	}

	/**
	 * Gets DumpMetaInformation for specific dump ids from database
	 * Returns array in the form 'dumpId' => DumpMetaInformation
	 *
	 * @param string[] $dumpIds
	 * @return DumpMetaInformation[]
	 * @throws InvalidArgumentException
	 */
	public function getWithIds( array $dumpIds ) {
		foreach ( $dumpIds as $dumpId ) {
			if ( !is_string( $dumpId ) ) {
				throw new InvalidArgumentException( '$dumpIds must contain only strings.' );
			}
		}

		if( count( $dumpIds ) > 0 ) {
			$db = wfGetDB( DB_SLAVE );
			$result = $db->select(
				array(
					self::META_TABLE_NAME,
					self::IDENTIFIER_PROPERTIES_TABLE_NAME
				),
				'*',
				array(
					'id' => $dumpIds
				),
				__METHOD__,
				array(),
				array(
					self::IDENTIFIER_PROPERTIES_TABLE_NAME => array(
						'LEFT JOIN',
						'dump_id = id'
					)
				)
			);

			return $this->buildDumpMetaInformationFromResult( $result );
		}

		return array();
	}

	/**
	 * Gets DumpMetaInformation for specific identifier properties from database
	 * Returns array in the form 'dumpId' => DumpMetaInformation
	 *
	 * @param PropertyId[] $identifierPropertyIds
	 *
	 * @throws InvalidArgumentException
	 * @return DumpMetaInformation[]
	 */
	public function getWithIdentifierProperties( array $identifierPropertyIds ) {
		foreach ( $identifierPropertyIds as $propertyId ) {
			if ( !( $propertyId instanceof PropertyId ) ) {
				throw new InvalidArgumentException( '$identifierProperties must contain only PropertyIds.' );
			}
		}

		if( count( $identifierPropertyIds ) > 0 ) {
			$db = wfGetDB( DB_SLAVE );
			$identifierPropertyIds = array_map(
				function ( PropertyId $propertyId ) {
					return $propertyId->getSerialization();
				},
				$identifierPropertyIds
			);
			$result = $db->select(
				self::IDENTIFIER_PROPERTIES_TABLE_NAME,
				'dump_id',
				array(
					'identifier_pid' => $identifierPropertyIds
				)
			);
			$dumpIds = array();
			foreach ( $result as $row ) {
				if ( !in_array( $row->dump_id, $dumpIds ) ) {
					$dumpIds[] = $row->dump_id;
				}
			}

			return $this->getWithIds( $dumpIds );
		}

		return array();
	}

	/**
	 * Gets id of item that represents the data source for each dump.
	 *
	 * @return ItemId[]
	 */
	public function getSourceItemIds() {
		$db = wfGetDB( DB_SLAVE );
		$result = $db->selectFieldValues(
			self::META_TABLE_NAME,
			'source_qid',
			array(),
			__METHOD__,
			'DISTINCT'
		);

		$sourceItemIds = $result; // TODO: Parse as ItemId, when ItemIds are used in violation table
		/*$sourceItemIds = array();
		foreach ( $result as $itemId ) {
			$sourceItemIds = new ItemId( $itemId );
		}*/

		return $sourceItemIds;
	}

	/**
	 * Gets all DumpMetaInformation from database
	 * Returns array in the form 'dumpId' => DumpMetaInformation
	 *
	 * @return DumpMetaInformation[]
	 */
	public function getAll() {
		$db = wfGetDB( DB_SLAVE );
		$result = $db->select(
			array(
				self::META_TABLE_NAME,
				self::IDENTIFIER_PROPERTIES_TABLE_NAME
			),
			'*',
			array(),
			__METHOD__,
			array(),
			array(
				self::IDENTIFIER_PROPERTIES_TABLE_NAME => array(
					'LEFT JOIN',
					'dump_id = id'
				)
			)
		);

		return $this->buildDumpMetaInformationFromResult( $result );
	}

	/**
	 * @param ResultWrapper $result
	 * @return null|array
	 * @throws UnexpectedValueException
	 */
	private function buildDumpMetaInformationFromResult( ResultWrapper $result ) {
		$aggregatedRows = array();
		foreach ( $result as $row ) {
			if ( array_key_exists( $row->id, $aggregatedRows ) ) {
				$propertyId = new PropertyId( $row->identifier_pid );
				$aggregatedRows[$row->id]->identifier_pid[] = $propertyId;
			} else {
				if ( $row->identifier_pid !== null ) {
					$propertyId = new PropertyId( $row->identifier_pid );
					$row->identifier_pid = array( $propertyId );
				}
				$aggregatedRows[$row->id] = $row;
			}
		}

		$dumpMetaInformation = array();
		foreach ( $aggregatedRows as $row ) {
			$dumpId = $row->id;
			$sourceItemId = new ItemId( $row->source_qid );
			$importDate = wfTimestamp( TS_MW, $row->import_date );
			$language = $row->language;
			$sourceUrl = $row->source_url;
			$size = (int)$row->size;
			$licenseItemId = new ItemId( $row->license_qid );
			$identifierPropertyIds = $row->identifier_pid;

			$dumpMetaInformation[$row->dump_id] =
				new DumpMetaInformation( $dumpId,
					$sourceItemId,
					$identifierPropertyIds,
					$importDate,
					$language,
					$sourceUrl,
					$size,
					$licenseItemId
				);
		}

		return $dumpMetaInformation;
	}

	/**
	 * Inserts or updates given dump meta information to database
	 *
	 * @param DumpMetaInformation $dumpMetaInformation
	 */
	public function save( DumpMetaInformation $dumpMetaInformation ) {
		$db = wfGetDB( DB_SLAVE );
		$dumpId = $dumpMetaInformation->getDumpId();
		$accumulator = $this->getDumpInformationFields( $db, $dumpMetaInformation );

		$existing = $db->selectRow(
			self::META_TABLE_NAME,
			array( 'id' ),
			array( 'id' => $dumpId )
		);

		if ( $existing ) {
			$db->update(
				self::META_TABLE_NAME,
				$accumulator,
				array( 'id' => $dumpId )
			);
		} else {
			$db->insert(
				self::META_TABLE_NAME,
				$accumulator
			);
		}

		$db->delete(
			self::IDENTIFIER_PROPERTIES_TABLE_NAME,
			array( 'dump_id' => $dumpId )
		);
		$db->insert(
			self::IDENTIFIER_PROPERTIES_TABLE_NAME,
			array_map(
				function ( PropertyId $identifierPropertyId ) use ( $dumpId ) {
					return array(
						'dump_id' => $dumpId,
						'identifier_pid' => $identifierPropertyId->getSerialization()
					);
				},
				$dumpMetaInformation->getIdentifierPropertyIds()
			)
		);
	}

	/**
	 * @param DumpMetaInformation $dumpMetaInformation *
	 * @return array
	 */
	private function getDumpInformationFields( DatabaseBase $db,  DumpMetaInformation $dumpMetaInformation ) {
		return array(
			'id' => $dumpMetaInformation->getDumpId(),
			'source_qid' => $dumpMetaInformation->getSourceItemId()->getSerialization(),
			'import_date' => $db->timestamp( $dumpMetaInformation->getImportDate() ),
			'language' => $dumpMetaInformation->getLanguageCode(),
			'source_url' => $dumpMetaInformation->getSourceUrl(),
			'size' => $dumpMetaInformation->getSize(),
			'license_qid' => $dumpMetaInformation->getLicenseItemId()->getSerialization()
		);
	}

}
