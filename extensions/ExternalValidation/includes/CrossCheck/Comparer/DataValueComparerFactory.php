<?php

namespace WikibaseQuality\ExternalValidation\CrossCheck\Comparer;

use Wikibase\StringNormalizer;
use Wikibase\TermIndex;

/**
 * @package WikibaseQuality\ExternalValidation\CrossCheck\Comparer
 * @author BP2014N1
 * @license GNU GPL v2+
 */
class DataValueComparerFactory {

	/**
	 * @var TermIndex
	 */
	private $termIndex;

	/**
	 * @var StringNormalizer
	 */
	private $stringNormalizer;

	/**
	 * @param TermIndex $termIndex
	 * @param StringNormalizer $stringNormalizer
	 */
	public function __construct( TermIndex $termIndex, StringNormalizer $stringNormalizer ) {
		$this->termIndex = $termIndex;
		$this->stringNormalizer = $stringNormalizer;
	}

	/**
	 * Returns a DataValueComparer that can compare each DataValue object by dispatching comparison to specific comparer.
	 *
	 * @return DispatchingDataValueComparer
	 */
	public function newDispatchingDataValueComparer() {
		return new DispatchingDataValueComparer(
			array(
				$this->newEntityIdValueComparer(),
				$this->newGlobeCoordinateValueComparer(),
				$this->newMonolingualTextValueComparer(),
				$this->newMultilingualTextValueComparer(),
				$this->newQuantityValueComparer(),
				$this->newStringValueComparer(),
				$this->newTimeValueComparer()
			)
		);
	}

	/**
	 * Returns a DataValueComparer that can compare EntityIdValue objects.
	 *
	 * @return EntityIdValueComparer
	 */
	public function newEntityIdValueComparer() {
		return new EntityIdValueComparer(
			$this->termIndex,
			$this->newStringComparer()
		);
	}

	/**
	 * Returns a StringComparer that can compare various number of strings with each other.
	 *
	 * @return StringComparer
	 */
	private function newStringComparer() {
		return new StringComparer( $this->stringNormalizer );
	}

	/**
	 * Returns a DataValueComparer that can compare GlobeCoordinateValue objects.
	 *
	 * @return GlobeCoordinateValueComparer
	 */
	public function newGlobeCoordinateValueComparer() {
		return new GlobeCoordinateValueComparer();
	}

	/**
	 * Returns a DataValueComparer that can compare MonolingualTextValue objects.
	 *
	 * @return MonolingualTextValueComparer
	 */
	public function newMonolingualTextValueComparer() {
		return new MonolingualTextValueComparer(
			$this->newStringComparer()
		);
	}

	/**
	 * Returns a DataValueComparer that can compare MultilingualTextValue objects.
	 *
	 * @return MultilingualTextValueComparer
	 */
	public function newMultilingualTextValueComparer() {
		return new MultilingualTextValueComparer(
			$this->newStringComparer()
		);
	}

	/**
	 * Returns a DataValueComparer that can compare QuantityValue objects.
	 *
	 * @return QuantityValueComparer
	 */
	public function newQuantityValueComparer() {
		return new QuantityValueComparer();
	}

	/**
	 * Returns a DataValueComparer that can compare StringValue objects.
	 *
	 * @return StringValueComparer
	 */
	public function newStringValueComparer() {
		return new StringValueComparer(
			$this->newStringComparer()
		);
	}

	/**
	 * Returns a DataValueComparer that can compare TimeValue objects.
	 *
	 * @return TimeValueComparer
	 */
	public function newTimeValueComparer() {
		return new TimeValueComparer();
	}

}
