<?php

namespace WikibaseQuality\ExternalValidation\Tests\CrossCheck\Comparer;

use DataValues\DataValue;
use DataValues\MonolingualTextValue;
use DataValues\MultilingualTextValue;
use DataValues\QuantityValue;
use DataValues\StringValue;
use DataValues\TimeValue;
use InvalidArgumentException;
use ValueParsers\QuantityParser;
use Wikibase\DataModel\Entity\EntityIdValue;
use Wikibase\DataModel\Entity\ItemId;
use WikibaseQuality\ExternalValidation\CrossCheck\Comparer\DataValueComparer;
use WikibaseQuality\ExternalValidation\CrossCheck\Comparer\DispatchingDataValueComparer;
use WikibaseQuality\ExternalValidation\CrossCheck\Comparer\QuantityValueComparer;

/**
 * @covers WikibaseQuality\ExternalValidation\CrossCheck\Comparer\DispatchingDataValueComparer
 *
 * @group WikibaseQualityExternalValidation
 *
 * @author BP2014N1
 * @license GNU GPL v2+
 */
class DispatchingDataValueComparerTest extends DataValueComparerTestBase {

	public function comparableProvider() {
		return array(
			array(
				new StringValue( 'foobar' ),
				new StringValue( 'foobar' )
			),
			array(
				new MonolingualTextValue( 'de', 'foobar' ),
				new MonolingualTextValue( 'de', 'foobar' )
			),
			array(
				QuantityValue::newFromNumber( 42 ),
				QuantityValue::newFromNumber( 42 )
			)
		);
	}

	public function nonComparableProvider() {
		return array(
			array(
				new EntityIdValue( new ItemId( 'Q1' ) ),
				new StringValue( 'foobar' )
			),
			array(
				new TimeValue( '+0000000000001955-03-11T00:00:00Z', 0, 0, 0, 11, 'http://www.wikidata.org/entity/Q1985727' ) ,
				new StringValue( 'foobar' )
			),
			array(
				new MultilingualTextValue(
					array( new MonolingualTextValue( 'de', 'foobar' ) )
				),
				new StringValue( 'foobar' )
			),
			array(
				new StringValue( 'foobar' ),
				new EntityIdValue( new ItemId( 'Q1' ) )
			),
			array(
				new StringValue( 'foobar' ),
				new TimeValue( '+0000000000001955-03-11T00:00:00Z', 0, 0, 0, 11, 'http://www.wikidata.org/entity/Q1985727' )
			),
			array(
				new StringValue( 'foobar' ),
				new MultilingualTextValue(
					array( new MonolingualTextValue( 'de', 'foobar' ) )
				)
			)
		);
	}

	public function comparisonProvider() {
		$stringValue = new StringValue( 'foobar' );
		$monolingualTextValue = new MonolingualTextValue( 'en', 'foobar' );
		$quantityValue = QuantityValue::newFromNumber( 42 );

		return array(
			array(
				true,
				$stringValue,
				$stringValue
			),
			array(
				true,
				$monolingualTextValue,
				$monolingualTextValue
			),
			array(
				false,
				$quantityValue,
				$quantityValue
			)
		);
	}

	protected function buildComparer() {
		return new DispatchingDataValueComparer(
			array(
				$this->mockDataValueComparer( 'string', true ),
				$this->mockDataValueComparer( 'monolingualtext', true ),
				$this->mockDataValueComparer( 'quantity', false )
			)
		);
	}

	/**
	 * @param string $acceptedType
	 * @param bool $comparisonResult
	 *
	 * @return DataValueComparer
	 */
	private function mockDataValueComparer( $acceptedType, $comparisonResult ) {
		$mock = $this->getMockBuilder( DataValueComparer::class )
					 ->setMethods( array( 'canCompare', 'compare' ) )
					 ->getMock();

		$mock->expects( $this->any() )
			 ->method( 'compare' )
			 ->will( $this->returnValue( $comparisonResult ) );
		$mock->expects( $this->any() )
			 ->method( 'canCompare' )
			 ->will( $this->returnCallback(
				 function ( DataValue $value, DataValue $comparativeValue ) use ( $acceptedType ) {
					 return $value->getType() === $acceptedType && $comparativeValue->getType() == $acceptedType;
				 }
			 ) );

		return $mock;
	}

	/**
	 * @dataProvider constructInvalidArgumentsDataProvider
	 */
	public function testConstructInvalidArguments( $dataValueComparer ) {
		$this->setExpectedException( InvalidArgumentException::class );
		new DispatchingDataValueComparer( $dataValueComparer );
	}

	/**
	 * Test cases for testConstructInvalidArguments
	 * @return array
	 */
	public function constructInvalidArgumentsDataProvider() {
		return array(
			array(
				array(
					new QuantityValueComparer(),
					42
				)
			),
			array(
				array(
					new QuantityValueComparer(),
					new QuantityParser()
				)
			)
		);
	}

}
