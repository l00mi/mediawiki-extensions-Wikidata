<?php

namespace WikibaseQuality\ExternalValidation\Tests\UpdateExternalData;

use WikibaseQuality\ExternalValidation\UpdateExternalData\CsvImportSettings;

/**
 * @covers WikibaseQuality\ExternalValidation\UpdateExternalData\CsvImportSettings
 *
 * @group WikibaseQualityExternalValidation
 *
 * @author BP2014N1
 * @license GNU GPL v2+
 */
class CsvImportSettingsTest extends \MediaWikiTestCase {

	/**
	 * Valid arguments are already tested in UpdateTableTest
	 *
	 * @dataProvider provideInvalidArguments()
	 */
	public function testConstructWithInvalidArguments( $externalValuesFilePath, $dumpInformationFilePath, $batchSize, $quiet ) {
		$this->setExpectedException( 'InvalidArgumentException' );
		new CsvImportSettings( $externalValuesFilePath, $dumpInformationFilePath, $batchSize, $quiet );
	}

	public function provideInvalidArguments() {
		$externalValuesFilePath = 'foobar';
		$dumpInformationFilePath = 'foobar';
		$batchSize = 42;
		$quiet = true;

		return array(
			array(
				42,
				$dumpInformationFilePath,
				$batchSize,
				$quiet
			),
			array(
				$externalValuesFilePath,
				42,
				$batchSize,
				$quiet
			),
			array(
				$externalValuesFilePath,
				$dumpInformationFilePath,
				'foobar',
				$quiet
			),
			array(
				$externalValuesFilePath,
				$dumpInformationFilePath,
				$batchSize,
				'foobar'
			)
		);
	}

}
