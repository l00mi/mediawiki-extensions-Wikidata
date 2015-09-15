<?php

namespace WikibaseQuality\ExternalValidation\CrossCheck\Comparer;

use DataValues\DataValue;
use DataValues\QuantityValue;
use InvalidArgumentException;
use WikibaseQuality\ExternalValidation\CrossCheck\Result\ComparisonResult;

/**
 * Class QuantityValueComparer
 *
 * @package WikibaseQuality\ExternalValidation\CrossCheck\Comparer
 * @author BP2014N1
 * @license GNU GPL v2+
 */
class QuantityValueComparer implements DataValueComparer {

	/**
	 * @see DataValueComparer::compare
	 *
	 * @param DataValue $value
	 * @param DataValue $comparativeValue
	 * @return string
	 */
	public function compare( DataValue $value, DataValue $comparativeValue ) {
		if( !$this->canCompare( $value, $comparativeValue ) ) {
			throw new InvalidArgumentException( 'Given values can not be compared using this comparer.' );
		}

		if ( $comparativeValue->getLowerBound()->compare( $value->getUpperBound() ) <= 0 &&
			$comparativeValue->getUpperBound()->compare( $value->getLowerBound() ) >= 0
		) {
			return ComparisonResult::STATUS_MATCH;
		}

		return ComparisonResult::STATUS_MISMATCH;
	}

	/**
	 * @see DataValueComparer::canCompare
	 *
	 * @param DataValue $value
	 * @param DataValue $comparativeValue
	 * @return bool
	 */
	public function canCompare( DataValue $value, DataValue $comparativeValue ) {
		return $value instanceof QuantityValue && $comparativeValue instanceof QuantityValue;
	}
}