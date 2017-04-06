<?php

namespace WikibaseQuality\ExternalValidation\Tests\CrossCheck\Comparer;

use DataValues\MonolingualTextValue;
use DataValues\QuantityValue;
use DataValues\StringValue;
use WikibaseQuality\ExternalValidation\CrossCheck\Comparer\QuantityValueComparer;
use WikibaseQuality\ExternalValidation\CrossCheck\Result\ComparisonResult;

/**
 * @covers \WikibaseQuality\ExternalValidation\CrossCheck\Comparer\QuantityValueComparer
 *
 * @group WikibaseQualityExternalValidation
 *
 * @author BP2014N1
 * @license GNU GPL v2+
 */
class QuantityValueComparerTest extends DataValueComparerTestBase {

	public function comparableProvider() {
		return array(
			array(
				QuantityValue::newFromNumber( 42 ),
				QuantityValue::newFromNumber( 42 )
			)
		);
	}

	public function nonComparableProvider() {
		return array(
			array(
				new StringValue( 'foobar' ),
				QuantityValue::newFromNumber( 42 )
			),
			array(
				new MonolingualTextValue( 'de', 'foobar' ),
				QuantityValue::newFromNumber( 42 )
			),
			array(
				QuantityValue::newFromNumber( 42 ),
				new StringValue( 'foobar' )
			),
			array(
				QuantityValue::newFromNumber( 42 ),
				new MonolingualTextValue( 'de', 'foobar' )
			)
		);
	}

	public function comparisonProvider() {
		return array(
			// Correct formatted external values
			array(
				ComparisonResult::STATUS_MATCH,
				QuantityValue::newFromNumber( 42, '1', 44, 40 ),
				QuantityValue::newFromNumber( 42, '1', 43, 41 )
			),
			array(
				ComparisonResult::STATUS_MATCH,
				QuantityValue::newFromNumber( 42, '1', 44, 40 ),
				QuantityValue::newFromNumber( 41, '1', 42, 40 )
			),
			array(
				ComparisonResult::STATUS_MISMATCH,
				QuantityValue::newFromNumber( 42, '1' ),
				QuantityValue::newFromNumber( 23, '1', 24, 22 )
			),
			array(
				ComparisonResult::STATUS_MATCH,
				QuantityValue::newFromNumber( 42, '1' ),
				QuantityValue::newFromNumber( 42, '1', 43, 41 )
			),
			array(
				ComparisonResult::STATUS_MISMATCH,
				QuantityValue::newFromNumber( 42, '1' ),
				QuantityValue::newFromNumber( 44, '1', 45, 43 )
			),
		);
	}

	protected function buildComparer() {
		return new QuantityValueComparer();
	}

}
