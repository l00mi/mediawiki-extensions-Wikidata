<?php

namespace WikibaseQuality\Tests\Helper;

use DataValues\Deserializers\DataValueDeserializer;
use Deserializers\Deserializer;
use Wikibase\DataModel\DeserializerFactory;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\Lib\Store\EntityLookup;
use Wikibase\Repo\WikibaseRepo;


class JsonFileEntityLookup implements EntityLookup {

	/**
	 * Base dir which contains serialized entities as json files.
	 *
	 * @var string
	 */
	private $baseDir;

	/**
	 * @var Deserializer
	 */
	private $entityDeserializer;

	/**
	 * @param string $baseDir
	 */
	public function __construct( $baseDir ) {
		$this->baseDir = $baseDir;

		$factory = new DeserializerFactory(
			new DataValueDeserializer(
				array(
					'boolean' => 'DataValues\BooleanValue',
					'number' => 'DataValues\NumberValue',
					'string' => 'DataValues\StringValue',
					'unknown' => 'DataValues\UnknownValue',
					'globecoordinate' => 'DataValues\GlobeCoordinateValue',
					'monolingualtext' => 'DataValues\MonolingualTextValue',
					'multilingualtext' => 'DataValues\MultilingualTextValue',
					'quantity' => 'DataValues\QuantityValue',
					'time' => 'DataValues\TimeValue',
					'wikibase-entityid' => 'Wikibase\DataModel\Entity\EntityIdValue',
				)
			),
			WikibaseRepo::getDefaultInstance()->getEntityIdParser()
		);

		$this->entityDeserializer = $factory->newEntityDeserializer();
	}

	/**
	 * Returns the entity with the provided id or null if there is no such entity.
	 *
	 * @param EntityId $entityId
	 *
	 * @return EntityDocument|null
	 */
	public function getEntity( EntityId $entityId ) {
		if ( !$this->hasEntity( $entityId ) ) {
			return null;
		}

		$filePath = $this->buildFilePath( $entityId );
		$serializedEntity = json_decode( file_get_contents( $filePath ), true );

		if ( $serializedEntity === null ) {
			return null;
		}


		return $this->entityDeserializer->deserialize( $serializedEntity );
	}

	/**
	 * Returns whether the given entity can bee looked up using getEntity().
	 *
	 * @param EntityId $entityId
	 *
	 * @return boolean
	 */
	public function hasEntity( EntityId $entityId ) {
		return file_exists( $this->buildFilePath( $entityId ) );
	}

	/**
	 * Returns path of the file, which contains the serialized entity.
	 *
	 * @param EntityId $entityId
	 *
	 * @return string
	 */
	private function buildFilePath( EntityId $entityId ) {
		$filePath = sprintf( '%s/%s.json', $this->baseDir, (string)$entityId );
		return $filePath;
	}
}