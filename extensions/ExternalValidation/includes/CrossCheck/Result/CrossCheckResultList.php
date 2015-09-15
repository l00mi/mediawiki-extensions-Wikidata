<?php

namespace WikibaseQuality\ExternalValidation\CrossCheck\Result;

use ArrayIterator;
use Countable;
use InvalidArgumentException;
use IteratorAggregate;
use Wikibase\DataModel\Entity\PropertyId;
use Wikimedia\Assert\Assert;


/**
 * Class CrossCheckResultList
 *
 * @package WikibaseQuality\ExternalValidation\CrossCheck\Result
 * @author BP2014N1
 * @license GNU GPL v2+
 */
class CrossCheckResultList implements IteratorAggregate, Countable {

	/**
	 * @var CrossCheckResult[]
	 */
	private $results;

	/**
	 * @param CrossCheckResult[] $results
	 *
	 * @throws InvalidArgumentException
	 */
	public function __construct( array $results = array() ) {
		Assert::parameterElementType(
			'WikibaseQuality\ExternalValidation\CrossCheck\Result\CrossCheckResult',
			$results,
			'$results'
		);

		$this->results = $results;
	}

	/**
	 * Adds a given CrossCheckResult to the list
	 *
	 * @param CrossCheckResult $result
	 */
	public function add( CrossCheckResult $result ) {
		$this->results[] = $result;
	}

	/**
	 * Merges another CrossCheckResultList to the current one
	 *
	 * @param CrossCheckResultList $resultList
	 */
	public function merge( CrossCheckResultList $resultList ) {
		$this->results = array_merge( $this->results, $resultList->results );
	}

	/**
	 * Returns the property ids used by crosscheck results
	 *
	 * @return PropertyId[]
	 */
	public function getPropertyIds() {
		$propertyIds = array();

		foreach ( $this->results as $result ) {
			$propertyId = $result->getPropertyId();
			if ( !in_array( $propertyId, $propertyIds ) ) {
				$propertyIds[] = $propertyId;
			}
		}

		return $propertyIds;
	}

	/**
	 * Returns all crosscheck results using given property id
	 *
	 * @param $propertyId
	 *
	 * @return CrossCheckResultList
	 */
	public function getByPropertyId( PropertyId $propertyId ) {
		$results = array();

		foreach ( $this->results as $result ) {
			if ( $result->getPropertyId()->equals( $propertyId ) ) {
				$results[] = $result;
			}
		}

		return new self( $results );
	}

	/**
	 * Returns results as array
	 *
	 * @return CrossCheckResult[]
	 */
	public function toArray() {
		return $this->results;
	}

	/**
	 * Gets an iterator for results
	 *
	 * @return ArrayIterator
	 * @codeCoverageIgnore
	 */
	public function getIterator() {
		return new ArrayIterator( $this->results );
	}

	/**
	 * Counts number of results
	 *
	 * @return int
	 */
	public function count() {
		return count( $this->results );
	}
}