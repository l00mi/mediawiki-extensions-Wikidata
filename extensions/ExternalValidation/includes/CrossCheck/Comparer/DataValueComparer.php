<?php

namespace WikibaseQuality\ExternalValidation\CrossCheck\Comparer;

use DataValues\DataValue;


/**
 * Interface DataValueComparer
 * @package WikibaseQuality\ExternalValidation\CrossCheck\Comparer
 * @author BP2014N1
 * @license GNU GPL v2+
 */
interface DataValueComparer {

	/**
	 * @param DataValue $value
	 * @param DataValue $comparativeValue
	 *
	 * @return bool
	 */
	public function canCompare( DataValue $value, DataValue $comparativeValue );

	/**
	 * Runs the comparison of two DataValues.
	 *
	 * @param DataValue $value
	 * @param DataValue $comparativeValue
	 *
	 * @return string (one of the status constants of ComparisonResult)
	 */
	public function compare( DataValue $value, DataValue $comparativeValue );
}