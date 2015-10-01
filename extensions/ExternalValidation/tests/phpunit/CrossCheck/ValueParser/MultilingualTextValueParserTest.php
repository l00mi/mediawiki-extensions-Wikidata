<?php

namespace WikibaseQuality\ExternalValidation\Tests\CrossCheck\ValueParser;

use DataValues\MonolingualTextValue;
use DataValues\MultilingualTextValue;
use WikibaseQuality\ExternalValidation\CrossCheck\ValueParser\MultilingualTextValueParser;

/**
 * @covers WikibaseQuality\ExternalValidation\CrossCheck\ValueParser\MultilingualTextValueParser
 *
 * @group WikibaseQualityExternalValidation
 *
 * @author BP2014N1
 * @license GNU GPL v2+
 */
class MultilingualTextValueParserTest extends ValueParserTestBase {

	public function nonParseableProvider() {
		return array(
			array(
				42
			),
			array(
				true
			)
		);
	}

	public function parsingProvider() {
		return array(
			array(
				new MultilingualTextValue( array( new MonolingualTextValue( 'en', 'foo' ) ) ),
				'foo'
			),
			array(
				new MultilingualTextValue( array( new MonolingualTextValue( 'en', 'foo' ) ) ),
				'foo'
			)
		);
	}

	protected function buildParser() {
		$valueParserMock = $this->getMockBuilder( 'ValueParsers\ValueParser' )
					 ->setMethods( array( 'parse' ) )
					 ->getMock();
		$valueParserMock->expects( $this->any() )
			 ->method( 'parse' )
			 ->will( $this->returnValue( new MonolingualTextValue( 'en', 'foo' ) ) );

		return new MultilingualTextValueParser( $valueParserMock );
	}

}
