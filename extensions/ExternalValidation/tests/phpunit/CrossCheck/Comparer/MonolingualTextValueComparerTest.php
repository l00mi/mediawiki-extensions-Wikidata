<?php

namespace WikibaseQuality\ExternalValidation\Tests\CrossCheck\Comparer;

use DataValues\MonolingualTextValue;
use DataValues\QuantityValue;
use DataValues\StringValue;
use WikibaseQuality\ExternalValidation\CrossCheck\Comparer\MonolingualTextValueComparer;
use WikibaseQuality\ExternalValidation\CrossCheck\Result\ComparisonResult;

/**
 * @covers WikibaseQuality\ExternalValidation\CrossCheck\Comparer\MonolingualTextValueComparer
 *
 * @group WikibaseQualityExternalValidation
 *
 * @author BP2014N1
 * @license GNU GPL v2+
 */
class MonolingualTextValueComparerTest extends DataValueComparerTestBase {

	public function comparableProvider() {
		return array (
			array (
				new MonolingualTextValue( 'de', 'foobar' ),
				new MonolingualTextValue( 'de', 'foobar' )
			)
		);
	}

	public function nonComparableProvider() {
		return array (
			array (
				new StringValue( 'foobar' ),
				new MonolingualTextValue( 'de', 'foobar' )
			),
			array (
				QuantityValue::newFromNumber( 42 ),
				new MonolingualTextValue( 'en', 'foobar' )
			),
			array (
				new MonolingualTextValue( 'de', 'foobar' ),
				new StringValue( 'foobar' )
			),
			array (
				new MonolingualTextValue( 'de', 'foobar' ),
				QuantityValue::newFromNumber( 42 )
			)
		);
	}

	public function comparisonProvider() {
		return array (
			// Languages match
			array (
				ComparisonResult::STATUS_MATCH,
				new MonolingualTextValue( 'en', 'foo' ),
				new MonolingualTextValue( 'en', 'foo' )
			),
			array (
				ComparisonResult::STATUS_MISMATCH,
				new MonolingualTextValue( 'en', 'foobar' ),
				new MonolingualTextValue( 'en', 'fubar' )
			),
			// Languages do not match
			array (
				null,
				new MonolingualTextValue( 'en', 'foo' ),
				new MonolingualTextValue( 'de', 'foo' )
			)
		);
	}

	protected function buildComparer() {
		$stringComparer = $this->getMockBuilder( 'WikibaseQuality\ExternalValidation\CrossCheck\Comparer\StringComparer' )
			->disableOriginalConstructor()
			->setMethods( array ( 'compare' ) )
			->getMock();
		$stringComparer->expects( $this->any() )
						->method( 'compare' )
						->will( $this->returnCallback(
									function ( $value1, $value2 ) {
										if ( $value1 === $value2 ) {
											return ComparisonResult::STATUS_MATCH;
										} else {
											return ComparisonResult::STATUS_MISMATCH;
										}
									}
								) );

		return new MonolingualTextValueComparer( $stringComparer );
	}

}
