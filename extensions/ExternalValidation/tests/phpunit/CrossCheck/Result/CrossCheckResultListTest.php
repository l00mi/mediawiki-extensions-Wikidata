<?php

namespace WikibaseQuality\ExternalValidation\Tests\CrossCheck\Result;

use Wikibase\DataModel\Entity\PropertyId;
use WikibaseQuality\ExternalValidation\CrossCheck\Result\ComparisonResult;
use WikibaseQuality\ExternalValidation\CrossCheck\Result\CrossCheckResult;
use WikibaseQuality\ExternalValidation\CrossCheck\Result\CrossCheckResultList;


/**
 * @covers WikibaseQuality\ExternalValidation\CrossCheck\Result\CrossCheckResultList
 *
 * @group WikibaseQualityExternalValidation
 *
 * @author BP2014N1
 * @license GNU GPL v2+
 */
class CrossCheckResultListTest extends \MediaWikiTestCase {
	/**
	 * @var CrossCheckResult
	 */
	private $singleCrossCheckResult;

	/**
	 * @var CrossCheckResult[]
	 */
	private $crossCheckResults;

	/**
	 * @var CrossCheckResultList
	 */
	private $crossCheckResultList;

	/**
	 * @var CrossCheckResult[]
	 */
	private $anotherCrossCheckResults;

	/**
	 * @var CrossCheckResultList
	 */
	private $anotherCrossCheckResultList;

	protected function setUp() {
		parent::setUp();

		// Generate test data
		$this->singleCrossCheckResult = $this->getCrossCheckResultMock( new PropertyId( 'P7' ), ComparisonResult::STATUS_MATCH, false );
		$this->crossCheckResults = array(
			$this->getCrossCheckResultMock( new PropertyId( 'P1' ), ComparisonResult::STATUS_MISMATCH, false ),
			$this->getCrossCheckResultMock( new PropertyId( 'P2' ), ComparisonResult::STATUS_MATCH, false ),
			$this->getCrossCheckResultMock( new PropertyId( 'P3' ), ComparisonResult::STATUS_MISMATCH, false )
		);
		$this->crossCheckResultList = new CrossCheckResultList( $this->crossCheckResults );

		$this->anotherCrossCheckResults = array(
			$this->getCrossCheckResultMock( new PropertyId( 'P4' ), ComparisonResult::STATUS_MATCH, true ),
			$this->getCrossCheckResultMock( new PropertyId( 'P5' ), ComparisonResult::STATUS_MATCH, false ),
			$this->getCrossCheckResultMock( new PropertyId( 'P5' ), ComparisonResult::STATUS_MATCH, true ),
			$this->getCrossCheckResultMock( new PropertyId( 'P6' ), ComparisonResult::STATUS_MATCH, false )
		);
		$this->anotherCrossCheckResultList = new CrossCheckResultList( $this->anotherCrossCheckResults );
	}

	protected function tearDown() {
		unset(
			$this->singleCrossCheckResult,
			$this->crossCheckResults,
			$this->crossCheckResultList,
			$this->anotherCrossCheckResults,
			$this->anotherCrossCheckResultList
		);

		parent::tearDown();
	}

	/**
	 * @dataProvider constructDataProvider
	 */
	public function testConstruct( array $results = array(), $expectedException = null ) {
		if ( $expectedException ) {
			$this->setExpectedException( $expectedException );
		}

		$crossCheckResultList = new CrossCheckResultList( $results );

		$this->assertEquals( $results, $crossCheckResultList->toArray() );
	}

	/**
	 * Test cases for testConstruct
	 * @return array
	 */
	public function constructDataProvider() {
		return array(
			array(),
			array(
				array(
					$this->getCrossCheckResultMock( new PropertyId( 'P7' ), ComparisonResult::STATUS_MATCH, false )
				)
			),
			array(
				array( 'foobar' ),
				'InvalidArgumentException'
			)
		);
	}

	public function testCount() {
		$this->assertEquals( 3, $this->crossCheckResultList->count() );
		$this->assertEquals( 4, $this->anotherCrossCheckResultList->count() );
	}

	public function testAddingCrossCheckResults() {
		$count = $this->crossCheckResultList->count();
		$this->crossCheckResultList->add( $this->singleCrossCheckResult );

		$expected = $count + 1;
		$actual = $this->crossCheckResultList->count();
		$this->assertEquals( $expected, $actual );
	}

	public function testMergingCrossCheckResultsLists() {
		$count = $this->crossCheckResultList->count();
		$anotherCount = $this->anotherCrossCheckResultList->count();
		$this->crossCheckResultList->merge( $this->anotherCrossCheckResultList );

		$expected = $count + $anotherCount;
		$actual = $this->crossCheckResultList->count();
		$this->assertEquals( $expected, $actual );
	}

	public function testGetPropertyIds() {
		$expected = array( new PropertyId( 'P1' ), new PropertyId( 'P2' ), new PropertyId( 'P3' ) );
		$actual = $this->crossCheckResultList->getPropertyIds();
		$this->assertEquals( $expected, $actual );

		$expected = array( new PropertyId( 'P4' ), new PropertyId( 'P5' ), new PropertyId( 'P6' ) );
		$actual = $this->anotherCrossCheckResultList->getPropertyIds();
		$this->assertEquals( $expected, $actual );
	}

	public function testGetByPropertyId() {
		$actual = $this->crossCheckResultList->getByPropertyId( new PropertyId( 'P2' ) )->count();
		$this->assertEquals( 1, $actual );

		$actual = $this->anotherCrossCheckResultList->getByPropertyId( new PropertyId( 'P5' ) )->count();
		$this->assertEquals( 2, $actual );
	}

	public function testToArray() {
		$actual = $this->crossCheckResultList->toArray();
		$this->assertArrayEquals( $this->crossCheckResults, $actual );

		$actual = $this->anotherCrossCheckResultList->toArray();
		$this->assertArrayEquals( $this->anotherCrossCheckResults, $actual );
	}

	private function getCrossCheckResultMock( PropertyId $propertyId, $status, $referencesMissing ) {
		// Mock ComparisonResult
		$comparisonResultMock = $this->getMockBuilder( 'WikibaseQuality\ExternalValidation\CrossCheck\Result\ComparisonResult' )
								  ->disableOriginalConstructor()
								  ->getMock();
		$comparisonResultMock->expects( $this->any() )
						  ->method( 'getStatus' )
						  ->will( $this->returnValue( $status ) );

		// Mock ReferenceResult
		$referenceResult = $this->getMockBuilder( 'WikibaseQuality\ExternalValidation\CrossCheck\Result\ReferenceResult' )
			->disableOriginalConstructor()
			->getMock();
		$referenceResult->expects( $this->any() )
			->method( 'areReferencesMissing' )
			->will( $this->returnValue( $referencesMissing ) );

		// Mock CrossCheckResult
		$crossCheckResultMock = $this->getMockBuilder( 'WikibaseQuality\ExternalValidation\CrossCheck\Result\CrossCheckResult' )
			->disableOriginalConstructor()
			->getMock();
		$crossCheckResultMock->expects( $this->any() )
			->method( 'getPropertyId' )
			->will( $this->returnValue( $propertyId ) );
		$crossCheckResultMock->expects( $this->any() )
							 ->method( 'getComparisonResult' )
							 ->will( $this->returnValue( $comparisonResultMock ) );
		$crossCheckResultMock->expects( $this->any() )
			->method( 'getReferenceResult' )
			->will( $this->returnValue( $referenceResult ) );

		return $crossCheckResultMock;
	}
}