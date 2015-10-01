<?php

namespace WikibaseQuality\ExternalValidation\Serializer;

use Serializers\Serializer;

/**
 * @package WikibaseQuality\ExternalValidation\Serializer
 * @author BP2014N1
 * @license GNU GPL v2+
 */
class SerializerFactory {

	/**
	 * @var Serializer
	 */
	private $dataValueSerializer;

	/**
	 * @var Serializer
	 */
	private $referenceSerializer;

	/**
	 * @param Serializer $dataValueSerializer
	 * @param Serializer $referenceSerializer
	 */
	public function __construct( Serializer $dataValueSerializer, Serializer $referenceSerializer ) {
		$this->dataValueSerializer = $dataValueSerializer;
		$this->referenceSerializer = $referenceSerializer;
	}

	/**
	 * Returns a serializer that can serialize CrossCheckResultList objects
	 *
	 * @return Serializer
	 */
	public function newCrossCheckResultListSerializer() {
		return new CrossCheckResultListSerializer(
			$this->newCrossCheckResultSerializer()
		);
	}

	/**
	 * Returns a serializer that can serialize ComparisonResult objects
	 *
	 * @return Serializer
	 */
	public function newComparisonResultSerializer() {
		return new ComparisonResultSerializer( $this->dataValueSerializer );
	}

	/**
	 * Returns a serializer that can serialize DumpMetaInformation objects
	 *
	 * @return Serializer
	 */
	public function newDumpMetaInformationSerializer() {
		return new DumpMetaInformationSerializer();
	}

	/**
	 * Returns a serializer that can serialize CompareResult objects
	 *
	 * @return Serializer
	 */
	public function newCrossCheckResultSerializer() {
		return new CrossCheckResultSerializer(
			$this->newDumpMetaInformationSerializer(),
			$this->newComparisonResultSerializer(),
			$this->newReferenceResultSerializer()
		);
	}

	/**
	 * Returns a serializer that can serialize ReferenceResult objects
	 *
	 * @return Serializer
	 */
	public function newReferenceResultSerializer() {
		return new ReferenceResultSerializer( $this->referenceSerializer );
	}

}
