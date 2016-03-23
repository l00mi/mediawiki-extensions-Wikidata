<?php

namespace WikibaseQuality\ExternalValidation\UpdateExternalData;

use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\PropertyId;
use WikibaseQuality\ExternalValidation\DumpMetaInformation\DumpMetaInformation;
use WikibaseQuality\ExternalValidation\DumpMetaInformation\DumpMetaInformationStore;
use WikibaseQuality\ExternalValidation\ExternalDataRepo;

/**
 * @package WikibaseQuality\ExternalValidation\UpdateExternalData
 * @author BP2014N1
 * @license GNU GPL v2+
 */
class ExternalDataImporter {

	/**
	 * @var DumpMetaInformationStore
	 */
	private $dumpMetaInformationStore;

	/**
	 * @var ExternalDataRepo
	 */
	private $externalDataRepo;

	/**
	 * @var CsvImportSettings
	 */
	private $importSettings;

	/**
	 * @param CsvImportSettings $importSettings
	 * @param DumpMetaInformationStore $dumpMetaInformationStore
	 * @param ExternalDataRepo $externalDataRepo
	 */
	public function __construct(
		CsvImportSettings $importSettings,
		DumpMetaInformationStore $dumpMetaInformationStore,
		ExternalDataRepo $externalDataRepo
	) {
		$this->importSettings = $importSettings;
		$this->dumpMetaInformationStore = $dumpMetaInformationStore;
		$this->externalDataRepo = $externalDataRepo;
	}

	/**
	 * Starts the whole import process
	 */
	public function import() {
		$dumpIds = $this->insertMetaInformation();

		$this->log( "\nDelete old database entries...\n" );

		foreach ( $dumpIds as $dumpId ) {
			$this->externalDataRepo->deleteOfDump( $dumpId, $this->importSettings->getBatchSize() );
		}

		$this->log( "\n" );

		$this->insertExternalValues();
	}

	/**
	 * Inserts meta information stored in csv file into database
	 *
	 * @return array
	 */
	protected function insertMetaInformation() {
		$this->log( "Insert new dump meta information\n" );

		$csvFile = fopen( $this->importSettings->getDumpInformationFilePath(), 'rb' );
		if( !$csvFile ) {
			exit( 'Error while reading CSV file.' );
		}

		$i = 0;
		$dumpIds = array();
		while ( $data = fgetcsv( $csvFile ) ) {
			$identifierPropertyIds = array_map(
				function ( $propertyId ) {
					return new PropertyId( $propertyId );
				},
				json_decode( $data[2] )
			);
			try {
				$dumpMetaInformation = new DumpMetaInformation(
					$data[0],
					new ItemId( $data[1] ),
					$identifierPropertyIds,
					$data[3],
					$data[4],
					$data[5],
					intval( $data[6] ),
					new ItemId( $data[7] )
				);
			}
			catch( \InvalidArgumentException $e ) {
				exit( 'Input file has bad formatted values.' );
			}
			$dumpIds[] = $dumpMetaInformation->getDumpId();

			try {
				$this->dumpMetaInformationStore->save( $dumpMetaInformation );
			}
			catch( \DBError $e ) {
				exit( 'Unknown database error occurred.' );
			}

			$i++;
			$this->log( "\r\033[K" );
			$this->log( "$i rows inserted or updated" );
		}

		fclose( $csvFile );

		$this->log( "\n" );

		return $dumpIds;
	}

	/**
	 * Inserts external values stored in csv file into database
	 */
	private function insertExternalValues() {
		$this->log( "Insert new data values\n" );

		$csvFile = fopen( $this->importSettings->getExternalValuesFilePath(), 'rb' );
		if( !$csvFile ) {
			exit( 'Error while reading CSV file.' );
		}

		$i = 0;
		$accumulator = array();
		while ( true ) {
			$data = fgetcsv( $csvFile );
			if ( $data === false || ++$i % $this->importSettings->getBatchSize() === 0 ) {
				try {
					$this->externalDataRepo->insertBatch( $accumulator );
				}
				catch( \DBError $e ) {
					exit( 'Unknown database error occurred.' );
				}
				wfGetLBFactory()->waitForReplication();

				$this->log( "\r\033[K" );
				$this->log( "$i rows inserted" );

				$accumulator = array();

				if ( $data === false ) {
					break;
				}
			}

			$accumulator[] = $data;
		}

		fclose( $csvFile );

		$this->log( "\n" );
	}

	/**
	 * @param string $text
	 */
	private function log( $text ) {
		if ( !$this->importSettings->isQuiet() ) {
			print $text;
		}
	}

}
