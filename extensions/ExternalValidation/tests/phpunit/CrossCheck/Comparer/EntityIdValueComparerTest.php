<?php

namespace WikibaseQuality\ExternalValidation\Tests\CrossCheck\Comparer;

use DataValues\MonolingualTextValue;
use DataValues\QuantityValue;
use DataValues\StringValue;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\EntityIdValue;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\TermIndexEntry;
use WikibaseQuality\ExternalValidation\CrossCheck\Comparer\EntityIdValueComparer;
use WikibaseQuality\ExternalValidation\CrossCheck\Result\ComparisonResult;

/**
 * @covers WikibaseQuality\ExternalValidation\CrossCheck\Comparer\EntityIdValueComparer
 *
 * @group WikibaseQualityExternalValidation
 *
 * @author BP2014N1
 * @license GNU GPL v2+
 */
class EntityIdValueComparerTest extends DataValueComparerTestBase {

	public function comparableProvider() {
		return array(
			array(
				new EntityIdValue( new ItemId( 'Q1' ) ),
				new MonolingualTextValue( 'en', 'foobar' )
			)
		);
	}

	public function nonComparableProvider() {
		return array(
			array(
				new StringValue( 'foobar' ),
				new MonolingualTextValue( 'en', 'foobar' )
			),
			array(
				new MonolingualTextValue( 'de', 'foobar' ),
				new MonolingualTextValue( 'en', 'foobar' )
			),
			array(
				QuantityValue::newFromNumber( 42 ),
				new MonolingualTextValue( 'en', 'foobar' )
			),
			array(
				new EntityIdValue( new ItemId( 'Q42' ) ),
				new StringValue( 'foobar' )
			),
			array(
				new EntityIdValue( new ItemId( 'Q42' ) ),
				QuantityValue::newFromNumber( 42 )
			)
		);
	}

	public function comparisonProvider() {
		$valueQ1 = new EntityIdValue( new ItemId( 'Q1' ) );
		$valueQ2 = new EntityIdValue( new ItemId( 'Q2' ) );

		return array(
			// Languages match
			array(
				ComparisonResult::STATUS_MATCH,
				$valueQ1,
				new MonolingualTextValue( 'en', 'foo' )
			),
			array(
				ComparisonResult::STATUS_MISMATCH,
				$valueQ1,
				new MonolingualTextValue( 'en', 'baz' )
			),
			array(
				ComparisonResult::STATUS_MATCH,
				$valueQ1,
				new MonolingualTextValue( 'de', 'Fubar' )
			),
			// Languages do not match
			array(
				null,
				$valueQ1,
				new MonolingualTextValue( 'es', 'foo' )
			),
			// Entity does not exist
			array(
				null,
				$valueQ2,
				new MonolingualTextValue( 'en', 'foo' )
			)
		);
	}

	protected function buildComparer() {
		$termIndex = $this->getMockForAbstractClass( 'Wikibase\TermIndex' );
		$termIndex->expects( $this->any() )
			->method( 'getTermsOfEntity' )
			->will( $this->returnCallback(
				function( EntityId $id, $termTypes, $languageCodes ) {
					$terms = array();
					if ( $id->getSerialization() === 'Q1' ) {
						if ( in_array( TermIndexEntry::TYPE_LABEL, $termTypes ) ) {
							if ( in_array( 'en', $languageCodes) ) {
								$terms[] = new TermIndexEntry( array( 'termText' => 'foobar' ) );
							}
							if ( in_array( 'de', $languageCodes) ) {
								$terms[] = new TermIndexEntry( array( 'termText' => 'Fubar' ) );
							}
						}
						if ( in_array( TermIndexEntry::TYPE_ALIAS, $termTypes ) ) {
							if ( in_array( 'en', $languageCodes) ) {
								$terms[] = new TermIndexEntry( array( 'termText' => 'foo' ) );
								$terms[] = new TermIndexEntry( array( 'termText' => 'bar' ) );
							}
							if ( in_array( 'de', $languageCodes) ) {
								$terms[] = new TermIndexEntry( array( 'termText' => 'foobar' ) );
							}
						}
					}

					return $terms;
			}
		) );

		$stringComparer = $this->getMockBuilder( 'WikibaseQuality\ExternalValidation\CrossCheck\Comparer\StringComparer' )
			->disableOriginalConstructor()
			->setMethods( array( 'compareWithArray' ) )
			->getMock();
		$stringComparer->expects( $this->any() )
						->method( 'compareWithArray' )
						->will( $this->returnCallback(
							function ( $value, array $values ) {
								if ( in_array(  $value, $values ) ) {
									return ComparisonResult::STATUS_MATCH;
								} else {
									return ComparisonResult::STATUS_MISMATCH;
							   }
							}
						) );

		return new EntityIdValueComparer( $termIndex, $stringComparer );
	}

}
