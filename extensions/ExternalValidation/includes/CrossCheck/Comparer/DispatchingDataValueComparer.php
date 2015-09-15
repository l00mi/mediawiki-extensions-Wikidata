<?php

namespace WikibaseQuality\ExternalValidation\CrossCheck\Comparer;


use DataValues\DataValue;
use InvalidArgumentException;
use WikibaseQuality\ExternalValidation\DumpMetaInformation\DumpMetaInformation;

/**
 * Class DispatchingDataValueComparer
 * @package WikibaseQuality\ExternalValidation\CrossCheck\Comparer
 * @author BP2014N1
 * @license GNU GPL v2+
 */
class DispatchingDataValueComparer implements DataValueComparer {

	/**
	 * @var DataValueComparer[]
	 */
	private $dataValueComparers;

	/**
	 * @param DataValueComparer[] $dataValueComparers
	 */
	public function __construct( array $dataValueComparers = array() ) {
		$this->assertAreDataValueComparer( $dataValueComparers );
		$this->dataValueComparers = $dataValueComparers;
	}

	/**
	 * @param DataValueComparer[] $dataValueComparers
	 */
	protected function assertAreDataValueComparer( array $dataValueComparers ) {
		foreach ( $dataValueComparers as $dataValueComparer ) {
			if ( !is_object( $dataValueComparer ) || !( $dataValueComparer instanceof DataValueComparer ) ) {
				throw new InvalidArgumentException(
					'All $dataValueComparers need to implement the DataValueComparer interface'
				);
			}
		}
	}

	/**
	 * @see DataValueComparer::canCompare
	 *
	 * @param string $dataValueType
	 * @return bool
	 */
	public function canCompare( DataValue $value, DataValue $comparativeValue ) {
		foreach ( $this->dataValueComparers as $dataValueComparer ) {
			if ( $dataValueComparer->canCompare( $value, $comparativeValue ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * @see DataValueComparer::compare
	 *
	 * @param DataValue $value
	 * @param DataValue $comparativeValue
	 * @return string
	 */
	public function compare( DataValue $value, DataValue $comparativeValue ) {
		foreach ( $this->dataValueComparers as $dataValueComparer ) {
			if ( $dataValueComparer->canCompare( $value, $comparativeValue ) ) {
				return $dataValueComparer->compare( $value, $comparativeValue );
			}
		}

		throw new InvalidArgumentException(
			'None of the DataValueComparers can compare the provided DataValue'
		);
	}
}