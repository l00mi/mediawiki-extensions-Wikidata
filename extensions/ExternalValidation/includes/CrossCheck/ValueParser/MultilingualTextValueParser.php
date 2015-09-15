<?php

namespace WikibaseQuality\ExternalValidation\CrossCheck\ValueParser;

use InvalidArgumentException;
use DataValues\DataValue;
use DataValues\MultilingualTextValue;
use ValueParsers\ParserOptions;
use ValueParsers\ValueParser;
use Wikibase\Repo\ValueParserFactory;
use WikibaseQuality\ExternalValidation\DumpMetaInformation\DumpMetaInformation;
use Wikimedia\Assert\Assert;


/**
 * Class MultilingualStringValueParser
 * @package WikibaseQuality\ExternalValidation\CrossCheck\ComparativeValueParser
 * @author BP2014N1
 * @license GNU GPL v2+
 */
class MultilingualTextValueParser implements ValueParser {

	/**
	 * @var ValueParser
	 */
	private $monolingualTextValueParser;

	/**
	 * @param ValueParser $monolingualTextValueParser
	 */
	public function __construct( ValueParser $monolingualTextValueParser ) {
		$this->monolingualTextValueParser = $monolingualTextValueParser;
	}

	/**
	 * @see ValueParser::parse
	 *
	 * @param string $value
	 *
	 * @return MultilingualTextValue
	 */
	public function parse( $value ) {
		Assert::parameterType( 'string', $value, '$value' );

		return new MultilingualTextValue(
			array( $this->monolingualTextValueParser->parse( $value ) )
		);
	}
}