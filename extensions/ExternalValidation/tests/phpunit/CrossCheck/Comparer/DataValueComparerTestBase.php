<?php

namespace WikibaseQuality\ExternalValidation\Tests\CrossCheck\Comparer;

use DataValues\DataValue;
use InvalidArgumentException;
use WikibaseQuality\ExternalValidation\CrossCheck\Comparer\DataValueComparer;

/**
 * @group WikibaseQualityExternalValidation
 *
 * @author BP2014N1
 * @license GNU GPL v2+
 */
abstract class DataValueComparerTestBase extends \MediaWikiTestCase {

	/**
	 * @return DataValueComparer
	 */
	protected abstract function buildComparer();

	public function testImplementsDataValueComparerInterface() {
		$this->assertInstanceOf( DataValueComparer::class, $this->buildComparer() );
	}

	/**
	 * @dataProvider comparableProvider
	 */
	public function testCanCompareReturnsTrue( DataValue $dataValue, DataValue $comparativeValue ) {
		$comparer = $this->buildComparer();
		$this->assertTrue( $comparer->canCompare( $dataValue, $comparativeValue ) );
	}

	public abstract function comparableProvider();

	/**
	 * @dataProvider nonComparableProvider
	 */
	public function testCanCompareReturnsFalse( DataValue $dataValue, DataValue $comparativeValue ) {
		$comparer = $this->buildComparer();
		$this->assertFalse( $comparer->canCompare( $dataValue, $comparativeValue ) );
	}

	public abstract function nonComparableProvider();

	/**
	 * @dataProvider nonComparableProvider
	 */
	public function testComparerThrowsInvalidArgumentException( DataValue $value, DataValue $comparativeValue ) {
		$this->setExpectedException( InvalidArgumentException::class );
		$this->buildComparer()->compare( $value, $comparativeValue );
	}

	/**
	 * @dataProvider comparisonProvider
	 */
	public function testComparison( $expectedResult, $value, $comparativeValue ) {

		$actualResult = $this->buildComparer()->compare( $value, $comparativeValue );

		$this->assertEquals( $expectedResult, $actualResult );
	}

	public abstract function comparisonProvider();

}
