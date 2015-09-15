<?php

namespace WikibaseQuality\ExternalValidation\Tests;

use Wikibase\DataModel\Entity\PropertyId;
use WikibaseQuality\ExternalValidation\ExternalDataRepo;


/**
 * @covers WikibaseQuality\ExternalValidation\ExternalDataRepo
 *
 * @group WikibaseQualityExternalValidation
 *
 * @group Database
 * @group medium
 *
 * @author BP2014N1
 * @license GNU GPL v2+
 */
class ExternalDataRepoTest extends \MediaWikiTestCase {

	/**
	 * @var ExternalDataRepo
	 */
	private $externalDataRepo;

	public function setUp() {
		parent::setUp();

		$this->tablesUsed[] = ExternalDataRepo::TABLE_NAME;

		$this->externalDataRepo = new ExternalDataRepo();
	}

	public function tearDown() {
		unset( $this->externalDataRepo );

		parent::tearDown();
	}

	/**
	 * Adds temporary test data to database
	 *
	 * @throws \DBUnexpectedError
	 */
	public function addDBData() {
		$this->db->delete(
			ExternalDataRepo::TABLE_NAME,
			'*'
		);

		$this->db->insert(
			ExternalDataRepo::TABLE_NAME,
			array(
				array(
					'dump_id' => 'foobar',
					'external_id' => 'foo',
					'pid' => 'P1',
					'external_value' => 'foo'
				),
				array(
					'dump_id' => 'foobar',
					'external_id' => 'foo',
					'pid' => 'P1',
					'external_value' => 'bar'
				),
				array(
					'dump_id' => 'foobar',
					'external_id' => 'bar',
					'pid' => 'P2',
					'external_value' => 'bar'
				),
				array(
					'dump_id' => 'fubar',
					'external_id' => 'bar',
					'pid' => 'P3',
					'external_value' => 'baz'
				)
			)
		);
	}


	/**
	 * @dataProvider getDataProvider
	 */
	public function testGetExternalData( $dumpIds, $externalIds, $propertyIds, $expectedExternalData, $expectedException = null ) {
		$this->setExpectedException( $expectedException );

		$externalData = $this->externalDataRepo->getExternalData( $dumpIds, $externalIds, $propertyIds );

		$this->assertArrayEquals( $expectedExternalData, $externalData );
	}

	/**
	 * Test cases for testGetExternalData
	 * @return array
	 */
	public function getDataProvider() {
		return array(
			array(
				array( 'foobar' ),
				array( 'foo', 'bar' ),
				array(
					new PropertyId( 'P1' ),
					new PropertyId( 'P2' ),
					new PropertyId( 'P3' )
				),
				array(
					'foobar' => array(
						'foo' => array(
							'P1' => array( 'foo', 'bar' )
						),
						'bar' => array(
							'P2' => array( 'bar' )
						)
					)
				)
			),
			array(
				array( 'fubar' ),
				array( 'foo', 'bar' ),
				array(
					new PropertyId( 'P1' ),
					new PropertyId( 'P3' )
				),
				array(
					'fubar' => array(
						'bar' => array(
							'P3' => array( 'baz' )
						)
					)
				)
			),
			array(
				array( 'foobar', 'fubar' ),
				array( 'foo', 'bar' ),
				array(
					new PropertyId( 'P1' ),
					new PropertyId( 'P3' )
				),
				array(
					'foobar' => array(
						'foo' => array(
							'P1' => array( 'foo', 'bar' )
						)
					),
					'fubar' => array(
						'bar' => array(
							'P3' => array( 'baz' )
						)
					)
				)
			),
			array(
				array( 42 ),
				array('foo', 'bar'),
				array(
					new PropertyId( 'P1' ),
					new PropertyId( 'P3' )
				),
				null,
				'InvalidArgumentException'
			),
			array(
				array( 'foobar', 'fubar' ),
				array( 42 ),
				array(
					new PropertyId( 'P1' ),
					new PropertyId( 'P3' )
				),
				null,
				'InvalidArgumentException'
			),
			array(
				array(),
				array('foo', 'bar'),
				array(),
				null,
				'InvalidArgumentException'
			),
			array(
				array('foo', 'bar'),
				array(),
				array(),
				null,
				'InvalidArgumentException'
			)
		);
	}


	/**
	 * @dataProvider insertDataProvider
	 */
	public function testInsert( $dumpId, $externalId, PropertyId $propertyId, $externalValue, $expectedException = null ) {
		$this->setExpectedException( $expectedException );

		$this->externalDataRepo->insert( $dumpId, $externalId, $propertyId, $externalValue );

		$this->assertSelect(
			ExternalDataRepo::TABLE_NAME,
			array(
				'external_id',
				'pid',
				'external_value'
			),
			"dump_id='$dumpId'",
			array(
				array(
					$externalId,
					$propertyId->getSerialization(),
					$externalValue
				)
			)
		);
	}

	/**
	 * Test cases for testInsert
	 * @return array
	 */
	public function insertDataProvider() {
		return array(
			array(
				'insert',
				'fubar',
				new PropertyId( 'P42' ),
				'42'
			),
			array(
				42,
				'foobar',
				new PropertyId( 'P42' ),
				'foobar',
				'InvalidArgumentException'
			),
			array(
				'foobar',
				42,
				new PropertyId( 'P42' ),
				'foobar',
				'InvalidArgumentException'
			),
			array(
				'foobar',
				'foobar',
				new PropertyId( 'P42' ),
				42,
				'InvalidArgumentException'
			)
		);
	}


	/**
	 * @dataProvider insertBatchDataProvider
	 */
	public function testInsertBatch( $externalDataBatch, $expectedException = null ) {
		$this->setExpectedException( $expectedException );

		$this->externalDataRepo->insertBatch( $externalDataBatch );


		$this->assertSelect(
			ExternalDataRepo::TABLE_NAME,
			array(
				'dump_id',
				'external_id',
				'pid',
				'external_value'
			),
			"dump_id='insertBatch'",
			$externalDataBatch
		);
	}

	/**
	 * Test cases for testInsertBatch
	 * @return array
	 */
	public function insertBatchDataProvider() {
		return array(
			array(
				array(
					array(
						'insertBatch',
						'foobar',
						'P42',
						'bar'
					),
					array(
						'insertBatch',
						'foobaz',
						'P42',
						'baz'
					),
					array(
						'insertBatch',
						'fubar',
						'P42',
						'foo'
					)
				)
			)
		);
	}


	public function testDeleteOfDump() {
		$dumpId = 'deleteOfDump';
		$this->db->insert(
			ExternalDataRepo::TABLE_NAME,
			array(
				array(
					'dump_id' => $dumpId,
					'external_id' => 'foo',
					'pid' => 'P1',
					'external_value' => 'foo'
				),
				array(
					'dump_id' => $dumpId,
					'external_id' => 'bar',
					'pid' => 'P2',
					'external_value' => 'bar'
				)
			)
		);

		$this->externalDataRepo->deleteOfDump( $dumpId );

		$this->assertSelect(
			ExternalDataRepo::TABLE_NAME,
			array( 'count' => 'COUNT(row_id)' ),
			"dump_id='$dumpId'",
			array(
				array( '0' )
			)
		);
	}

	/**
	 * @dataProvider deleteDataProvider
	 */
	public function testDeleteOfDumpInvalidArguments( $dumpId, $limit ) {
		$this->setExpectedException( 'InvalidArgumentException' );

		$this->externalDataRepo->deleteOfDump( $dumpId, $limit );
	}

	/**
	 * @dataProvider deleteDataProvider
	 */
	public function testDeleteOfDumpsInvalidArguments( $dumpId, $limit ) {
		$this->setExpectedException( 'InvalidArgumentException' );

		$this->externalDataRepo->deleteOfDump( $dumpId, $limit );
	}

	public function deleteDataProvider() {
		return array(
			array(
				42,
				42
			),
			array(
				'foobar',
				'foobar'
			)
		);
	}
}