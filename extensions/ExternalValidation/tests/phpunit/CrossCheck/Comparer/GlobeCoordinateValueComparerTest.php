<?php

namespace WikibaseQuality\ExternalValidation\Tests\CrossCheck\Comparer;

use DataValues\Geo\Values\GlobeCoordinateValue;
use DataValues\Geo\Values\LatLongValue;
use DataValues\MonolingualTextValue;
use DataValues\QuantityValue;
use DataValues\StringValue;
use WikibaseQuality\ExternalValidation\CrossCheck\Comparer\GlobeCoordinateValueComparer;
use WikibaseQuality\ExternalValidation\CrossCheck\Result\ComparisonResult;

/**
 * @covers WikibaseQuality\ExternalValidation\CrossCheck\Comparer\GlobeCoordinateValueComparer
 *
 * @group WikibaseQualityExternalValidation
 *
 * @author BP2014N1
 * @license GNU GPL v2+
 */
class GlobeCoordinateValueComparerTest extends DataValueComparerTestBase {

	public function comparableProvider() {
		return array (
			array (
				new GlobeCoordinateValue( new LatLongValue( 42, 42 ), 0.1 ),
				new GlobeCoordinateValue( new LatLongValue( 42, 42 ), 0.1 )
			)
		);
	}

	public function nonComparableProvider() {
		return array (
			array (
				new StringValue( 'foobar' ),
				new GlobeCoordinateValue( new LatLongValue( 42, 42 ), 0.1 )
			),
			array (
				new MonolingualTextValue( 'de', 'foobar' ),
				new GlobeCoordinateValue( new LatLongValue( 42, 42 ), 0.1 )
			),
			array (
				QuantityValue::newFromNumber( 42 ),
				new MonolingualTextValue( 'en', 'foobar' )
			),
			array (
				new GlobeCoordinateValue( new LatLongValue( 42, 42 ), 0.1 ),
				new StringValue( 'foobar' )
			),
			array (
				new GlobeCoordinateValue( new LatLongValue( 42, 42 ), 0.1 ),
				QuantityValue::newFromNumber( 42 )
			)
		);
	}

	public function comparisonProvider() {
		$localValue = new GlobeCoordinateValue( new LatLongValue( 64, 26 ), 1 );
		$localPrecisionValue = new GlobeCoordinateValue( new LatLongValue( 42, 32 ), 0.1 );

		return array (
			// Correct formatted external data
			array (
				ComparisonResult::STATUS_MATCH,
				$localValue,
				new GlobeCoordinateValue( new LatLongValue( 64, 26 ), 1 )
			),
			array (
				ComparisonResult::STATUS_MATCH,
				$localValue,
				new GlobeCoordinateValue( new LatLongValue( 64, 26 ), 1 )
			),
			array (
				ComparisonResult::STATUS_MISMATCH,
				$localValue,
				new GlobeCoordinateValue( new LatLongValue( 42, 32 ), 1 )
			),
			// Match with precision
			array (
				ComparisonResult::STATUS_MATCH,
				$localPrecisionValue,
				new GlobeCoordinateValue( new LatLongValue( 42.09, 31.91 ), 0.01 )
			),
			// Partial match with pi * daumen
			array (
				ComparisonResult::STATUS_PARTIAL_MATCH,
				$localPrecisionValue,
				new GlobeCoordinateValue( new LatLongValue( 42.3, 31.7 ), 0.1 )
			)
		);
	}

	protected function buildComparer() {
		return new GlobeCoordinateValueComparer();
	}

}
