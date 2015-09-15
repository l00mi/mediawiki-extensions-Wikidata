<?php

namespace WikibaseQuality\ExternalValidation\Serializer;

use Serializers\DispatchableSerializer;
use Serializers\Exceptions\UnsupportedObjectException;
use Serializers\Serializer;
use WikibaseQuality\ExternalValidation\CrossCheck\Result\ReferenceResult;


/**
 * Class ReferenceResultSerializer
 *
 * @package WikibaseQuality\ExternalValidation\Serializer
 * @author BP2014N1
 * @license GNU GPL v2+
 */
class ReferenceResultSerializer implements DispatchableSerializer {

	/**
	 * @var Serializer
	 */
	private $referenceSerializer;

	/**
	 * @param Serializer $referenceSerializer
	 */
	public function __construct( Serializer $referenceSerializer ) {
		$this->referenceSerializer = $referenceSerializer;
	}

	/**
	 * @see DispatchableSerializer::isSerializerFor
	 *
	 * @param mixed $object
	 *
	 * @return bool
	 */
	public function isSerializerFor( $object ) {
		return $object instanceof ReferenceResult;
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
				'ReferenceResultSerializer can only serialize ReferenceResult objects.'
			);
		}

		return $this->getSerialized( $object );
	}

	private function getSerialized( ReferenceResult $referenceResult ) {
		return array(
			'reference' => $this->referenceSerializer->serialize( $referenceResult->getReference() ),
			'status' => $referenceResult->getStatus()
		);
	}
}