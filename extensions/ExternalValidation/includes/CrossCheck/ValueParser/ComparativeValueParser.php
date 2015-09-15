<?php

namespace WikibaseQuality\ExternalValidation\CrossCheck\ValueParser;

use DataValues\DataValue;
use ValueParsers\ValueParser;
use ValueParsers\ParserOptions;
use Wikibase\Repo\ValueParserFactory;
use WikibaseQuality\ExternalValidation\DumpMetaInformation\DumpMetaInformation;
use Wikimedia\Assert\Assert;


/**
 * Class NewComparativeValueParser
 *
 * @package WikibaseQuality\ExternalValidation\CrossCheck\ComparativeValueParser
 * @author BP2014N1
 * @license GNU GPL v2+
 */
class ComparativeValueParser {

	/**
	 * @var ValueParserFactory
	 */
	private $comparativeValueParserFactory;

	/**
	 * @var string
	 */
	private $languageCode;

	/**
	 * @var ValueParser[]
	 */
	private $valueParsers = array();

	/**
	 * @param ValueParserFactory $comparativeValueParserFactory
	 * @param string $languageCode
	 */
	public function __construct(
		ValueParserFactory $comparativeValueParserFactory,
		$languageCode
	) {
		Assert::parameterType( 'string', $languageCode, '$languageCode' );

		$this->comparativeValueParserFactory = $comparativeValueParserFactory;
		$this->languageCode = $languageCode;
	}

	/**
	 * Parses given string into DataValue for comparison with DataValues of $propertyType.
	 *
	 * @param string $value
	 * @param string $propertyType
	 *
	 * @return DataValue
	 */
	public function parse( $value, $propertyType ) {
		Assert::parameterType( 'string', $value, '$value' );
		Assert::parameterType( 'string', $propertyType, '$propertyType' );

		return $this->getValueParser( $propertyType )->parse( $value );
	}

	/**
	 * @param string $type
	 *
	 * @return ValueParser
	 */
	private function getValueParser( $type ) {
		if( !array_key_exists( $type, $this->valueParsers ) ) {
			$parserOptions = $this->getParserOptions();
			$this->valueParsers[$type] = $this->comparativeValueParserFactory->newParser(
				$type,
				$parserOptions
			);
		}

		return $this->valueParsers[$type];
	}

	/**
	 * @return ParserOptions
	 */
	private function getParserOptions() {
		$options = new ParserOptions();
		$options->setOption( 'valuelang', $this->languageCode );
		$options->setOption( ValueParser::OPT_LANG, $this->languageCode );

		return $options;
	}
}
