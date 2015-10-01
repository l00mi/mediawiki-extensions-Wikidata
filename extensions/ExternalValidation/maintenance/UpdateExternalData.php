<?php

namespace WikibaseQuality\ExternalValidation\Maintenance;

use Maintenance;
use WikibaseQuality\ExternalValidation\ExternalValidationServices;
use WikibaseQuality\ExternalValidation\UpdateExternalData\CsvImportSettings;
use WikibaseQuality\ExternalValidation\UpdateExternalData\ExternalDataImporter;

$basePath = getenv( 'MW_INSTALL_PATH' ) !== false
	? getenv( 'MW_INSTALL_PATH' )
	: __DIR__ . '/../../..';
require_once $basePath . '/maintenance/Maintenance.php';

/**
 * Maintenance script that evokes updates of wbqev_external_data, wbqev_dump_information, wbqev_identifier_properties
 * Input data is taken from tar file, which can be generated by DumpConverter tool.
 *
 * @package WikibaseQuality\ExternalValidation\Maintenance
 * @author BP2014N1
 * @license GNU GPL v2+
 */
class UpdateExternalData extends Maintenance {

	public function __construct() {
		parent::__construct();

		$this->mDescription = "Imports external entities from given CSV files into the local database. CSV files can be generated using the DumpConverter.";
		$this->addOption( 'external-values-file', 'CSV file containing external values for import.', true, true );
		$this->addOption( 'dump-information-file', 'CSV file containing dump meta information for import.', true, true );
		$this->setBatchSize( 1000 );
	}

	public function execute() {
		$context = new CsvImportSettings(
			$this->getOption( 'external-values-file' ),
			$this->getOption( 'dump-information-file' ),
			$this->mBatchSize,
			$this->isQuiet()
		);
		$importer = new ExternalDataImporter(
			$context,
			ExternalValidationServices::getDefaultInstance()->getDumpMetaInformationStore(),
			ExternalValidationServices::getDefaultInstance()->getExternalDataRepo()
		);
		$importer->import();
	}
}

// @codeCoverageIgnoreStart
$maintClass = 'WikibaseQuality\ExternalValidation\Maintenance\UpdateExternalData';
require_once RUN_MAINTENANCE_IF_MAIN;
// @codeCoverageIgnoreEnd
