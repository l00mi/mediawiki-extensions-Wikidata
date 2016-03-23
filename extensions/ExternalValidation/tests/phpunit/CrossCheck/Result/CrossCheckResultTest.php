<?php

namespace WikibaseQuality\ExternalValidation\Tests\CrossCheck\Result;

use InvalidArgumentException;
use PHPUnit_Framework_TestCase;
use Wikibase\DataModel\Entity\PropertyId;
use WikibaseQuality\ExternalValidation\CrossCheck\Result\ComparisonResult;
use WikibaseQuality\ExternalValidation\CrossCheck\Result\CrossCheckResult;
use WikibaseQuality\ExternalValidation\CrossCheck\Result\ReferenceResult;
use WikibaseQuality\ExternalValidation\DumpMetaInformation\DumpMetaInformation;

/**
 * @covers WikibaseQuality\ExternalValidation\CrossCheck\Result\CrossCheckResult
 *
 * @group WikibaseQualityExternalValidation
 *
 * @uses   WikibaseQuality\ExternalValidation\CrossCheck\Result\ComparisonResult
 * @uses   WikibaseQuality\ExternalValidation\CrossCheck\Result\ReferenceResult
 *
 * @author BP2014N1
 * @license GNU GPL v2+
 */
class CrossCheckResultTest extends PHPUnit_Framework_TestCase {

	public function testConstructValidArguments() {
		// Create test data
		$propertyId = new PropertyId( 'P42' );
		$guid = 'Q42$fccafc70-07a0-4e82-807f-288a4b21c13c';
		$externalId = 'foobar';
		$dumpMetaInformation = $this->getDumpMetaInformationMock();
		$comparisonResult = $this->getComparisonResultMock();
		$referenceResult = $this->getReferenceResultMock();

		// Create instance
		$crossCheckResult = new CrossCheckResult( $propertyId, $guid, $externalId, $dumpMetaInformation, $comparisonResult, $referenceResult );

		// Run assertions
		$this->assertEquals( $propertyId, $crossCheckResult->getPropertyId() );
		$this->assertEquals( $guid, $crossCheckResult->getClaimGuid() );
		$this->assertEquals( $externalId, $crossCheckResult->getExternalId() );
		$this->assertEquals( $dumpMetaInformation, $crossCheckResult->getDumpMetaInformation() );
		$this->assertEquals( $comparisonResult, $crossCheckResult->getComparisonResult() );
		$this->assertEquals( $referenceResult, $crossCheckResult->getReferenceResult() );
	}

	/**
	 * @dataProvider constructInvalidArgumentsDataProvider
	 */
	public function testConstructInvalidArguments( $propertyId, $guid, $externalId, $dumpMetaInformation, $comparisonResult, $referenceResult ) {
		$this->setExpectedException( InvalidArgumentException::class );

		new CrossCheckResult( $propertyId, $guid, $externalId, $dumpMetaInformation, $comparisonResult, $referenceResult );
	}

	/**
	 * Test cases for testConstructInvalidArguments
	 * @return array
	 */
	public function constructInvalidArgumentsDataProvider() {
		$dumpMetaInformation = $this->getDumpMetaInformationMock();
		$comparisonResult = $this->getComparisonResultMock( true );
		$referenceResult = $this->getReferenceResultMock( true );

		return array(
			array(
				new PropertyId( 'P42' ),
				42,
				'foobar',
				$dumpMetaInformation,
				$comparisonResult,
				$referenceResult
			),
			array(
				new PropertyId( 'P42' ),
				'foobar',
				42,
				$dumpMetaInformation,
				$comparisonResult,
				$referenceResult
			),
			array(
				new PropertyId( 'P42' ),
				42,
				42,
				$dumpMetaInformation,
				$comparisonResult,
				$referenceResult
			)
		);
	}

	/**
	 * @return DumpMetaInformation
	 */
	private function getDumpMetaInformationMock() {
		$mock = $this->getMockBuilder( DumpMetaInformation::class )
			->disableOriginalConstructor()
			->getMock();

		return $mock;
	}

	/**
	 * @param string $status
	 *
	 * @return ComparisonResult
	 */
	private function getComparisonResultMock( $status = ComparisonResult::STATUS_MISMATCH ) {
		$mock = $this
			->getMockBuilder( ComparisonResult::class )
			->disableOriginalConstructor()
			->getMock();
		$mock->expects( $this->any() )
			->method( 'getStatus' )
			->will( $this->returnValue( $status ) );

		return $mock;
	}

	/**
	 * @param bool $referencesMissing
	 *
	 * @return ReferenceResult
	 */
	private function getReferenceResultMock( $referencesMissing = true ) {
		$mock = $this
			->getMockBuilder( ReferenceResult::class )
			->disableOriginalConstructor()
			->getMock();
		$mock->expects( $this->any() )
			->method( 'areReferencesMissing' )
			->will( $this->returnValue( $referencesMissing ) );

		return $mock;
	}

}
