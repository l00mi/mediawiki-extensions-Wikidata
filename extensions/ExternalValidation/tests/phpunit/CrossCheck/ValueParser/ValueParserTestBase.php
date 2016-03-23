<?php

namespace WikibaseQuality\ExternalValidation\Tests\CrossCheck\ValueParser;

use InvalidArgumentException;
use PHPUnit_Framework_TestCase;
use ValueParsers\ValueParser;

/**
 * @group WikibaseQualityExternalValidation
 *
 * @author BP2014N1
 * @license GNU GPL v2+
 */
abstract class ValueParserTestBase extends PHPUnit_Framework_TestCase {

	/**
	 * @return ValueParser
	 */
	protected abstract function buildParser();

	public function testImplementsDataValueComparerInterface() {
		$this->assertInstanceOf( ValueParser::class, $this->buildParser() );
	}

	public abstract function nonParseableProvider();

	/**
	 * @dataProvider nonParseableProvider
	 */
	public function testParserThrowsInvalidArgumentException( $value ) {
		$this->setExpectedException( InvalidArgumentException::class );
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
