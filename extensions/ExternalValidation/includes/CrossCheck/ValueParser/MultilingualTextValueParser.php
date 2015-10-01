<?php

namespace WikibaseQuality\ExternalValidation\CrossCheck\ValueParser;

use DataValues\MultilingualTextValue;
use ValueParsers\ValueParser;
use Wikimedia\Assert\Assert;

/**
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
