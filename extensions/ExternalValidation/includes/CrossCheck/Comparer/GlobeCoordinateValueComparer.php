<?php

namespace WikibaseQuality\ExternalValidation\CrossCheck\Comparer;

use DataValues\DataValue;
use DataValues\GlobeCoordinateValue;
use InvalidArgumentException;
use WikibaseQuality\ExternalValidation\CrossCheck\Result\ComparisonResult;

/**
 * @package WikibaseQuality\ExternalValidation\CrossCheck\Comparer
 * @author BP2014N1
 * @license GNU GPL v2+
 */
class GlobeCoordinateValueComparer implements DataValueComparer {

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

		/**
		 * @var GlobeCoordinateValue $value
		 * @var GlobeCoordinateValue $comparativeValue
		 */

		$precision = $value->getPrecision();
		$locLat = $value->getLatitude();
		$locLong = $value->getLongitude();
		$extLat = $comparativeValue->getLatitude();
		$extLong = $comparativeValue->getLongitude();

		$diffLat = abs( $locLat - $extLat );
		$diffLong = abs( $locLong - $extLong );
		if ( ( $diffLat <= $precision ) && ( $diffLong <= $precision ) ) {
			return ComparisonResult::STATUS_MATCH;
		}

		$daumen = $precision;
		if ( ( $diffLat <= pi() * $daumen ) && ( $diffLong <= pi() * $daumen ) ) {
			return ComparisonResult::STATUS_PARTIAL_MATCH;
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
		return $value instanceof GlobeCoordinateValue && $comparativeValue instanceof GlobeCoordinateValue;
	}

}
