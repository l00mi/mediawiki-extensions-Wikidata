<?php

namespace WikibaseQuality\ExternalValidation\CrossCheck\Comparer;

use DataValues\DataValue;
use DataValues\MonolingualTextValue;
use InvalidArgumentException;

/**
 * Class MonolingualTextValueComparer
 *
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
	 * @return string
	 */
	public function compare( DataValue $value, DataValue $comparativeValue ) {
		if( !$this->canCompare( $value, $comparativeValue ) ) {
			throw new InvalidArgumentException( 'Given values can not be compared using this comparer.' );
		}

		if( $value->getLanguageCode() === $comparativeValue->getLanguageCode() ) {
			return $this->stringComparer->compare( $value->getText(), $comparativeValue->getText() );
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
		return $value instanceof MonolingualTextValue && $comparativeValue instanceof MonolingualTextValue;
	}
}