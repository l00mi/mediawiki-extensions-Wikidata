<?php

namespace WikibaseQuality\ExternalValidation\Tests\CrossCheck\Comparer;

use DataValues\MonolingualTextValue;
use DataValues\QuantityValue;
use DataValues\StringValue;
use Wikibase\DataModel\Entity\EntityIdValue;
use Wikibase\DataModel\Entity\ItemId;
use WikibaseQuality\ExternalValidation\CrossCheck\Comparer\StringValueComparer;
use WikibaseQuality\ExternalValidation\CrossCheck\Result\ComparisonResult;

/**
 * @covers WikibaseQuality\ExternalValidation\CrossCheck\Comparer\StringValueComparer
 *
 * @group WikibaseQualityExternalValidation
 *
 * @author BP2014N1
 * @license GNU GPL v2+
 */
class StringValueComparerTest extends DataValueComparerTestBase {

	public function comparableProvider() {
		return array (
			array (
				new StringValue( 'foobar' ),
				new StringValue( 'foobar' )
			)
		);
	}

	public function nonComparableProvider() {
		return array (
			array (
				new MonolingualTextValue( 'de', 'foobar' ),
				new StringValue( 'foobar' )
			),
			array (
				QuantityValue::newFromNumber( 42 ),
				new StringValue( 'foobar' )
			),
			array (
				new StringValue( 'foobar' ),
				new EntityIdValue( new ItemId( 'Q42' ) )
			),
			array (
				new StringValue( 'foobar' ),
				QuantityValue::newFromNumber( 42 )
			)
		);
	}

	public function comparisonProvider() {
		return array (
			// Correct formatted external value
			array (
				ComparisonResult::STATUS_MATCH,
				new StringValue( 'foo' ),
				new StringValue( 'foo' )
			),
			array (
				ComparisonResult::STATUS_MISMATCH,
				new StringValue( 'foo' ),
				new StringValue( 'bar' )
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

		return new StringValueComparer( $stringComparer );
	}

}
