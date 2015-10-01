<?php

namespace WikibaseQuality\ExternalValidation\CrossCheck\Result;

use DataValues\DataValue;
use InvalidArgumentException;
use Wikimedia\Assert\Assert;

/**
 * @package WikibaseQuality\ExternalValidation\CrossCheck\Result
 * @author BP2014N1
 * @license GNU GPL v2+
 */
class ComparisonResult {

	const STATUS_MATCH = 'match';
	const STATUS_PARTIAL_MATCH = 'partial-match';
	const STATUS_MISMATCH = 'mismatch';

	/**
	 * Wikibase data value
	 *
	 * @var DataValue
	 */
	private $localValue;

	/**
	 * Data values from external database
	 *
	 * @var DataValue[]
	 */
	private $externalValues;

	/**
	 * Status of check for match/mismatch
	 *
	 * @var string - one of the status constants
	 */
	private $status;

	/**
	 * @param DataValue $localValue
	 * @param array $externalValues
	 * @param string $status
	 *
	 * @throws InvalidArgumentException
	 */
	public function __construct( DataValue $localValue, array $externalValues, $status ) {
		Assert::parameterElementType( 'DataValues\DataValue', $externalValues, '$externalValues' );

		$this->localValue = $localValue;
		$this->externalValues = $externalValues;
		$this->setStatus( $status );
	}

	/**
	 * @return DataValue
	 */
	public function getLocalValue() {
		return $this->localValue;
	}

	/**
	 * @return DataValue[]
	 */
	public function getExternalValues() {
		return $this->externalValues;
	}

	/**
	 * @return string
	 */
	public function getStatus() {
		return $this->status;
	}

	/**
	 * @param string $status
	 *
	 * @throws InvalidArgumentException
	 */
	private function setStatus( $status ) {
		Assert::parameterType( 'string', $status, '$status' );
		if ( !in_array(
			$status,
			array( self::STATUS_MATCH, self::STATUS_PARTIAL_MATCH, self::STATUS_MISMATCH )
		)
		) {
			throw new InvalidArgumentException( '$status must be one of the status constants.' );
		}

		$this->status = $status;
	}

}
