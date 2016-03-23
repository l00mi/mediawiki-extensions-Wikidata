<?php

namespace WikibaseQuality\ExternalValidation\Tests\CrossCheck\Comparer;

use DataValues\MonolingualTextValue;
use DataValues\MultilingualTextValue;
use DataValues\QuantityValue;
use DataValues\StringValue;
use WikibaseQuality\ExternalValidation\CrossCheck\Comparer\MultilingualTextValueComparer;
use WikibaseQuality\ExternalValidation\CrossCheck\Comparer\StringComparer;
use WikibaseQuality\ExternalValidation\CrossCheck\Result\ComparisonResult;

/**
 * @covers WikibaseQuality\ExternalValidation\CrossCheck\Comparer\MultilingualTextValueComparer
 *
 * @group WikibaseQualityExternalValidation
 *
 * @author BP2014N1
 * @license GNU GPL v2+
 */
class MultilingualTextValueComparerTest extends DataValueComparerTestBase {

	public function comparableProvider() {
		return array (
			array (
				new MultilingualTextValue(
					array ( new MonolingualTextValue( 'de', 'foobar' ) )
				),
				new MultilingualTextValue(
					array ( new MonolingualTextValue( 'de', 'foobar' ) )
				)
			)
		);
	}

	public function nonComparableProvider() {
		return array (
			array (
				new StringValue( 'foobar' ),
				new MultilingualTextValue(
					array ( new MonolingualTextValue( 'de', 'foobar' ) )
				)
			),
			array (
				new MonolingualTextValue( 'de', 'foobar' ),
				new MultilingualTextValue(
					array ( new MonolingualTextValue( 'de', 'foobar' ) )
				)
			),
			array (
				QuantityValue::newFromNumber( 42 ),
				new MultilingualTextValue(
					array ( new MonolingualTextValue( 'de', 'foobar' ) )
				)
			),
			array (
				new MultilingualTextValue(
					array ( new MonolingualTextValue( 'de', 'foobar' ) )
				),
				new StringValue( 'foobar' )
			),
			array (
				new MultilingualTextValue(
					array ( new MonolingualTextValue( 'de', 'foobar' ) )
				),
				new MonolingualTextValue( 'de', 'foobar' )
			),
			array (
				new MultilingualTextValue(
					array ( new MonolingualTextValue( 'de', 'foobar' ) )
				),
				QuantityValue::newFromNumber( 42 )
			)
		);
	}

	public function comparisonProvider() {
		$localValueEn = new MultilingualTextValue( array ( new MonolingualTextValue( 'en', 'foo' ) ) );
		$localValueDe = new MultilingualTextValue( array ( new MonolingualTextValue( 'de', 'foo' ) ) );

		return array (
			// Languages match
			array (
				ComparisonResult::STATUS_MATCH,
				$localValueEn,
				new MultilingualTextValue( array ( new MonolingualTextValue( 'en', 'foo' ) ) )
			),
			array (
				ComparisonResult::STATUS_MATCH,
				$localValueEn,
				new MultilingualTextValue( array (
											   new MonolingualTextValue( 'de', 'bar' ),
											   new MonolingualTextValue( 'en', 'foo' )
										   ) )
			),
			array (
				ComparisonResult::STATUS_MISMATCH,
				$localValueEn,
				new MultilingualTextValue( array (
											   new MonolingualTextValue( 'de', 'foo' ),
											   new MonolingualTextValue( 'en', 'bar' )
										   ) )
			),
			array (
				ComparisonResult::STATUS_MISMATCH,
				$localValueEn,
				new MultilingualTextValue( array (
											   new MonolingualTextValue( 'de', 'foo' ),
											   new MonolingualTextValue( 'en', 'bar' )
										   ) )
			),
			array (
				ComparisonResult::STATUS_MISMATCH,
				new MultilingualTextValue( array (
											   new MonolingualTextValue( 'de', 'foobar' ),
											   new MonolingualTextValue( 'en', 'foobar' )
										   ) ),
				new MultilingualTextValue( array (
											   new MonolingualTextValue( 'de', 'foo' ),
											   new MonolingualTextValue( 'en', 'foo' )
										   ) )
			),
			// Languages does not match
			array (
				null,
				$localValueDe,
				new MultilingualTextValue( array ( new MonolingualTextValue( 'en', 'foo' ) ) )
			)
		);
	}

	protected function buildComparer() {
		$stringComparer = $this->getMockBuilder( StringComparer::class )
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

		return new MultilingualTextValueComparer( $stringComparer );
	}

}
