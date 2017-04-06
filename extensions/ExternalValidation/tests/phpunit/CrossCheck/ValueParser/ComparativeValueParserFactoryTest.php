<?php

namespace WikibaseQuality\ExternalValidation\Tests\CrossCheck\ValueParser;

use InvalidArgumentException;
use ValueParsers\ParserOptions;
use Wikibase\Lib\DataTypeDefinitions;
use Wikibase\Repo\Parsers\MonolingualTextParser;
use Wikibase\StringNormalizer;
use WikibaseQuality\ExternalValidation\CrossCheck\ValueParser\ComparativeValueParser;
use WikibaseQuality\ExternalValidation\CrossCheck\ValueParser\ComparativeValueParserFactory;
use WikibaseQuality\ExternalValidation\CrossCheck\ValueParser\MultilingualTextValueParser;
use WikibaseQuality\ExternalValidation\CrossCheck\ValueParser\StringValueParser;

/**
 * @covers \WikibaseQuality\ExternalValidation\CrossCheck\ValueParser\ComparativeValueParserFactory
 *
 * @group WikibaseQualityExternalValidation
 *
 * @author BP2014N1
 * @license GNU GPL v2+
 */
class ComparativeValueParserFactoryTest extends \MediaWikiTestCase {

	public function testNewComparativeStringValueParser() {
		$parser = $this->getFactory()->newStringValueParser();
		$this->assertInstanceOf( StringValueParser::class, $parser );
	}

	public function testNewComparativeMultilingualTextValueParser() {
		$parser = $this->getFactory()->newMultilingualTextValueParser();
		$this->assertInstanceOf( MultilingualTextValueParser::class, $parser );
	}

	public function testNewComparativeValueParser() {
		$parser = $this->getFactory()->newComparativeValueParser( 'en' );
		$this->assertInstanceOf( ComparativeValueParser::class, $parser );
	}

	/**
	 * @return ComparativeValueParserFactory
	 */
	private function getFactory() {
		$dataTypeDefinitionConfig = array(
			'PT:monolingualtext' => array(
				'value-type' => 'monolingualtext'
			),
			'VT:monolingualtext' => array(
				'parser-factory-callback' => function( ParserOptions $options ) {
            	    return new MonolingualTextParser( $options );
            	}
			)
		);

		return new ComparativeValueParserFactory(
			new DataTypeDefinitions( $dataTypeDefinitionConfig ),
			new StringNormalizer()
		);
	}

	public function testConstructor_missingMonolingualText() {
		$this->setExpectedException( InvalidArgumentException::class );

		new ComparativeValueParserFactory(
			new DataTypeDefinitions( array() ),
			new StringNormalizer()
		);
	}

}
