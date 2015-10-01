<?php

namespace WikibaseQuality\ExternalValidation\CrossCheck\Result;

use Wikibase\DataModel\Reference;
use InvalidArgumentException;
use Wikimedia\Assert\Assert;

/**
 * @package WikibaseQuality\ExternalValidation\CrossCheck\Result
 * @author BP2014N1
 * @license GNU GPL v2+
 */
class ReferenceResult {

	const STATUS_REFERENCES_MISSING = 'references-missing';
	const STATUS_REFERENCES_STATED = 'references-stated';

	/**
	 * Status that determines, whether references for specific statement are missing and can be added
	 *
	 * @var string
	 */
	private $status;

	/**
	 * Reference, that can be added to specific statement
	 *
	 * @var Reference
	 */
	private $reference;

	/**
	 * @param string $status
	 * @param Reference $reference
	 *
	 * @throws InvalidArgumentException
	 */
	public function __construct( $status, Reference $reference ) {

		$this->reference = $reference;
		$this->setStatus( $status );
	}

	/**
	 * @return Reference
	 */
	public function getReference() {
		return $this->reference;
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
			array( self::STATUS_REFERENCES_MISSING, self::STATUS_REFERENCES_STATED )
		)
		) {
			throw new InvalidArgumentException( '$status must be one of the status constants.' );
		}

		$this->status = $status;
	}

}
