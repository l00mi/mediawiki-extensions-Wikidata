<?php

namespace WikibaseQuality\ExternalValidation\Tests\CrossCheck\ValueParser;

use ValueParsers\ParserOptions;
use Wikibase\Lib\DataTypeDefinitions;
use Wikibase\Repo\Parsers\MonolingualTextParser;
use Wikibase\StringNormalizer;
use WikibaseQuality\ExternalValidation\CrossCheck\ValueParser\ComparativeValueParserFactory;

/**
 * @covers WikibaseQuality\ExternalValidation\CrossCheck\ValueParser\ComparativeValueParserFactory
 *
 * @group WikibaseQualityExternalValidation
 *
 * @author BP2014N1
 * @license GNU GPL v2+
 */
class ComparativeValueParserFactoryTest extends \MediaWikiTestCase {

	public function testNewComparativeStringValueParser() {
		$parser = $this->getFactory()->newStringValueParser();
		$this->assertInstanceOf( 'WikibaseQuality\ExternalValidation\CrossCheck\ValueParser\StringValueParser', $parser );
	}

	public function testNewComparativeMultilingualTextValueParser() {
		$parser = $this->getFactory()->newMultilingualTextValueParser();
		$this->assertInstanceOf( 'WikibaseQuality\ExternalValidation\CrossCheck\ValueParser\MultilingualTextValueParser', $parser );
	}

	public function testNewComparativeValueParser() {
		$parser = $this->getFactory()->newComparativeValueParser( 'en' );
		$this->assertInstanceOf( 'WikibaseQuality\ExternalValidation\CrossCheck\ValueParser\ComparativeValueParser', $parser );
	}

	/**
	 * @return ComparativeValueParserFactory
	 */
	private function getFactory() {
		$dataTypeDefinitionConfig = array(
			'monolingualtext' => array(
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
		$this->setExpectedException( 'InvalidArgumentException' );

		new ComparativeValueParserFactory(
			new DataTypeDefinitions(),
			new StringNormalizer()
		);
	}

}
