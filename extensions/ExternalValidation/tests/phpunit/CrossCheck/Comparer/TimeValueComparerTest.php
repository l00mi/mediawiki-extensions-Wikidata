<?php

namespace WikibaseQuality\ExternalValidation\Tests\CrossCheck\Comparer;

use DataValues\MonolingualTextValue;
use DataValues\QuantityValue;
use DataValues\StringValue;
use DataValues\TimeValue;
use WikibaseQuality\ExternalValidation\CrossCheck\Comparer\TimeValueComparer;
use WikibaseQuality\ExternalValidation\CrossCheck\Result\ComparisonResult;

/**
 * @covers  WikibaseQuality\ExternalValidation\CrossCheck\Comparer\TimeValueComparer
 *
 * @group   WikibaseQualityExternalValidation
 *
 * @author  BP2014N1
 * @license GNU GPL v2+
 */
class TimeValueComparerTest extends DataValueComparerTestBase {

	public function comparableProvider() {
		return array(
			array(
				new TimeValue( '+0000000000001955-03-11T00:00:00Z', 0, 0, 0, 11, 'http://www.wikidata.org/entity/Q1985727' ),
				new TimeValue( '+0000000000001955-03-11T00:00:00Z', 0, 0, 0, 11, 'http://www.wikidata.org/entity/Q1985727' )
			)
		);
	}

	public function nonComparableProvider() {
		return array(
			array(
				new MonolingualTextValue( 'de', 'foobar' ),
				new TimeValue( '+0000000000001955-03-11T00:00:00Z', 0, 0, 0, 11, 'http://www.wikidata.org/entity/Q1985727' )
			),
			array(
				QuantityValue::newFromNumber( 42 ),
				new TimeValue( '+0000000000001955-03-11T00:00:00Z', 0, 0, 0, 11, 'http://www.wikidata.org/entity/Q1985727' )
			),
			array(
				new TimeValue( '+0000000000001955-03-11T00:00:00Z', 0, 0, 0, 11, 'http://www.wikidata.org/entity/Q1985727' ),
				new StringValue( 'foobar' )
			),
			array(
				new TimeValue( '+0000000000001955-03-11T00:00:00Z', 0, 0, 0, 11, 'http://www.wikidata.org/entity/Q1985727' ),
				QuantityValue::newFromNumber( 42 )
			)
		);
	}

	public function comparisonProvider() {
		$localValue1955 = new TimeValue( '+0000000000001955-03-11T00:00:00Z', 0, 0, 0, 11, 'http://www.wikidata.org/entity/Q1985727' );
		$localValue2015 = new TimeValue( '+0000000000002015-00-00T00:00:00Z', 0, 0, 0, 9, 'http://www.wikidata.org/entity/Q1985727' );
		$localValue2016 = new TimeValue( '+0000000000002016-03-00T00:00:00Z', 0, 0, 0, 10, 'http://www.wikidata.org/entity/Q1985727' );

		return array(
			// Matches
			array(
				ComparisonResult::STATUS_MATCH,
				$localValue1955,
				new TimeValue( '+0000000000001955-03-11T00:00:00Z', 0, 0, 0, 11, 'http://www.wikidata.org/entity/Q1985727' )
			),
			array(
				ComparisonResult::STATUS_MISMATCH,
				$localValue1955,
				new TimeValue( '+0000000000001991-05-23T00:00:00Z', 0, 0, 0, 11, 'http://www.wikidata.org/entity/Q1985727' )
			),
			array(
				ComparisonResult::STATUS_MATCH,
				$localValue2015,
				new TimeValue( '+0000000000002015-00-00T00:00:00Z', 0, 0, 0, 9, 'http://www.wikidata.org/entity/Q1985727' )
			),
			// Partial matches
			array(
				ComparisonResult::STATUS_PARTIAL_MATCH,
				$localValue2015,
				new TimeValue( '+0000000000002015-03-00T00:00:00Z', 0, 0, 0, 10, 'http://www.wikidata.org/entity/Q1985727' )
			),
			array(
				ComparisonResult::STATUS_PARTIAL_MATCH,
				$localValue2016,
				new TimeValue( '+0000000000002016-00-00T00:00:00Z', 0, 0, 0, 9, 'http://www.wikidata.org/entity/Q1985727' )
			),
			array(
				ComparisonResult::STATUS_MISMATCH,
				new TimeValue( '+0000000000011980-08-00T00:00:00Z', 0, 0, 0, 9, 'http://www.wikidata.org/entity/Q1985727' ),
				new TimeValue( '+0000000000001980-00-00T00:00:00Z', 0, 0, 0, 9, 'http://www.wikidata.org/entity/Q1985727' )
			)
		);
	}

	protected function buildComparer() {
		return new TimeValueComparer();
	}

}
