<?php

namespace WikibaseQuality\ExternalValidation\CrossCheck\ValueParser;

use InvalidArgumentException;
use ValueParsers\ParserOptions;
use Wikibase\Repo\ValueParserFactory;
use Wikibase\Lib\DataTypeDefinitions;
use Wikibase\StringNormalizer;
use WikibaseQuality\ExternalValidation\DumpMetaInformation\DumpMetaInformation;
use Wikimedia\Assert\Assert;

/**
 * Class ComparativeValueParserFactory
 * @package WikibaseQuality\ExternalValidation\CrossCheck\ComparativeValueParser
 * @author BP2014N1
 * @license GNU GPL v2+
 */
class ComparativeValueParserFactory {

	/**
	 * @var DataTypeDefinitions
	 */
	private $dataTypeDefinitions;

	/**
	 * @var StringNormalizer
	 */
	private $stringNormalizer;

	/**
	 * @var ValueParserFactory
	 */
	private $valueParserFactory = null;

	/**
	 * @param DataTypeDefinitions $dataTypeDefinitions monolingualtext is required the definition.
	 * @param StringNormalizer $stringNormalizer
	 *
	 * @throws InvalidArgumentException
	 */
	public function __construct(
		DataTypeDefinitions $dataTypeDefinitions,
		StringNormalizer $stringNormalizer
	) {
		$dataTypeIds = $dataTypeDefinitions->getTypeIds();

		if ( !in_array( 'monolingualtext', $dataTypeIds ) ) {
			throw new InvalidArgumentException(
				'monolingualtext must be defined in the DataTypeDefinitions.'
			);
		}

		$this->dataTypeDefinitions = $dataTypeDefinitions;
		$this->stringNormalizer = $stringNormalizer;
	}

	/**
	 * @param ParserOptions|null $options
	 *
	 * @return MultilingualTextValueParser
	 */
	public function newMultilingualTextValueParser( ParserOptions $options = null ) {
		$options = $options ?: new ParserOptions();
		$monolingualTextValueParser = $this->getValueParserFactory()->newParser(
			'monolingualtext',
			$options
		);

		return new MultilingualTextValueParser( $monolingualTextValueParser );
	}

	/**
	 * @return StringValueParser
	 */
	public function newStringValueParser() {
		return new StringValueParser( $this->stringNormalizer );
	}

	/**
	 * @param string $languageCode
	 *
	 * @return ComparativeValueParser
	 */
	public function newComparativeValueParser( $languageCode ) {
		Assert::parameterType( 'string', $languageCode, '$languageCode' );

		return new ComparativeValueParser(
			$this->getValueParserFactory(),
			$languageCode
		);
	}

	/**
	 * Creates new ValueParserFactory that parses strings to DataValues for comparison
	 * with DataValues of given type.
	 *
	 * Factory is built based on given factory, but extends it to support parsing into
	 * StringValues and MultilingualTextValues. Parser for EntityIdValue is overwritten
	 * by MonolingualTextValueParser to meet requirements of EntityIdValueComparer.
	 *
	 * @return ValueParserFactory
	 */
	private function getValueParserFactory() {
		if ( $this->valueParserFactory === null ) {
			$callbacks = $this->dataTypeDefinitions->getParserFactoryCallbacks();

			$callbacks['string'] = array( $this, 'newStringValueParser' );
			$callbacks['multilingualtext'] = array( $this, 'newMultilingualTextValueParser' );
			$callbacks['wikibase-entityid'] = $callbacks['monolingualtext'];

			$this->valueParserFactory = new ValueParserFactory( $callbacks );
		}

		return $this->valueParserFactory;
	}

}
