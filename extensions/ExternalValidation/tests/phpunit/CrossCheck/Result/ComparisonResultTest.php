<?php

namespace WikibaseQuality\ExternalValidation\Tests\CrossCheck\Result;

use DataValues\DataValue;
use InvalidArgumentException;
use PHPUnit_Framework_TestCase;
use WikibaseQuality\ExternalValidation\CrossCheck\Result\ComparisonResult;

/**
 * @covers \WikibaseQuality\ExternalValidation\CrossCheck\Result\ComparisonResult
 *
 * @group WikibaseQualityExternalValidation
 *
 * @author BP2014N1
 * @license GNU GPL v2+
 */
class ComparisonResultTest extends PHPUnit_Framework_TestCase {

	/**
	 * @dataProvider constructValidArgumentsDataProvider
	 */
	public function testConstructValidArguments( $localValue, $externalValues, $status ) {
		$comparisonResult = new ComparisonResult( $localValue, $externalValues, $status );

		$this->assertEquals( $localValue, $comparisonResult->getLocalValue() );
		$this->assertEquals( $externalValues, $comparisonResult->getExternalValues() );
		$this->assertEquals( $status, $comparisonResult->getStatus() );
	}

	/**
	 * Test cases for testConstructValidArguments
	 * @return array
	 */
	public function constructValidArgumentsDataProvider() {
		return array(
			array(
				$this->getDataValueMock(),
				array( $this->getDataValueMock() ),
				ComparisonResult::STATUS_MISMATCH
			),
			array(
				$this->getDataValueMock(),
				array( $this->getDataValueMock() ),
				ComparisonResult::STATUS_MATCH
			),
			array(
				$this->getDataValueMock(),
				array( $this->getDataValueMock() ),
				ComparisonResult::STATUS_PARTIAL_MATCH
			),
			array(
				$this->getDataValueMock(),
				array( $this->getDataValueMock(), $this->getDataValueMock() ),
				ComparisonResult::STATUS_MISMATCH
			)
		);
	}

	private function getDataValueMock() {
		return $this->getMock( DataValue::class );
	}

	/**
	 * @dataProvider constructInvalidArgumentsDataProvider
	 */
	public function testConstructInvalidArguments( $localValue, $externalValues, $status ) {
		$this->setExpectedException( InvalidArgumentException::class );

		new ComparisonResult( $localValue, $externalValues, $status );
	}

	/**
	 * Test cases for testConstructInvalidArguments
	 * @return array
	 */
	public function constructInvalidArgumentsDataProvider() {
		return array(
			array(
				$this->getDataValueMock(),
				array( $this->getDataValueMock() ),
				42
			),
			array(
				$this->getDataValueMock(),
				array( 42 ),
				ComparisonResult::STATUS_MISMATCH
			)
		);
	}

}
