<?php

namespace WikibaseQuality\ExternalValidation\CrossCheck\Comparer;

use DataValues\DataValue;
use DataValues\MonolingualTextValue;
use InvalidArgumentException;
use WikibaseQuality\ExternalValidation\CrossCheck\Result\ComparisonResult;

/**
 * @package WikibaseQuality\ExternalValidation\CrossCheck\Comparer
 * @author BP2014N1
 * @license GNU GPL v2+
 */
class MonolingualTextValueComparer implements DataValueComparer {

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
	 * @return string|null One of the ComparisonResult::STATUS_... constants.
	 */
	public function compare( DataValue $value, DataValue $comparativeValue ) {
		if ( !$this->canCompare( $value, $comparativeValue ) ) {
			throw new InvalidArgumentException( 'Given values can not be compared using this comparer.' );
		}

		/**
		 * @var MonolingualTextValue $value
		 * @var MonolingualTextValue $comparativeValue
		 */

		if ( $value->getLanguageCode() === $comparativeValue->getLanguageCode() ) {
			return $this->stringComparer->compare( $value->getText(), $comparativeValue->getText() );
		}

		return null;
	}

	/**
	 * @see DataValueComparer::canCompare
	 *
	 * @param DataValue $value
	 * @param DataValue $comparativeValue
	 * @return bool
	 */
	public function canCompare( DataValue $value, DataValue $comparativeValue ) {
		return $value instanceof MonolingualTextValue && $comparativeValue instanceof MonolingualTextValue;
	}

}
