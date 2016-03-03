<?php

namespace WikibaseQuality\ExternalValidation\Tests\CrossCheck\Result;

use PHPUnit_Framework_TestCase;
use WikibaseQuality\ExternalValidation\CrossCheck\Result\ReferenceResult;

/**
 * @covers WikibaseQuality\ExternalValidation\CrossCheck\Result\ReferenceResult
 *
 * @group WikibaseQualityExternalValidation
 *
 * @author BP2014N1
 * @license GNU GPL v2+
 */
class ReferenceResultTest extends PHPUnit_Framework_TestCase {

	/**
	 * @dataProvider constructValidArgumentsDataProvider
	 */
	public function testConstructValidArguments( $status, $reference ) {
		$referenceResult = new ReferenceResult( $status, $reference );

		$this->assertEquals( $status, $referenceResult->getStatus() );
		$this->assertEquals( $reference, $referenceResult->getReference() );
	}

	/**
	 * Test cases for testConstructValidArguments
	 * @return array
	 */
	public function constructValidArgumentsDataProvider() {
		return array(
			array(
				ReferenceResult::STATUS_REFERENCES_STATED,
				$this->getReferenceMock()
			),
			array(
				ReferenceResult::STATUS_REFERENCES_MISSING,
				$this->getReferenceMock()
			)
		);
	}

	private function getReferenceMock() {
		return $this->getMock( 'Wikibase\DataModel\Reference' );
	}

	/**
	 * @dataProvider constructInvalidArgumentsDataProvider
	 */
	public function testConstructInvalidArguments( $status, $addableReference ) {
		$this->setExpectedException( 'InvalidArgumentException' );

		new ReferenceResult( $status, $addableReference );
	}

	/**
	 * Test cases for testConstructInvalidArguments
	 * @return array
	 */
	public function constructInvalidArgumentsDataProvider() {
		return array(
			array(
				42,
				$this->getReferenceMock()
			),
			array(
				'foo',
				$this->getReferenceMock()
			)
		);
	}

}
