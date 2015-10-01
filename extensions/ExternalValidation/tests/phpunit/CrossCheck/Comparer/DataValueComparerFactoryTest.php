<?php

namespace WikibaseQuality\ExternalValidation\Tests\CrossCheck\Comparer;

use DataValues\DataValue;
use DataValues\Geo\Values\GlobeCoordinateValue;
use DataValues\LatLongValue;
use DataValues\MonolingualTextValue;
use DataValues\MultilingualTextValue;
use DataValues\QuantityValue;
use DataValues\StringValue;
use DataValues\TimeValue;
use Wikibase\DataModel\Entity\EntityIdValue;
use Wikibase\DataModel\Entity\ItemId;
use WikibaseQuality\ExternalValidation\CrossCheck\Comparer\DataValueComparer;
use WikibaseQuality\ExternalValidation\CrossCheck\Comparer\DataValueComparerFactory;

/**
 * @covers WikibaseQuality\ExternalValidation\CrossCheck\Comparer\DataValueComparerFactory
 *
 * @group WikibaseQualityExternalValidation
 *
 * @author BP2014N1
 * @license GNU GPL v2+
 */
class DataValueComparerFactoryTest extends \MediaWikiTestCase {

	// Test objects
	private $entityIdValue;
	private $globeCoordinateValue;
	private $monolingualTextValue;
	private $multilingualTextValue;
	private $quantityValue;
	private $stringValue;
	private $timeValue;

	public function setUp() {
		parent::setUp();

		$this->entityIdValue = new EntityIdValue( new ItemId( 'Q1' ) );
		$this->globeCoordinateValue = new GlobeCoordinateValue(
			new LatLongValue( 42, 42 ),
			0.01
		);
		$this->monolingualTextValue = new MonolingualTextValue( 'en', 'foobar' );
		$this->multilingualTextValue = new MultilingualTextValue(
			array( $this->monolingualTextValue )
		);
		$this->quantityValue = QuantityValue::newFromNumber( 42 );
		$this->stringValue = new StringValue( 'foobar' );
		$this->timeValue = new TimeValue( '+0000000000001955-03-11T00:00:00Z', 0, 0, 0, 11, 'http://www.wikidata.org/entity/Q1985727' );
	}

	public function tearDown() {
		unset(
			$this->entityIdValue,
			$this->globeCoordinateValue,
			$this->monolingualTextValue,
			$this->multilingualTextValue,
			$this->quantityValue,
			$this->stringValue,
			$this->timeValue
		);

		parent::tearDown();
	}

	private function buildDataValueComparerFactory() {
		$termIndex = $this->getMockForAbstractClass( 'Wikibase\TermIndex' );
		$termIndex->expects( $this->any() )
			->method( 'getTermsOfEntity' )
			->will( $this->returnValue( array() ) );

		$stringNormalizer = $this->getMock( 'Wikibase\StringNormalizer' );

		return new DataValueComparerFactory( $termIndex, $stringNormalizer );
	}

	private function assertComparesWithoutException( DataValueComparer $comparer, DataValue $value, DataValue $comparativeValue ) {
		$comparer->compare(
			$value,
			$comparativeValue
		);

		$this->assertTrue( true, 'No exception occurred during comparison.' );
	}

	public function testNewEntityIdValueComparer() {
		$this->assertEntityIdValueComparesWithoutException(
			$this->buildDataValueComparerFactory()->newEntityIdValueComparer()
		);
	}

	private function assertEntityIdValueComparesWithoutException( DataValueComparer $dataValueComparer ) {
		$this->assertComparesWithoutException(
			$dataValueComparer,
			$this->entityIdValue,
			$this->monolingualTextValue
		);
	}

	public function testNewGlobeCoordinateValueComparer() {
		$this->assertGlobeCoordinateValueComparesWithoutException(
			$this->buildDataValueComparerFactory()->newGlobeCoordinateValueComparer()
		);
	}

	private function assertGlobeCoordinateValueComparesWithoutException( DataValueComparer $dataValueComparer ) {
		$this->assertComparesWithoutException(
			$dataValueComparer,
			$this->globeCoordinateValue,
			$this->globeCoordinateValue
		);
	}

	public function testNewMonolingualTextValueComparer() {
		$this->assertMonolingualTextValueComparesWithoutException(
			$this->buildDataValueComparerFactory()->newMonolingualTextValueComparer()
		);
	}

	private function assertMonolingualTextValueComparesWithoutException( DataValueComparer $dataValueComparer ) {
		$this->assertComparesWithoutException(
			$dataValueComparer,
			$this->monolingualTextValue,
			$this->monolingualTextValue
		);
	}

	public function testNewMultilingualTextValueComparer() {
		$this->assertMultilingualTextValueComparesWithoutException(
			$this->buildDataValueComparerFactory()->newMultilingualTextValueComparer()
		);
	}

	private function assertMultilingualTextValueComparesWithoutException( DataValueComparer $dataValueComparer ) {
		$this->assertComparesWithoutException(
			$dataValueComparer,
			$this->multilingualTextValue,
			$this->multilingualTextValue
		);
	}

	public function testNewQuantityValueComparer() {
		$this->assertQuantityValueComparesWithoutException(
			$this->buildDataValueComparerFactory()->newQuantityValueComparer()
		);
	}

	private function assertQuantityValueComparesWithoutException( DataValueComparer $dataValueComparer ) {
		$this->assertComparesWithoutException(
			$dataValueComparer,
			$this->quantityValue,
			$this->quantityValue
		);
	}

	public function testNewStringValueComparer() {
		$this->assertStringValueComparesWithoutException(
			$this->buildDataValueComparerFactory()->newStringValueComparer()
		);
	}

	private function assertStringValueComparesWithoutException( DataValueComparer $dataValueComparer ) {
		$this->assertComparesWithoutException(
			$dataValueComparer,
			$this->stringValue,
			$this->stringValue
		);
	}

	public function testNewTimeValueComparer() {
		$this->assertTimeValueComparesWithoutException(
			$this->buildDataValueComparerFactory()->newTimeValueComparer()
		);
	}

	private function assertTimeValueComparesWithoutException( DataValueComparer $dataValueComparer ) {
		$this->assertComparesWithoutException(
			$dataValueComparer,
			$this->timeValue,
			$this->timeValue
		);
	}

	public function testNewDispatchingDataValueComparer() {
		$dataValueComparer = $this->buildDataValueComparerFactory()->newDispatchingDataValueComparer();

		$this->assertEntityIdValueComparesWithoutException( $dataValueComparer );
		$this->assertGlobeCoordinateValueComparesWithoutException( $dataValueComparer );
		$this->assertMonolingualTextValueComparesWithoutException( $dataValueComparer );
		$this->assertMultilingualTextValueComparesWithoutException( $dataValueComparer );
		$this->assertQuantityValueComparesWithoutException( $dataValueComparer );
		$this->assertStringValueComparesWithoutException( $dataValueComparer );
		$this->assertTimeValueComparesWithoutException( $dataValueComparer );
	}

}
