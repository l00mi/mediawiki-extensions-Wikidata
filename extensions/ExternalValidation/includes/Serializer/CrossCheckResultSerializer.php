<?php

namespace WikibaseQuality\ExternalValidation\Serializer;

use Serializers\DispatchableSerializer;
use Serializers\Exceptions\UnsupportedObjectException;
use Serializers\Serializer;
use WikibaseQuality\ExternalValidation\CrossCheck\Result\CrossCheckResult;


/**
 * Class CrossCheckResultSerializer
 *
 * @package WikibaseQuality\ExternalValidation\Serializer
 * @author BP2014N1
 * @license GNU GPL v2+
 */
class CrossCheckResultSerializer implements DispatchableSerializer {

	/**
	 * @var Serializer
	 */
	private $dumpMetaInformationSerializer;

	/**
	 * @var Serializer
	 */
	private $comparisonResultSerializer;

	/**
	 * @var Serializer
	 */
	private $referenceResultSerializer;

	/**
	 * @param Serializer $dumpMetaInformationSerializer
	 * @param Serializer $comparisonResultSerializer
	 * @param Serializer $referenceResultSerializer
	 */
	public function __construct( Serializer $dumpMetaInformationSerializer, Serializer $comparisonResultSerializer, Serializer $referenceResultSerializer ) {
		$this->dumpMetaInformationSerializer = $dumpMetaInformationSerializer;
		$this->comparisonResultSerializer = $comparisonResultSerializer;
		$this->referenceResultSerializer = $referenceResultSerializer;
	}

	/**
	 * @see DispatchableSerializer::isSerializerFor
	 *
	 * @param mixed $object
	 *
	 * @return bool
	 */
	public function isSerializerFor( $object ) {
		return $object instanceof CrossCheckResult;
	}

	/**
	 * @see Serializer::serialize
	 *
	 * @param mixed $object
	 *
	 * @return array
	 * @throws UnsupportedObjectException
	 */
	public function serialize( $object ) {
		if ( !$this->isSerializerFor( $object ) ) {
			throw new UnsupportedObjectException(
				$object,
				'CrossCheckResultSerializer can only serialize CrossCheckResult objects.'
			);
		}

		return $this->getSerialized( $object );
	}

	private function getSerialized( CrossCheckResult $crossCheckResult ) {
		return array(
			'propertyId' => $crossCheckResult->getPropertyId()->getSerialization(),
			'claimGuid' => $crossCheckResult->getClaimGuid(),
			'externalId' => $crossCheckResult->getExternalId(),
			'dataSource' => $this->dumpMetaInformationSerializer->serialize( $crossCheckResult->getDumpMetaInformation() ),
			'comparisonResult' => $this->comparisonResultSerializer->serialize( $crossCheckResult->getComparisonResult() ),
			'referenceResult' => $this->referenceResultSerializer->serialize( $crossCheckResult->getReferenceResult() )
		);
	}
}