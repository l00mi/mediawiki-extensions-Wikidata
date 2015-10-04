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
		$g = TimeValue::CALENDAR_GREGORIAN;

		return array(
			array(
				new TimeValue( '+1955-03-11T00:00:00Z', 0, 0, 0, TimeValue::PRECISION_DAY, $g ),
				new TimeValue( '+1955-03-11T00:00:00Z', 0, 0, 0, TimeValue::PRECISION_DAY, $g )
			)
		);
	}

	public function nonComparableProvider() {
		$g = TimeValue::CALENDAR_GREGORIAN;

		return array(
			array(
				new MonolingualTextValue( 'de', 'foobar' ),
				new TimeValue( '+1955-03-11T00:00:00Z', 0, 0, 0, TimeValue::PRECISION_DAY, $g )
			),
			array(
				QuantityValue::newFromNumber( 42 ),
				new TimeValue( '+1955-03-11T00:00:00Z', 0, 0, 0, TimeValue::PRECISION_DAY, $g )
			),
			array(
				new TimeValue( '+1955-03-11T00:00:00Z', 0, 0, 0, TimeValue::PRECISION_DAY, $g ),
				new StringValue( 'foobar' )
			),
			array(
				new TimeValue( '+1955-03-11T00:00:00Z', 0, 0, 0, TimeValue::PRECISION_DAY, $g ),
				QuantityValue::newFromNumber( 42 )
			)
		);
	}

	public function comparisonProvider() {
		$g = TimeValue::CALENDAR_GREGORIAN;
		$localValue1955 = new TimeValue( '+1955-03-11T00:00:00Z', 0, 0, 0, TimeValue::PRECISION_DAY, $g );
		$localValue2015 = new TimeValue( '+2015-00-00T00:00:00Z', 0, 0, 0, TimeValue::PRECISION_YEAR, $g );
		$localValue2016 = new TimeValue( '+2016-03-00T00:00:00Z', 0, 0, 0, TimeValue::PRECISION_MONTH, $g );

		return array(
			'Same second' => array(
				ComparisonResult::STATUS_MATCH,
				new TimeValue( '+2015-01-01T01:01:01Z', 0, 0, 0, TimeValue::PRECISION_SECOND, $g ),
				new TimeValue( '+2015-01-01T01:01:01Z', 0, 0, 0, TimeValue::PRECISION_SECOND, $g )
			),
			'Same day' => array(
				ComparisonResult::STATUS_MATCH,
				$localValue1955,
				new TimeValue( '+1955-03-11T00:00:00Z', 0, 0, 0, TimeValue::PRECISION_DAY, $g )
			),
			'Other day' => array(
				ComparisonResult::STATUS_MISMATCH,
				$localValue1955,
				new TimeValue( '+1991-05-23T00:00:00Z', 0, 0, 0, TimeValue::PRECISION_DAY, $g )
			),
			'Same year' => array(
				ComparisonResult::STATUS_MATCH,
				$localValue2015,
				new TimeValue( '+2015-00-00T00:00:00Z', 0, 0, 0, TimeValue::PRECISION_YEAR, $g )
			),
			'Partial year vs. month match' => array(
				ComparisonResult::STATUS_PARTIAL_MATCH,
				$localValue2015,
				new TimeValue( '+2015-03-00T00:00:00Z', 0, 0, 0, TimeValue::PRECISION_MONTH, $g )
			),
			'Partial month vs. year match' => array(
				ComparisonResult::STATUS_PARTIAL_MATCH,
				$localValue2016,
				new TimeValue( '+2016-00-00T00:00:00Z', 0, 0, 0, TimeValue::PRECISION_YEAR, $g )
			),
			'Other year' => array(
				ComparisonResult::STATUS_MISMATCH,
				new TimeValue( '+11980-08-00T00:00:00Z', 0, 0, 0, TimeValue::PRECISION_YEAR, $g ),
				new TimeValue( '+1980-00-00T00:00:00Z', 0, 0, 0, TimeValue::PRECISION_YEAR, $g )
			),
			'Other gigayear' => array(
				ComparisonResult::STATUS_MISMATCH,
				new TimeValue( '+1000000000-00-00T00:00:00Z', 0, 0, 0, TimeValue::PRECISION_YEAR1G, $g ),
				new TimeValue( '+2000000000-00-00T00:00:00Z', 0, 0, 0, TimeValue::PRECISION_YEAR1G, $g )
			),
			'Other decade with year precision' => array(
				ComparisonResult::STATUS_MISMATCH,
				new TimeValue( '+2010-00-00T00:00:00Z', 0, 0, 0, TimeValue::PRECISION_YEAR, $g ),
				new TimeValue( '+2020-00-00T00:00:00Z', 0, 0, 0, TimeValue::PRECISION_YEAR, $g )
			),
			'Other year with month precision' => array(
				ComparisonResult::STATUS_MISMATCH,
				new TimeValue( '+2001-01-00T00:00:00Z', 0, 0, 0, TimeValue::PRECISION_MONTH, $g ),
				new TimeValue( '+2002-01-00T00:00:00Z', 0, 0, 0, TimeValue::PRECISION_MONTH, $g )
			),
			'Other month with day precision' => array(
				ComparisonResult::STATUS_MISMATCH,
				new TimeValue( '+2001-01-01T00:00:00Z', 0, 0, 0, TimeValue::PRECISION_DAY, $g ),
				new TimeValue( '+2001-02-01T00:00:00Z', 0, 0, 0, TimeValue::PRECISION_DAY, $g )
			),
			'Other day with hour precision' => array(
				ComparisonResult::STATUS_MISMATCH,
				new TimeValue( '+2001-01-01T01:00:00Z', 0, 0, 0, TimeValue::PRECISION_HOUR, $g ),
				new TimeValue( '+2001-01-02T01:00:00Z', 0, 0, 0, TimeValue::PRECISION_HOUR, $g )
			),
			'Other hour with minute precision' => array(
				ComparisonResult::STATUS_MISMATCH,
				new TimeValue( '+2001-01-01T01:01:00Z', 0, 0, 0, TimeValue::PRECISION_MINUTE, $g ),
				new TimeValue( '+2001-01-01T02:01:00Z', 0, 0, 0, TimeValue::PRECISION_MINUTE, $g )
			),
			'Other minute with second precision' => array(
				ComparisonResult::STATUS_MISMATCH,
				new TimeValue( '+2001-01-01T01:01:01Z', 0, 0, 0, TimeValue::PRECISION_SECOND, $g ),
				new TimeValue( '+2001-01-01T01:02:01Z', 0, 0, 0, TimeValue::PRECISION_SECOND, $g )
			),
			// FIXME: This is a bug!
			'Can not compare 5+ digit years' => array(
				ComparisonResult::STATUS_MISMATCH,
				new TimeValue( '+12345-00-00T00:00:00Z', 0, 0, 0, TimeValue::PRECISION_YEAR, $g ),
				new TimeValue( '+12345-00-00T00:00:00Z', 0, 0, 0, TimeValue::PRECISION_YEAR, $g )
			),
		);
	}

	protected function buildComparer() {
		return new TimeValueComparer();
	}

}
