<?php

namespace WikibaseQuality\ExternalValidation\Tests\CrossCheck\ValueParser;

use DataValues\DataValue;
use ValueParsers\ValueParser;
use WikibaseQuality\ExternalValidation\CrossCheck\ComparativeValueParser\ComparativeValueParser;
use WikibaseQuality\ExternalValidation\CrossCheck\Comparer\DataValueComparer;
use WikibaseQuality\ExternalValidation\DumpMetaInformation\DumpMetaInformation;


/**
 * @group WikibaseQualityExternalValidation
 *
 * @author BP2014N1
 * @license GNU GPL v2+
 */
abstract class ValueParserTestBase extends \MediaWikiTestCase {

	/**
	 * @return ValueParser
	 */
	protected abstract function buildParser();

	public function testImplementsDataValueComparerInterface() {
		$this->assertInstanceOf( 'ValueParsers\ValueParser', $this->buildParser() );
	}

	public abstract function nonParseableProvider();

	/**
	 * @dataProvider nonParseableProvider
	 */
	public function testParserThrowsInvalidArgumentException( $value ) {
		$this->setExpectedException( 'InvalidArgumentException' );
		$this->buildParser()->parse( $value );
	}

	public abstract function parsingProvider();

	/**
	 * @dataProvider parsingProvider
	 */
	public function testParsing( $expectedResult, $value ) {
		$actualResult = $this->buildParser()->parse( $value );

		$this->assertEquals( $expectedResult, $actualResult );
	}
}