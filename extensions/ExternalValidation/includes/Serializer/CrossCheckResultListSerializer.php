<?php

namespace WikibaseQuality\ExternalValidation\Serializer;

use Serializers\DispatchableSerializer;
use Serializers\Exceptions\UnsupportedObjectException;
use Serializers\Serializer;
use WikibaseQuality\ExternalValidation\CrossCheck\Result\CrossCheckResultList;

/**
 * @package WikibaseQuality\ExternalValidation\Serializer
 * @author BP2014N1
 * @license GNU GPL v2+
 */
class CrossCheckResultListSerializer extends IndexedTagsSerializer implements DispatchableSerializer {

	/**
	 * @var Serializer
	 */
	private $crossCheckResultSerializer;

	/**
	 * @param Serializer $crossCheckResultSerializer
	 */
	public function __construct( Serializer $crossCheckResultSerializer ) {
		$this->crossCheckResultSerializer = $crossCheckResultSerializer;
	}

	/**
	 * @see DispatchableSerializer::isSerializerFor
	 *
	 * @param mixed $object
	 *
	 * @return bool
	 */
	public function isSerializerFor( $object ) {
		return $object instanceof CrossCheckResultList;
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
				'CrossCheckResultListSerializer can only serialize CrossCheckResultList objects.'
			);
		}

		return $this->getSerialized( $object );
	}

	private function getSerialized( CrossCheckResultList $resultList ) {
		$serialization = array();
		$this->setKeyAttributeName( $serialization, 'property', 'id' );

		foreach ( $resultList->getPropertyIds() as $propertyId ) {
			$id = $propertyId->getSerialization();
			$resultSerialization = array();

			$this->setIndexedTagName( $resultSerialization, 'result' );

			foreach ( $resultList->getByPropertyId( $propertyId ) as $result ) {
				$resultSerialization[] = $this->crossCheckResultSerializer->serialize( $result );
			}

			$serialization[$id] = $resultSerialization;
		}

		return $serialization;
	}

}
