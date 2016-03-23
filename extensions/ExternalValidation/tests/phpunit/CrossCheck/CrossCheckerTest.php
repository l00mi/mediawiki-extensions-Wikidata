<?php

namespace WikibaseQuality\ExternalValidation\Tests\CrossCheck;

use DataValues\DataValue;
use InvalidArgumentException;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Statement\StatementList;
use WikibaseQuality\ExternalValidation\CrossCheck\Comparer\DataValueComparer;
use WikibaseQuality\ExternalValidation\CrossCheck\CrossChecker;
use WikibaseQuality\ExternalValidation\CrossCheck\ReferenceChecker;
use WikibaseQuality\ExternalValidation\CrossCheck\Result\ComparisonResult;
use WikibaseQuality\ExternalValidation\CrossCheck\Result\CrossCheckResult;
use WikibaseQuality\ExternalValidation\CrossCheck\Result\CrossCheckResultList;
use WikibaseQuality\ExternalValidation\CrossCheck\Result\ReferenceResult;
use WikibaseQuality\ExternalValidation\CrossCheck\ValueParser\ComparativeValueParser;
use WikibaseQuality\ExternalValidation\CrossCheck\ValueParser\ComparativeValueParserFactory;
use WikibaseQuality\ExternalValidation\DumpMetaInformation\DumpMetaInformation;
use WikibaseQuality\ExternalValidation\DumpMetaInformation\DumpMetaInformationLookup;
use WikibaseQuality\ExternalValidation\ExternalDataRepo;
use WikibaseQuality\Tests\Helper\JsonFileEntityLookup;

/**
 * @covers WikibaseQuality\ExternalValidation\CrossCheck\CrossChecker
 *
 * @group WikibaseQualityExternalValidation
 *
 * @uses   WikibaseQuality\ExternalValidation\CrossCheck\Comparer\DataValueComparer
 * @uses   WikibaseQuality\ExternalValidation\CrossCheck\Comparer\StringValueComparer
 * @uses   WikibaseQuality\ExternalValidation\CrossCheck\Result\CrossCheckResultList
 * @uses   WikibaseQuality\ExternalValidation\CrossCheck\Result\ComparisonResult
 * @uses   WikibaseQuality\ExternalValidation\CrossCheck\Result\CrossCheckResult
 * @uses   WikibaseQuality\ExternalValidation\CrossCheck\Result\ReferenceResult
 * @uses   WikibaseQuality\ExternalValidation\DumpMetaInformation\DumpMetaInformation
 * @uses   WikibaseQuality\ExternalValidation\CrossCheck\ReferenceChecker
 *
 * @author BP2014N1
 * @license GNU GPL v2+
 */
class CrossCheckerTest extends \MediaWikiTestCase {

	/**
	 * @var Item[]
	 */
	private $items;

	/**
	 * @var DumpMetaInformation[]
	 */
	private $dumpMetaInformation;

	/**
	 * @var array
	 */
	private $externalData;

	/**
	 * @var CrossChecker
	 */
	private $crossChecker;

	public function __construct( $name = null, $data = array(), $dataName = null ) {
		parent::__construct( $name, $data, $dataName );

		$entityLookup = new JsonFileEntityLookup( __DIR__ . '/testdata' );

		$this->items = array(
			'Q1' => $entityLookup->getEntity( new ItemId( 'Q1' ) ),
			'Q2' => $entityLookup->getEntity( new ItemId( 'Q2' ) ),
			'Q3' => $entityLookup->getEntity( new ItemId( 'Q3' ) ),
			'Q4' => $entityLookup->getEntity( new ItemId( 'Q4' ) ),
			'Q5' => new Item( new ItemId( 'Q5' ) )
		);
	}

	protected function setUp() {
		parent::setUp();

		$this->dumpMetaInformation = array(
			new DumpMetaInformation(
				'foobar',
				new ItemId( 'Q36578' ),
				array( new PropertyId( 'P227' ) ),
				'20150101000000',
				'en',
				'http://www.foo.bar',
				42,
				new ItemId( 'Q6938433' )
			),
			new DumpMetaInformation(
				'fubar',
				new ItemId( 'Q36578' ),
				array( new PropertyId( 'P228' ) ),
				'20150101000000',
				'en',
				'http://www.fu.bar',
				42,
				new ItemId( 'Q6938433' )
			)
		);

		$this->externalData = array(
			array(
				'dump_id' => 'foobar',
				'external_id' => '119033364',
				'pid' => 'P1',
				'external_value' => 'foo'
			),
			array(
				'dump_id' => 'foobar',
				'external_id' => '119033364',
				'pid' => 'P3',
				'external_value' => 'foobar'
			),
			array(
				'dump_id' => 'foobar',
				'external_id' => '121649091',
				'pid' => 'P1',
				'external_value' => 'bar'
			),
			array(
				'dump_id' => 'foobar',
				'external_id' => '345676543',
				'pid' => 'P1',
				'external_value' => 'foobar'
			),
			array(
				'dump_id' => 'fubar',
				'external_id' => '039467482',
				'pid' => 'P1',
				'external_value' => 'baz'
			),
			array(
				'dump_id' => 'fubar',
				'external_id' => '039467482',
				'pid' => 'P1',
				'external_value' => 'bar'
			)
		);

		$this->crossChecker = $this->getCrossChecker();
	}

	protected function tearDown() {
		unset( $this->items, $this->dumpMetaInformation, $this->externalData, $this->crossChecker );

		parent::tearDown();
	}

	/**
	 * @dataProvider crossCheckStatementsDataProvider
	 */
	public function testCrossCheckStatements(
		StatementList $entityStatements,
		StatementList $statements,
		array $expectedResults = null,
		$expectedException = null
	) {
		if ( $expectedException ) {
			$this->setExpectedException( $expectedException );
		}

		$results = $this->crossChecker->crossCheckStatements( $entityStatements, $statements );

		$this->runResultAssertions( $results, $expectedResults );
	}

	/**
	 * Test cases for testCrossCheckStatements
	 */
	public function crossCheckStatementsDataProvider() {

		return array(
			// Cross-check all statements of Q1
			array(
				$this->items['Q1']->getStatements(),
				$this->items['Q1']->getStatements(),
				array(
					array(
						'claimGuid' => 'Q1$c0f25a6f-9e33-41c8-be34-c86a730ff30b',
						'dumpId' => 'foobar',
						'externalId' => '119033364'
					),
					array(
						'claimGuid' => 'Q1$dd6dcfc9-55e2-4be6-b70c-d22f20f398b7',
						'dumpId' => 'foobar',
						'externalId' => '119033364'
					),
					array(
						'claimGuid' => 'Q1$27ba9958-7151-4673-8956-f8f1d8648d1e',
						'dumpId' => 'foobar',
						'externalId' => '119033364'
					)
				)
			),
			// Only cross-check statements of Q1 with P1
			array(
				$this->items['Q1']->getStatements(),
				$this->items['Q1']->getStatements()->getByPropertyId( new PropertyId( 'P1' ) ),
				array(
					array(
						'claimGuid' => 'Q1$c0f25a6f-9e33-41c8-be34-c86a730ff30b',
						'dumpId' => 'foobar',
						'externalId' => '119033364'
					),
					array(
						'claimGuid' => 'Q1$dd6dcfc9-55e2-4be6-b70c-d22f20f398b7',
						'dumpId' => 'foobar',
						'externalId' => '119033364'
					)
				)
			),
			// Cross-check Q2, which has two identifier for a single dump
			array(
				$this->items['Q2']->getStatements(),
				$this->items['Q2']->getStatements(),
				array(
					array(
						'claimGuid' => 'Q2$0adcfe9e-cda1-4f74-bc98-433150e49b53',
						'dumpId' => 'foobar',
						'externalId' => '119033364'
					),
					array(
						'claimGuid' => 'Q2$0adcfe9e-cda1-4f74-bc98-433150e49b53',
						'dumpId' => 'foobar',
						'externalId' => '121649091'
					),
					array(
						'claimGuid' => 'Q2$07c00375-1be7-43a6-ac97-32770f2bb5ac',
						'dumpId' => 'foobar',
						'externalId' => '119033364'
					),
					array(
						'claimGuid' => 'Q2$07c00375-1be7-43a6-ac97-32770f2bb5ac',
						'dumpId' => 'foobar',
						'externalId' => '121649091'
					),
				)
			),
			// Cross-check Q3, which has two identifier different dumps
			array(
				$this->items['Q3']->getStatements(),
				$this->items['Q3']->getStatements(),
				array(
					array(
						'claimGuid' => 'Q3$8439491d-0c62-41e5-9916-a1ca5d690adb',
						'dumpId' => 'foobar',
						'externalId' => '119033364'
					),
					array(
						'claimGuid' => 'Q3$8439491d-0c62-41e5-9916-a1ca5d690adb',
						'dumpId' => 'fubar',
						'externalId' => '039467482'
					),
					array(
						'claimGuid' => 'Q3$bc4f0007-bb78-4639-acff-6087cc369ac3',
						'dumpId' => 'foobar',
						'externalId' => '119033364'
					),
					array(
						'claimGuid' => 'Q3$bc4f0007-bb78-4639-acff-6087cc369ac3',
						'dumpId' => 'fubar',
						'externalId' => '039467482'
					)
				)
			),
			// Cross-check Q4, which has a novalue snak for identifier property
			array(
				$this->items['Q4']->getStatements(),
				$this->items['Q4']->getStatements(),
				array()
			),
			// Cross-check Q5, which has no statements
			array(
				$this->items['Q5']->getStatements(),
				$this->items['Q5']->getStatements(),
				array()
			),
			// Provide statements that do not belong to given entity
			array(
				$this->items['Q2']->getStatements(),
				$this->items['Q1']->getStatements()->getByPropertyId( new PropertyId( 'P2' ) ),
				null,
				InvalidArgumentException::class
			)
		);
	}

	/**
	 * Runs assertions on compare result list.
	 *
	 * @param CrossCheckResultList $actualResults
	 * @param array $expectedResults
	 */
	private function runResultAssertions( $actualResults, $expectedResults ) {
		if ( $actualResults ) {
			$actualResults = array_map(
				function ( CrossCheckResult $result ) {
					return array(
						'claimGuid' => $result->getClaimGuid(),
						'dumpId' => $result->getDumpMetaInformation()->getDumpId(),
						'externalId' => $result->getExternalId()
					);
				},
				$actualResults->toArray()
			);
			$this->assertArrayEquals( $expectedResults, $actualResults );
		} else {
			$this->assertEquals( $expectedResults, $actualResults );
		}
	}

	private function getCrossChecker() {
		$dataValue = $this->getMockForAbstractClass( DataValue::class );
		$comparativeValueParser = $this->getMockBuilder( ComparativeValueParser::class )
			->disableOriginalConstructor()
			->getMock();
		$comparativeValueParser->expects( $this->any() )
			->method( 'parse' )
			->will( $this->returnValue( $dataValue ) );
		$comparativeValueParserFactory = $this->getMockBuilder( ComparativeValueParserFactory::class )
			->disableOriginalConstructor()
			->setMethods( array( 'newComparativeValueParser' ) )
			->getMock();
		$comparativeValueParserFactory->expects( $this->any() )
			->method( 'newComparativeValueParser' )
			->will( $this->returnValue( $comparativeValueParser ) );

		$dataValueComparer = $this->getMockBuilder( DataValueComparer::class )
		->setMethods( array( 'compare' ) )
		->getMockForAbstractClass();
		$dataValueComparer->expects( $this->any() )
		->method( 'compare' )
		->will( $this->returnValue( ComparisonResult::STATUS_MATCH ) );

		$referenceResult = $this->getMockBuilder( ReferenceResult::class )
		->disableOriginalConstructor()
		->getMock();
		$referenceHandler = $this->getMockBuilder( ReferenceChecker::class )
		->setMethods( array( 'execute' ) )
		->getMock();
		$referenceHandler->expects( $this->any() )
		->method( 'execute' )
		->will( $this->returnValue( $referenceResult ) );

		return new CrossChecker(
			new JsonFileEntityLookup( __DIR__ . '/testdata' ),
			$comparativeValueParserFactory,
			$dataValueComparer,
			$referenceHandler,
			$this->getDumpMetaInformationLookupMock(),
			$this->getExternalDataRepoMock()
		);
	}

	/**
	 * @return DumpMetaInformationLookup
	 */
	private function getDumpMetaInformationLookupMock() {
		$dumpMetaInformationRepo = $this->getMockForAbstractClass( DumpMetaInformationLookup::class );
		$dumpMetaInformation = $this->dumpMetaInformation;
		$dumpMetaInformationRepo->expects( $this->any() )
		->method( 'getWithIdentifierProperties' )
		->will( $this->returnCallback(
			function ( array $identifierPropertyIds ) use ( $dumpMetaInformation ) {
				$result = array();
				foreach ( $dumpMetaInformation as $dump ) {
					if ( count( array_intersect( $identifierPropertyIds, $dump->getIdentifierPropertyIds() ) ) > 0 ) {
						$result[ $dump->getDumpId() ] = $dump;
					}
				}
				return $result;
			}
		) );

		return $dumpMetaInformationRepo;
	}

	/**
	 * @return ExternalDataRepo
	 */
	private function getExternalDataRepoMock() {
		$externalDataRepo = $this->getMockBuilder( ExternalDataRepo::class )
		->disableOriginalConstructor()
		->setMethods( array( 'getExternalData' ) )
		->getMock();
		$externalData = $this->externalData;
		$externalDataRepo->expects( $this->any() )
		->method( 'getExternalData' )
		->will( $this->returnCallback(
			function ( array $dumpIds, array $externalIds, array $propertyIds ) use ( $externalData ) {
				$result = array();
				foreach ( $externalData as $row ) {
					$dumpId = $row['dump_id'];
					$externalId = $row['external_id'];
					$propertyId = $row['pid'];

					if ( in_array( $dumpId, $dumpIds ) && in_array( $externalId, $externalIds ) && in_array( $propertyId, $propertyIds ) ) {
						$result[ $dumpId ][ $externalId ][ $propertyId ][] = $row['external_value'];
					}
				}
				return $result;
			}
		) );

		return $externalDataRepo;
	}

}
