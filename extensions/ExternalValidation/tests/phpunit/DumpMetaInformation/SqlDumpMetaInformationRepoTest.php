<?php

namespace WikibaseQuality\ExternalValidation\Tests\DumpMetaInformation;

use InvalidArgumentException;
use Wikibase\DataModel\Entity\BasicEntityIdParser;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\PropertyId;
use WikibaseQuality\ExternalValidation\DumpMetaInformation\DumpMetaInformation;
use WikibaseQuality\ExternalValidation\DumpMetaInformation\SqlDumpMetaInformationRepo;

/**
 * @covers \WikibaseQuality\ExternalValidation\DumpMetaInformation\SqlDumpMetaInformationRepo
 *
 * @group WikibaseQualityExternalValidation
 * @group Database
 * @group medium
 *
 * @uses   \WikibaseQuality\ExternalValidation\DumpMetaInformation\DumpMetaInformation
 *
 * @author BP2014N1
 * @license GNU GPL v2+
 */
class SqlDumpMetaInformationRepoTest extends \MediaWikiTestCase {

	/**
	 * @var SqlDumpMetaInformationRepo
	 */
	private $dumpMetaInformationRepo;

	/**
	 * @var DumpMetaInformation[]
	 */
	private $dumpMetaInformation;

	public function __construct( $name = null, $data = array(), $dataName = null ) {
		parent::__construct( $name, $data, $dataName );

		// Create example dump meta information
		$this->dumpMetaInformation = array(
			'foo' => new DumpMetaInformation(
				'foo',
				new ItemId( 'Q1' ),
				array( new PropertyId( 'P1' ) ),
				'20150101000000',
				'en',
				'http://www.foo.bar',
				42,
				new ItemId( 'Q6938433' )
			),
			'bar' => new DumpMetaInformation(
				'bar',
				new ItemId( 'Q2' ),
				array( new PropertyId( 'P2' ) ),
				'20200101121212',
				'de',
				'http://www.fu.bar',
				4242,
				new ItemId( 'Q6938433' )
			),
			'baz' => new DumpMetaInformation(
				'baz',
				new ItemId( 'Q3' ),
				array(
					new PropertyId( 'P2' ),
					new PropertyId( 'P3' )
				),
				'20250101131313',
				'de',
				'http://www.fu.baz',
				424242,
				new ItemId( 'Q6938433' )
			)
		);
	}

	protected function setUp() {
		parent::setUp();

		$this->tablesUsed[] = SqlDumpMetaInformationRepo::META_TABLE_NAME;
		$this->tablesUsed[] = SqlDumpMetaInformationRepo::IDENTIFIER_PROPERTIES_TABLE_NAME;

		$this->dumpMetaInformationRepo = new SqlDumpMetaInformationRepo( new BasicEntityIdParser() );
	}

	public function tearDown() {
		unset( $this->dumpMetaInformationRepo );

		parent::tearDown();
	}

	/**
	 * Adds temporary test data to database
	 *
	 * @throws \DBUnexpectedError
	 */
	public function addDBData() {
		$this->db->delete(
			SqlDumpMetaInformationRepo::META_TABLE_NAME,
			'*'
		);
		$this->db->delete(
			SqlDumpMetaInformationRepo::IDENTIFIER_PROPERTIES_TABLE_NAME,
			'*'
		);

		foreach ( $this->dumpMetaInformation as $dumpMetaInformation ) {
			$this->db->insert(
				SqlDumpMetaInformationRepo::META_TABLE_NAME,
				array(
					'id' => $dumpMetaInformation->getDumpId(),
					'source_qid' => $dumpMetaInformation->getSourceItemId()->getSerialization(),
					'import_date' => $this->db->timestamp( $dumpMetaInformation->getImportDate() ),
					'language' => $dumpMetaInformation->getLanguageCode(),
					'source_url' => $dumpMetaInformation->getSourceUrl(),
					'size' => $dumpMetaInformation->getSize(),
					'license_qid' => $dumpMetaInformation->getLicenseItemId()
				)
			);
			foreach ( $dumpMetaInformation->getIdentifierPropertyIds() as $propertyId ) {
				$this->db->insert(
					SqlDumpMetaInformationRepo::IDENTIFIER_PROPERTIES_TABLE_NAME,
					array(
						'dump_id' => $dumpMetaInformation->getDumpId(),
						'identifier_pid' => $propertyId->getSerialization()
					)
				);
			}
		}
	}

	/**
	 * @return array
	 */
	public function getWithIdDataProvider() {
		return array(
			// Single id
			array(
				'foo',
				$this->dumpMetaInformation['foo']
			),
			array(
				'bar',
				$this->dumpMetaInformation['bar']
			),
			array(
				'baz',
				$this->dumpMetaInformation['baz']
			),
			// Non-existent id
			array(
				'foobar',
				null
			),
			// Invalid ids
			array(
				42,
				null,
				InvalidArgumentException::class
			),
			array(
				array( 'foo' ),
				null,
				InvalidArgumentException::class
			),
			array(
				null,
				null,
				InvalidArgumentException::class
			)
		);
	}

	/**
	 * @dataProvider getWithIdsDataProvider
	 */
	public function testGetWithIds( $dumpIds, $expectedDumpMetaInformation, $expectedException = null ) {
		$this->setExpectedException( $expectedException );

		$dumpMetaInformation = $this->dumpMetaInformationRepo->getWithIds( $dumpIds );

		$this->assertEquals( $expectedDumpMetaInformation, $dumpMetaInformation );
	}

	/**
	 * @return array
	 */
	public function getWithIdsDataProvider() {
		return array(
			// Multiple ids
			array(
				array(
					'foo',
					'bar'
				),
				array(
					'foo' => $this->dumpMetaInformation['foo'],
					'bar' => $this->dumpMetaInformation['bar']
				)
			),
			// Empty array of dump ids
			array(
				array(),
				array()
			),
			// Non-existent id
			array(
				array( 'foobar' ),
				array()
			),
			// Invalid ids
			array(
				array( 42 ),
				null,
				InvalidArgumentException::class
			)
		);
	}

	public function testGetAllDumpMetaInformation() {
		$dumpMetaInformation = $this->dumpMetaInformationRepo->getAll();
		$expectedDumpMetaInformation = array(
			'foo' => $this->dumpMetaInformation['foo'],
			'bar' => $this->dumpMetaInformation['bar'],
			'baz' => $this->dumpMetaInformation['baz']
		);

		$this->assertEquals( $expectedDumpMetaInformation, $dumpMetaInformation );
	}

	/**
	 * @dataProvider getWithIdentifierPropertiesDataProvider
	 */
	public function testGetWithIdentifierProperties( $identifierPropertyIds, $expectedDumpMetaInformation, $expectedException = null ) {
		$this->setExpectedException( $expectedException );

		$dumpMetaInformation = $this->dumpMetaInformationRepo->getWithIdentifierProperties( $identifierPropertyIds );

		$this->assertEquals( $expectedDumpMetaInformation, $dumpMetaInformation );
	}

	/**
	 * Test cases for testGetWithIdentifierProperties
	 * @return array
	 */
	public function getWithIdentifierPropertiesDataProvider() {
		return array(
			// Single identifier property id with single dump
			array(
				array( new PropertyId( 'P1' ) ),
				array(
					'foo' => $this->dumpMetaInformation['foo']
				)
			),
			// Single identifier property id with multiple dump
			array(
				array( new PropertyId( 'P2' ) ),
				array(
					'bar' => $this->dumpMetaInformation['bar'],
					'baz' => $this->dumpMetaInformation['baz']
				)
			),
			// Multiple identifier property id with multiple dump
			array(
				array(
					new PropertyId( 'P1' ),
					new PropertyId( 'P2' )
				),
				array(
					'foo' => $this->dumpMetaInformation['foo'],
					'bar' => $this->dumpMetaInformation['bar'],
					'baz' => $this->dumpMetaInformation['baz']
				)
			),
			// Empty input array
			array(
				array(),
				array()
			),
			// Invalid identifier property ids
			array(
				array( 'P42' ),
				null,
				InvalidArgumentException::class
			)
		);
	}

	/**
	 * @dataProvider saveDumpMetaInformationDataProvider
	 */
	public function testSaveDumpMetaInformation( DumpMetaInformation $dumpMetaInformation ) {
		$this->dumpMetaInformationRepo->save( $dumpMetaInformation );

		$actualRow = $this->db->selectRow(
			SqlDumpMetaInformationRepo::META_TABLE_NAME,
			'*',
			array( 'id' => $dumpMetaInformation->getDumpId() )
		);
		$this->assertEquals( $dumpMetaInformation->getDumpId(), $actualRow->id );
		$this->assertEquals( $dumpMetaInformation->getSourceItemId()->getSerialization(), $actualRow->source_qid );
		$this->assertEquals( $dumpMetaInformation->getImportDate(), wfTimestamp( TS_MW, $actualRow->import_date ) );
		$this->assertEquals( $dumpMetaInformation->getLanguageCode(), $actualRow->language );
		$this->assertEquals( $dumpMetaInformation->getSourceUrl(), $actualRow->source_url );
		$this->assertEquals( $dumpMetaInformation->getSize(), $actualRow->size );
		$this->assertEquals( $dumpMetaInformation->getLicenseItemId()->getSerialization(), $actualRow->license_qid );
	}

	/**
	 * Test cases for testSaveDumpMetaInformation
	 */
	public function saveDumpMetaInformationDataProvider() {
		$this->dumpMetaInformation['foo'] = new DumpMetaInformation(
			$this->dumpMetaInformation['foo']->getDumpId(),
			$this->dumpMetaInformation['foo']->getSourceItemId(),
			$this->dumpMetaInformation['foo']->getIdentifierPropertyIds(),
			$this->dumpMetaInformation['foo']->getImportDate(),
			'de',
			$this->dumpMetaInformation['foo']->getSourceUrl(),
			$this->dumpMetaInformation['foo']->getSize(),
			$this->dumpMetaInformation['foo']->getLicenseItemId()
		);

		return array(
			// Update existing one
			array(
				$this->dumpMetaInformation['foo']
			),
			// Insert new one
			array(
				new DumpMetaInformation(
					'fubar',
					new ItemId( 'Q3' ),
					array( new PropertyId( 'P3' ) ),
					'20150101000000',
					'en',
					'http://www.fubar.com',
					42,
					new ItemId( 'Q6938433' )
				)
			)
		);
	}

}
