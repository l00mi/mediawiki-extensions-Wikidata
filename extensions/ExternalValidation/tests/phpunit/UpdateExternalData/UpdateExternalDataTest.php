<?php

namespace WikibaseQuality\ExternalValidation\Tests\UpdateExternalData;

use WikibaseQuality\ExternalValidation\DumpMetaInformation\SqlDumpMetaInformationRepo;
use WikibaseQuality\ExternalValidation\ExternalDataRepo;
use WikibaseQuality\ExternalValidation\Maintenance\UpdateExternalData;

/**
 * @covers WikibaseQuality\ExternalValidation\UpdateExternalData\ExternalDataImporter
 * @covers WikibaseQuality\ExternalValidation\UpdateExternalData\CsvImportSettings
 * @covers WikibaseQuality\ExternalValidation\Maintenance\UpdateExternalData
 *
 * @group WikibaseQualityExternalValidation
 * @group Database
 * @group medium
 *
 * @uses   WikibaseQuality\ExternalValidation\DumpMetaInformation\DumpMetaInformation
 * @uses   WikibaseQuality\ExternalValidation\DumpMetaInformation\SqlDumpMetaInformationRepo
 *
 * @author BP2014N1
 * @license GNU GPL v2+
 */
class UpdateExternalDataTest extends \MediaWikiTestCase {

	protected function setUp() {
		parent::setUp();

		$this->tablesUsed[] = SqlDumpMetaInformationRepo::META_TABLE_NAME;
		$this->tablesUsed[] = ExternalDataRepo::TABLE_NAME;
	}

	public function addDBData() {
		// Truncate tables
		$this->db->delete(
			SqlDumpMetaInformationRepo::META_TABLE_NAME,
			'*'
		);
		$this->db->delete(
			SqlDumpMetaInformationRepo::IDENTIFIER_PROPERTIES_TABLE_NAME,
			'*'
		);
		$this->db->delete(
			ExternalDataRepo::TABLE_NAME,
			'*'
		);

		// Insert external test data
		$this->db->insert(
			SqlDumpMetaInformationRepo::META_TABLE_NAME,
			array(
				'id' => 'foobar',
				'source_qid' => 'Q36578',
				'import_date' => $this->db->timestamp( '20150101000000' ),
				'language' => 'en',
				'source_url' => 'http://www.foo.bar',
				'size' => 42,
				'license_qid' => 'Q6938433'
			)
		);
		$this->db->insert(
			SqlDumpMetaInformationRepo::IDENTIFIER_PROPERTIES_TABLE_NAME,
			array(
				'dump_id' => 'foobar',
				'identifier_pid' => 'P227'
			)
		);

		// Insert external data
		$this->db->insert(
			ExternalDataRepo::TABLE_NAME,
			array(
				array(
					'dump_id' => 'foobar',
					'external_id' => '1234',
					'pid' => 'P31',
					'external_value' => 'foo'
				),
				array(
					'dump_id' => 'foobar',
					'external_id' => '1234',
					'pid' => 'P35',
					'external_value' => 'bar'
				)
			)
		);
	}

	public function testExecute() {
		// Execute script
		$maintenanceScript = new UpdateExternalData();
		$args = array(
			'external-values-file' => __DIR__ . '/testdata/external_values.csv',
			'dump-information-file' => __DIR__ . '/testdata/dump_information.csv',
			'batch-size' => 2,
			'quiet' => true
		);
		$maintenanceScript->loadParamsAndArgs( null, $args, null );
		$maintenanceScript->execute();

		// Run assertions on meta information
		$actualRow = $this->db->selectRow(
			SqlDumpMetaInformationRepo::META_TABLE_NAME,
			'*',
			array( 'id' => 'foobar' )
		);
		$this->assertEquals( 'Q36578', $actualRow->source_qid );
		$this->assertEquals( '20150401144144', wfTimestamp( TS_MW, $actualRow->import_date ) );
		$this->assertEquals( 'de', $actualRow->language );
		$this->assertEquals( 'http://www.foo.bar', $actualRow->source_url );
		$this->assertEquals( '590798465', $actualRow->size );
		$this->assertEquals( 'Q6938433', $actualRow->license_qid );

		// Run assertions on identifier properties
		$this->assertSelect(
			SqlDumpMetaInformationRepo::IDENTIFIER_PROPERTIES_TABLE_NAME,
			array(
				'identifier_pid',
				'dump_id'
			),
			array(),
			array(
				array(
					'P227',
					'foobar'
				),
				array(
					'P228',
					'foobar'
				)
			)
		);

		// Run assertions on external data
		$this->assertSelect(
			ExternalDataRepo::TABLE_NAME,
			array(
				'count' => 'count(*)'
			),
			array(),
			array(
				array( '3' )
			)
		);
		$this->assertSelect(
			ExternalDataRepo::TABLE_NAME,
			array(
				'dump_id',
				'pid',
				'external_id',
				'external_value'
			),
			array(),
			array(
				array(
					'foobar',
					'19',
					'100001718',
					'Parma'
				),
				array(
					'foobar',
					'20',
					'100001718',
					'Paris'
				),
				array(
					'fubar',
					'569',
					'100001718',
					'01.06.1771'
				)
			)
		);
	}

}
