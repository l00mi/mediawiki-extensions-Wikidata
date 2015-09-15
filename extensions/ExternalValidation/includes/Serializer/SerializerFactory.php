<?php

namespace WikibaseQuality\ExternalValidation\Serializer;

use Serializers\Serializer;

/**
 * Class SerializerFactory
 *
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
	 * @param bool $shouldIndexTags
	 * @return Serializer
	 */
	public function newCrossCheckResultListSerializer( $shouldIndexTags = false ) {
		return new CrossCheckResultListSerializer(
			$this->newCrossCheckResultSerializer( $shouldIndexTags ),
			$shouldIndexTags
		);
	}

	/**
	 * Returns a serializer that can serialize ComparisonResult objects
	 *
	 * @param bool $shouldIndexTags
	 * @return Serializer
	 */
	public function newComparisonResultSerializer( $shouldIndexTags = false ) {
		return new ComparisonResultSerializer( $this->dataValueSerializer, $shouldIndexTags );
	}

	/**
	 * Returns a serializer that can serialize DumpMetaInformation objects
	 *
	 * @param bool $shouldIndexTags
	 * @return Serializer
	 */
	public function newDumpMetaInformationSerializer( $shouldIndexTags = false ) {
		return new DumpMetaInformationSerializer( $shouldIndexTags );
	}

	/**
	 * Returns a serializer that can serialize CompareResult objects
	 *
	 * @param bool $shouldIndexTags
	 * @return Serializer
	 */
	public function newCrossCheckResultSerializer( $shouldIndexTags = false ) {
		return new CrossCheckResultSerializer(
			$this->newDumpMetaInformationSerializer( $shouldIndexTags ),
			$this->newComparisonResultSerializer( $shouldIndexTags ),
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