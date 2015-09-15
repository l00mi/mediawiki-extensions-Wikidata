<?php

namespace WikibaseQuality\ExternalValidation\CrossCheck\Comparer;

use DataValues\DataValue;
use DataValues\MultilingualTextValue;
use InvalidArgumentException;
use WikibaseQuality\ExternalValidation\CrossCheck\Result\ComparisonResult;

/**
 * Class MultilingualTextValueComparer
 *
 * @package WikibaseQuality\ExternalValidation\CrossCheck\Comparer
 * @author BP2014N1
 * @license GNU GPL v2+
 */
class MultilingualTextValueComparer implements DataValueComparer {

	/**
	 * @var StringComparer
	 */
	private $stringComparer;

	/**
	 * @param StringComparer $stringComparer
	 */
	public function __construct( StringComparer $stringComparer ) {
		$this->stringComparer = $stringComparer;
	}

	/**
	 * @see DataValueComparer::compare
	 *
	 * @param DataValue $value
	 * @param DataValue $comparativeValue
	 *
	 * @throws InvalidArgumentException
	 * @return ComparisonResult
	 */
	public function compare( DataValue $value, DataValue $comparativeValue ) {
		if( !$this->canCompare( $value, $comparativeValue ) ) {
			throw new InvalidArgumentException( 'Given values can not be compared using this comparer.' );
		}

		$texts = $value->getTexts();
		$comparativeTexts = $comparativeValue->getTexts();
		$commonLanguages = array_intersect( array_keys( $texts ), array_keys( $comparativeTexts ) );

		if( $commonLanguages ) {
			$totalResult = ComparisonResult::STATUS_MISMATCH;
			foreach ( $commonLanguages as $language ) {
				$monolingualText = $texts[$language];
				$comparativeMonolingualText = $comparativeTexts[$language];

				$result = $this->stringComparer->compare( $monolingualText->getText(), $comparativeMonolingualText->getText() );
				if ( $result !== ComparisonResult::STATUS_MISMATCH ) {
					$totalResult = $result;
					if ( $result === ComparisonResult::STATUS_MATCH ) {
						break;
					}
				}
			}

			return $totalResult;
		}
	}

	/**
	 * @see DataValueComparer::canCompare
	 *
	 * @param DataValue $value
	 * @param DataValue $comparativeValue
	 * @return bool
	 */
	public function canCompare( DataValue $value, DataValue $comparativeValue ) {
		return $value instanceof MultilingualTextValue && $comparativeValue instanceof MultilingualTextValue;
	}
}