<?php

namespace WikibaseQuality\ExternalValidation\Serializer;

use Serializers\DispatchableSerializer;
use Serializers\Exceptions\UnsupportedObjectException;
use Wikibase\DataModel\Entity\PropertyId;
use WikibaseQuality\ExternalValidation\DumpMetaInformation\DumpMetaInformation;

/**
 * @package WikibaseQuality\ExternalValidation\Serializer
 * @author BP2014N1
 * @license GNU GPL v2+
 */
class DumpMetaInformationSerializer extends IndexedTagsSerializer implements DispatchableSerializer {

	/**
	 * @see DispatchableSerializer::isSerializerFor
	 *
	 * @param mixed $object
	 *
	 * @return bool
	 */
	public function isSerializerFor( $object ) {
		return $object instanceof DumpMetaInformation;
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
				'DumpMetaInformationSerializer can only serialize DumpMetaInformation objects.'
			);
		}

		return $this->getSerialized( $object );
	}

	private function getSerialized( DumpMetaInformation $dumpMetaInformation ) {
		$identifierPropertyIds = array_map(
			function ( PropertyId $propertyId ) {
				return $propertyId->getSerialization();
			},
			$dumpMetaInformation->getIdentifierPropertyIds()
		);

		$this->setIndexedTagName( $identifierPropertyIds, 'propertyId' );

		return array(
			'dumpId' => $dumpMetaInformation->getDumpId(),
			'sourceItemId' => $dumpMetaInformation->getSourceItemId()->getSerialization(),
			'identifierPropertyIds' => $identifierPropertyIds,
			'importDate' => wfTimestamp( TS_ISO_8601, $dumpMetaInformation->getImportDate() ),
			'language' => $dumpMetaInformation->getLanguageCode(),
			'sourceUrl' => $dumpMetaInformation->getSourceUrl(),
			'size' => $dumpMetaInformation->getSize(),
			'licenseItemId' => $dumpMetaInformation->getLicenseItemId()->getSerialization()
		);
	}

}
