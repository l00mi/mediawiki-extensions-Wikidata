<?php

namespace DataValues\Deserializers;

use Deserializers\Deserializer;
use Deserializers\Exceptions\DeserializationException;
use Deserializers\Exceptions\MissingAttributeException;
use Deserializers\Exceptions\MissingTypeException;
use Deserializers\Exceptions\UnsupportedTypeException;
use InvalidArgumentException;
use RuntimeException;

/**
 * @since 0.1
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class DataValueDeserializer implements Deserializer {

	const TYPE_KEY = 'type';
	const VALUE_KEY = 'value';

	private $classes;
	private $serialization;

	public function __construct( array $dataValueClasses = array() ) {
		$this->assertAreDataValueClasses( $dataValueClasses );
		$this->classes = $dataValueClasses;
	}

	private function assertAreDataValueClasses( array $classes ) {
		foreach ( $classes as $typeId => $class ) {
			if ( !is_string( $typeId ) || !$this->isDataValueClass( $class ) ) {
				throw new InvalidArgumentException( '$dataValueClasses can only contain dataTypeId => dataValueClass' );
			}
		}
	}

	private function isDataValueClass( $class ) {
		return is_string( $class ) && $this->classDerivesFrom( $class, 'DataValues\DataValue' );
	}

	private function classDerivesFrom( $class, $superClass ) {
		return class_exists( $class ) && in_array( $superClass, class_implements( $class ) );
	}

	/**
	 * @see Deserializer::isDeserializerFor
	 */
	public function isDeserializerFor( $serialization ) {
		$this->serialization = $serialization;

		try {
			$this->assertCanDeserialize();
			return true;
		}
		catch ( RuntimeException $ex ) {
			return false;
		}
	}

	/**
	 * @see Deserializer::deserialize
	 */
	public function deserialize( $serialization ) {
		$this->serialization = $serialization;

		$this->assertCanDeserialize();
		return $this->getDeserialization();
	}

	private function assertCanDeserialize() {
		$this->assertHasSupportedType();
		$this->assertHasValue();
	}

	private function assertHasSupportedType() {
		if ( !is_array( $this->serialization ) || !array_key_exists( self::TYPE_KEY, $this->serialization ) ) {
			throw new MissingTypeException();
		}

		if ( !$this->hasSupportedType() ) {
			throw new UnsupportedTypeException( $this->getType() );
		}
	}

	private function assertHasValue() {
		if ( !array_key_exists( self::VALUE_KEY, $this->serialization ) ) {
			throw new MissingAttributeException( self::VALUE_KEY );
		}
	}

	private function hasSupportedType() {
		return array_key_exists( $this->getType(), $this->classes );
	}

	private function getType() {
		return $this->serialization[self::TYPE_KEY];
	}

	private function getDeserialization() {
		$class = $this->getClass();

		try {
			return $class::newFromArray( $this->getValue() );
		}
		catch ( InvalidArgumentException $ex ) {
			throw new DeserializationException( $ex->getMessage(), $ex );
		}
	}

	private function getClass() {
		return $this->classes[$this->getType()];
	}

	private function getValue() {
		return $this->serialization[self::VALUE_KEY];
	}

}