<?php

namespace WikibaseQuality\ExternalValidation\Serializer;

use Serializers\DispatchableSerializer;
use Serializers\Exceptions\UnsupportedObjectException;
use Serializers\Serializer;
use WikibaseQuality\ExternalValidation\CrossCheck\Result\ComparisonResult;


/**
 * Class ComparisonResultSerializer
 *
 * @package WikibaseQuality\ExternalValidation\Serializer
 * @author BP2014N1
 * @license GNU GPL v2+
 */
class ComparisonResultSerializer extends IndexedTagsSerializer implements DispatchableSerializer {

	/**
	 * @var Serializer
	 */
	private $dataValueSerializer;

	/**
	 * @param Serializer $dataValueSerializer
	 * @param bool $shouldIndexTags
	 */
	public function __construct( Serializer $dataValueSerializer, $shouldIndexTags = false ) {
		parent::__construct( $shouldIndexTags );

		$this->dataValueSerializer = $dataValueSerializer;
	}

	/**
	 * @see DispatchableSerializer::isSerializerFor
	 *
	 * @param mixed $object
	 *
	 * @return bool
	 */
	public function isSerializerFor( $object ) {
		return $object instanceof ComparisonResult;
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
				'ComparisonResultSerializer can only serialize ComparisonResult objects.'
			);
		}

		return $this->getSerialized( $object );
	}

	private function getSerialized( ComparisonResult $comparisonResult ) {
		$dataValueSerializer = $this->dataValueSerializer;
		$externalValues = array_map(
			function ( $dataValue ) use ( $dataValueSerializer ) {
				return $dataValueSerializer->serialize( $dataValue );
			},
			$comparisonResult->getExternalValues()
		);
		$this->setIndexedTagName( $externalValues, 'dataValue' );

		return array(
			'localValue' => $this->dataValueSerializer->serialize( $comparisonResult->getLocalValue() ),
			'externalValues' => $externalValues,
			'result' => $comparisonResult->getStatus()
		);
	}
}