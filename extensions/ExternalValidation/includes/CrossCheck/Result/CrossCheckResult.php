<?php

namespace WikibaseQuality\ExternalValidation\CrossCheck\Result;

use InvalidArgumentException;
use Wikibase\DataModel\Entity\PropertyId;
use WikibaseQuality\ExternalValidation\DumpMetaInformation\DumpMetaInformation;
use Wikimedia\Assert\Assert;


/**
 * Class CrossCheckResult
 *
 * @package WikibaseQuality\ExternalValidation\CrossCheck\Result
 * @author BP2014N1
 * @license GNU GPL v2+
 */
class CrossCheckResult {

	/**
	 * Id of the property of the claim, that was checked
	 *
	 * @var PropertyId
	 */
	private $propertyId;

	/**
	 * Id of the claim, that was checked
	 *
	 * @var string
	 */
	private $claimGuid;

	/**
	 * Id of the entity of the external data source
	 *
	 * @var string
	 */
	private $externalId;

	/**
	 * Meta information about the data source
	 *
	 * @var DumpMetaInformation
	 */
	private $dumpMetaInformation;

	/**
	 * Result of data comparison
	 *
	 * @var ComparisonResult
	 */
	private $comparisonResult;

	/**
	 * Result of references check
	 *
	 * @var ReferenceResult
	 */
	private $referenceResult;

	/**
	 * @param PropertyId $propertyId
	 * @param string $claimGuid
	 * @param string $externalId
	 * @param DumpMetaInformation $dumpMetaInformation
	 * @param ComparisonResult $comparisonResult
	 * @param ReferenceResult $referenceResult
	 * @throws InvalidArgumentException
	 */
	public function __construct( PropertyId $propertyId,
								 $claimGuid,
								 $externalId,
								 DumpMetaInformation $dumpMetaInformation,
								 ComparisonResult $comparisonResult,
								 ReferenceResult $referenceResult ) {
		Assert::parameterType( 'string', $claimGuid, '$claimGuid' );
		Assert::parameterType( 'string', $externalId, '$externalId' );

		$this->propertyId = $propertyId;
		$this->claimGuid = $claimGuid;
		$this->externalId = $externalId;
		$this->dumpMetaInformation = $dumpMetaInformation;
		$this->comparisonResult = $comparisonResult;
		$this->referenceResult = $referenceResult;
	}

	/**
	 * @return PropertyId
	 */
	public function getPropertyId() {
		return $this->propertyId;
	}

	/**
	 * @return string
	 */
	public function getClaimGuid() {
		return $this->claimGuid;
	}

	/**
	 * @return string
	 */
	public function getExternalId() {
		return $this->externalId;
	}

	/**
	 * @return DumpMetaInformation
	 */
	public function getDumpMetaInformation() {
		return $this->dumpMetaInformation;
	}

	/**
	 * @return ComparisonResult
	 */
	public function getComparisonResult() {
		return $this->comparisonResult;
	}

	/**
	 * @return ReferenceResult
	 */
	public function getReferenceResult() {
		return $this->referenceResult;
	}
}