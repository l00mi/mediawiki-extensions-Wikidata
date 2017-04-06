<?php

namespace WikibaseQuality\ExternalValidation\Tests\CrossCheck\ValueParser;

use Wikibase\StringNormalizer;
use WikibaseQuality\ExternalValidation\CrossCheck\ValueParser\StringValueParser;
use DataValues\StringValue;

/**
 * @covers \WikibaseQuality\ExternalValidation\CrossCheck\ValueParser\StringValueParser
 *
 * @group WikibaseQualityExternalValidation
 *
 * @author BP2014N1
 * @license GNU GPL v2+
 */
class StringValueParserTest extends ValueParserTestBase {

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
				new StringValue( 'foo' ),
				'foo'
			),
			array(
				new StringValue( 'bar' ),
				'bar'
			)
		);
	}

	protected function buildParser() {
		$stringNormalizer = $this->getMockBuilder( StringNormalizer::class )
			->setMethods( array( 'trimToNFC' ) )
			->getMock();
		$stringNormalizer->expects( $this->any() )
			->method( 'trimToNFC' )
			->will( $this->returnArgument( 0 ) );

		return new StringValueParser( $stringNormalizer );
	}

}
