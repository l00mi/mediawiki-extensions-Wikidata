<?php

namespace WikibaseQuality\ExternalValidation\Serializer;

use Serializers\DispatchableSerializer;
use Serializers\Exceptions\UnsupportedObjectException;
use Serializers\Serializer;
use WikibaseQuality\ExternalValidation\CrossCheck\Result\CrossCheckResultList;


/**
 * Class CrossCheckResultListSerializer
 *
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
	 * @param bool $shouldIndexTags
	 */
	public function __construct( Serializer $crossCheckResultSerializer, $shouldIndexTags = false ) {
		parent::__construct( $shouldIndexTags );

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
		foreach ( $resultList->getPropertyIds() as $propertyId ) {
			if ( $this->shouldIndexTags() ) {
				$index = count( $serialization );
				$serialization[$index]['id'] = $propertyId->getSerialization();
				$this->setIndexedTagName( $serialization[$index], 'result' );
			} else {
				$index = (string)$propertyId;
			}

			foreach ( $resultList->getByPropertyId( $propertyId ) as $result ) {
				$serialization[ $index ][] = $this->crossCheckResultSerializer->serialize( $result );
			}
		}

		$this->setIndexedTagName( $serialization, 'property' );

		return $serialization;
	}
}