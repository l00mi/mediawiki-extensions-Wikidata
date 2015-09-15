<?php

namespace WikibaseQuality\ExternalValidation\CrossCheck\Comparer;

use InvalidArgumentException;
use Wikibase\StringNormalizer;
use WikibaseQuality\ExternalValidation\CrossCheck\Result\ComparisonResult;
use Wikimedia\Assert\Assert;

/**
 * Class StringComparer
 *
 * @package WikibaseQuality\ExternalValidation\CrossCheck\Comparer
 * @author BP2014N1
 * @license GNU GPL v2+
 */
class StringComparer {

	/**
	 * Threshold for matching compliance in prefix/suffix similarity checks
	 */
	const SIMILARITY_THRESHOLD = 0.75;

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
	 * Compares two strings with each other.
	 *
	 * @param string $value
	 * @param string $comparativeValue
	 * @return string
	 */
	public function compare( $value, $comparativeValue ) {
		Assert::parameterType( 'string', $value, '$value' );
		Assert::parameterType( 'string', $comparativeValue, '$comparativeValue' );

		$value = $this->cleanDataString( $value );
		$comparativeValue = $this->cleanDataString( $comparativeValue );

		if ( $value === $comparativeValue ) {
			return ComparisonResult::STATUS_MATCH;
		} elseif ( $this->checkSimilarity( $value, $comparativeValue ) ) {
			return ComparisonResult::STATUS_PARTIAL_MATCH;
		} else {
			return ComparisonResult::STATUS_MISMATCH;
		}
	}

	/**
	 * Compares single string with array of strings.
	 *
	 * @param string $value
	 * @param array $comparativeValues
	 * @return string
	 */
	public function compareWithArray( $value, array $comparativeValues ) {
		Assert::parameterType( 'string', $value, '$value' );
		Assert::parameterElementType( 'string', $comparativeValues, '$comparativeValues' );

		$value = $this->cleanDataString( $value );
		$comparativeValues = $this->cleanDataArray( $comparativeValues );

		if ( in_array( $value, $comparativeValues ) ) {
			return ComparisonResult::STATUS_MATCH;
		}

		foreach ( $comparativeValues as $comparativeValue ) {
			if ( $this->checkSimilarity( $comparativeValue, $value ) ) {
				return ComparisonResult::STATUS_PARTIAL_MATCH;
			}
		}

		return ComparisonResult::STATUS_MISMATCH;
	}

	/**
	 * Checks the similarity of two strings by prefix/suffix check.
	 *
	 * @param string $value
	 * @param string $comparativeValue
	 * @return bool
	 */
	private function checkSimilarity( $value, $comparativeValue ) {
		return
			$this->percentagePrefixSimilarity( $value, $comparativeValue ) > self::SIMILARITY_THRESHOLD ||
			$this->percentageSuffixSimilarity( $value, $comparativeValue ) > self::SIMILARITY_THRESHOLD ||
			$this->percentageLevenshteinDistance( $value, $comparativeValue ) > self::SIMILARITY_THRESHOLD;
	}

	/**
	 * Returns cleaned (without whitespaces at beginning/end and lowercase) string of a given input string.
	 *
	 * @param string $value
	 *
	 * @return string
	 */
	private function cleanDataString( $value ) {
		$value = $this->stringNormalizer->trimToNFC( $value );

		return mb_strtolower( $value );
	}

	/**
	 * Returns cleaned (without whitespaces at beginning/end and lowercase) array of strings of a given input array.
	 *
	 * @param array $array
	 *
	 * @return array
	 */
	private function cleanDataArray( array $array ) {

		return array_map(
			array( $this, 'cleanDataString' ),
			$array );
	}

	/**
	 * Returns percentage of local value prefix-matching the external values.
	 *
	 * @param string $value - value to prefix-match with external value
	 * @param string $comparativeValue - value to prefix-match with local value
	 *
	 * @return float
	 */
	private function percentagePrefixSimilarity( $value, $comparativeValue ) {
		$prefixLength = 0; // common prefix length
		$localLength = strlen( $value );
		$externalLength = strlen( $comparativeValue );
		while ( $prefixLength < min( $localLength, $externalLength ) ) {
			$c = $value[$prefixLength];
			if ( $externalLength > $prefixLength && $comparativeValue[$prefixLength] !== $c ) {
				break;
			}
			$prefixLength++;
		}

		return $prefixLength / max( $localLength, $externalLength );
	}

	/**
	 * Returns percentage of local value suffix-matching the external values.
	 *
	 * @param string $value - value to suffix-match with local value
	 * @param string $comparativeValue - value to suffix-match with external value
	 *
	 * @return float
	 */
	private function percentageSuffixSimilarity( $value, $comparativeValue ) {
		$suffixLength = 0; // common suffix length
		$localLength = strlen( $value );
		$externalLength = strlen( $comparativeValue );
		while ( $suffixLength < min( $localLength, $externalLength ) ) {
			$c = $value[$localLength - 1 - $suffixLength];
			if ( $externalLength > $suffixLength && $comparativeValue[$externalLength - 1 - $suffixLength] !== $c ) {
				break;
			}
			$suffixLength++;
		}

		return $suffixLength / max( $localLength, $externalLength );
	}

	/**
	 * Returns percentage of similarity using levenshtein distance.
	 *
	 * @param string $value
	 * @param string $comparativeValue
	 *
	 * @return float
	 */
	private function percentageLevenshteinDistance( $value, $comparativeValue ) {
		$distance = levenshtein( $value, $comparativeValue );
		$percentage = 1.0 - $distance / max( strlen( $value ), strlen( $comparativeValue ) );

		return $percentage;
	}
}
