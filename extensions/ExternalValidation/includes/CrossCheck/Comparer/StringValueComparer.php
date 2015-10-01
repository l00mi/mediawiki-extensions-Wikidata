<?php

namespace WikibaseQuality\ExternalValidation\CrossCheck\Comparer;

use DataValues\DataValue;
use DataValues\StringValue;
use InvalidArgumentException;

/**
 * @package WikibaseQuality\ExternalValidation\CrossCheck\Comparer
 * @author BP2014N1
 * @license GNU GPL v2+
 */
class StringValueComparer implements DataValueComparer {

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
	 * @return string
	 */
	public function compare( DataValue $value, DataValue $comparativeValue ) {
		if( !$this->canCompare( $value, $comparativeValue ) ) {
			throw new InvalidArgumentException( 'Given values can not be compared using this comparer.' );
		}

		return $this->stringComparer->compare( $value->getValue(), $comparativeValue->getValue());
	}

	/**
	 * @see DataValueComparer::canCompare
	 *
	 * @param DataValue $value
	 * @param DataValue $comparativeValue
	 * @return bool
	 */
	public function canCompare( DataValue $value, DataValue $comparativeValue ) {
		return $value instanceof StringValue && $comparativeValue instanceof StringValue;
	}

}
