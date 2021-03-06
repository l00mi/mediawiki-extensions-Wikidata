<?php

namespace WikibaseQuality\ExternalValidation\CrossCheck\ValueParser;

use DataValues\StringValue;
use ValueParsers\ValueParser;
use Wikibase\StringNormalizer;
use Wikimedia\Assert\Assert;

class StringValueParser implements ValueParser {

	/**
	 * @var StringNormalizer
	 */
	private $stringNormalizer;

	/**
	 * @param StringNormalizer $stringNormalizer
	 */
	public function __construct( StringNormalizer $stringNormalizer ) {
		$this->stringNormalizer = $stringNormalizer;
	}

	/**
	 * @see ValueParser::parse
	 *
	 * @param string $value
	 *
	 * @return StringValue
	 */
	public function parse( $value ) {
		Assert::parameterType( 'string', $value, '$value' );

		return new StringValue( $this->stringNormalizer->trimToNFC( $value ) );
	}

}
