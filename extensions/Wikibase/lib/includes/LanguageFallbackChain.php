<?php

namespace Wikibase;

use InvalidArgumentException;
use Language;

/**
 * FIXME: this class is not a language fallback chain. It takes and uses a fallback chain.
 * The name thus needs to be updated to not be misleading.
 *
 * @since 0.4
 *
 * @licence GNU GPL v2+
 * @author Liangent
 * @author Thiemo Mättig
 */
class LanguageFallbackChain {

	/**
	 * @var LanguageWithConversion[]
	 */
	private $chain;

	/**
	 * @param LanguageWithConversion[] $chain
	 */
	public function __construct( array $chain ) {
		$this->chain = $chain;
	}

	/**
	 * Get raw fallback chain as an array. Semi-private for testing.
	 *
	 * @return LanguageWithConversion[]
	 */
	public function getFallbackChain() {
		return $this->chain;
	}

	/**
	 * Try to fetch the best value in a multilingual data array.
	 *
	 * @param string[]|array[] $data Multilingual data with language codes as keys
	 *
	 * @throws InvalidArgumentException
	 * @return string[]|null of three items: array(
	 * 	'value' => finally fetched and translated value
	 * 	'language' => language code of the language which final value is in
	 * 	'source' => language code of the language where the value is translated from
	 * ), or null when no "acceptable" data can be found.
	 */
	public function extractPreferredValue( array $data ) {
		foreach ( $this->chain as $languageWithConversion ) {
			$languageCode = $languageWithConversion->getFetchLanguageCode();

			if ( isset( $data[$languageCode] ) ) {
				$value = $data[$languageCode];

				// Data from an EntityInfoBuilder is already made of pre-build arrays
				if ( is_array( $value ) ) {
					$value = $value['value'];
				}

				return $this->getValueArray(
					$languageWithConversion->translate( $value ),
					$languageWithConversion->getLanguageCode(),
					$languageWithConversion->getSourceLanguageCode()
				);
			}
		}

		return null;
	}

	/**
	 * Try to fetch the best value in a multilingual data array first.
	 * If no "acceptable" value exists, return any value known.
	 *
	 * @param string[]|array[] $data Multilingual data with language codes as keys
	 *
	 * @return string[]|null of three items: array(
	 * 	'value' => finally fetched and translated value
	 * 	'language' => language code of the language which final value is in
	 * 	'source' => language code of the language where the value is translated from
	 * ), or null when no data with a valid language code can be found.
	 */
	public function extractPreferredValueOrAny( array $data ) {
		$preferred = $this->extractPreferredValue( $data );

		if ( $preferred !== null ) {
			return $preferred;
		}

		foreach ( $data as $languageCode => $value ) {
			if ( Language::isValidCode( $languageCode ) ) {
				// We can not translate here, we do not have a LanguageWithConversion object
				return $this->getValueArray( $value, $languageCode );
			}
		}

		return null;
	}

	/**
	 * @param string|string[] $value
	 * @param string $languageCode
	 * @param string|null $sourceLanguageCode
	 *
	 * @return string[]
	 */
	private function getValueArray( $value, $languageCode, $sourceLanguageCode = null ) {
		// Data from an EntityInfoBuilder is already made of pre-build arrays
		if ( !is_array( $value ) ) {
			$value = array(
				'value' => $value,
				'language' => $languageCode,
				'source' => $sourceLanguageCode,
			);
		}

		return $value;
	}

}
