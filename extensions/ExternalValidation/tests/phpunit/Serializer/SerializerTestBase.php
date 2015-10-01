<?php

namespace WikibaseQuality\ExternalValidation\Tests\Serializer;

use ApiMain;
use ApiResult;
use FauxRequest;
use RequestContext;
use Serializers\DispatchableSerializer;
use Serializers\Serializer;

/**
 * @group WikibaseQualityExternalValidation
 *
 * @licence GNU GPL v2+
 * @author Thomas Pellissier Tanon
 */
abstract class SerializerTestBase extends \MediaWikiTestCase {

	public function testImplementsSerializerInterface() {
		$this->assertInstanceOf( 'Serializers\Serializer', $this->buildSerializer() );
	}

	/**
	 * @return Serializer
	 */
	protected abstract function buildSerializer();

	/**
	 * @dataProvider serializableProvider
	 */
	public function testIsSerializerForReturnsTrue( $serializable ) {
		$serializer = $this->buildSerializer();

		if ( $serializer instanceof DispatchableSerializer ) {
			$this->assertTrue( $serializer->isSerializerFor( $serializable ) );
		} else {
			$this->assertTrue( true );
		}
	}

	/**
	 * @return mixed[] things that are serialized by the serializer
	 */
	public abstract function serializableProvider();

	/**
	 * @dataProvider nonSerializableProvider
	 */
	public function testIsSerializerForReturnsFalse( $nonSerializable ) {
		$serializer = $this->buildSerializer();

		if ( $serializer instanceof DispatchableSerializer ) {
			$this->assertFalse( $serializer->isSerializerFor( $nonSerializable ) );
		} else {
			$this->assertTrue( true );
		}
	}

	/**
	 * @dataProvider nonSerializableProvider
	 */
	public function testSerializeThrowsUnsupportedObjectException( $nonSerializable ) {
		$this->setExpectedException( 'Serializers\Exceptions\UnsupportedObjectException' );
		$this->buildSerializer()->serialize( $nonSerializable );
	}

	/**
	 * @return mixed[] things that aren't serialized by the serializer
	 */
	public abstract function nonSerializableProvider();

	private function applySerializer( $object, $serializerParameter = array() ) {
		$serializer = call_user_func_array( array( $this, 'buildSerializer' ), $serializerParameter );
		$actual = $serializer->serialize( $object );
		return $actual;
	}

	private function fillApiResult( array $data, ApiResult $result ) {
		$_element = null;
		$_type = null;
		$_kvpkeyname = null;

		foreach ( $data as $key => $value ) {
			switch ( $key ) {
				case '_element':
					$_element = $value;
					break;

				case '_type':
					$_type = $value;
					break;

				case '_kvpkeyname':
					$_kvpkeyname = $value;
					break;

				default:
					$result->addValue( null, $key, $value );
			}
		}

		if ( $_element !== null ) {
			$result->addIndexedTagName( null, $_element );
		}

		if ( $_type !== null ) {
			$result->addArrayType( null, $_type, $_kvpkeyname );
		}
	}

	private function applyApiFormat( array $data, $format ) {
		// create a faux ApiMain and ApiFormatter module
		$context = new RequestContext();
		$context->setRequest( new FauxRequest( array(
			'format' => $format,
		) ) );

		$api = new ApiMain( $context );
		$printer = $api->createPrinterByName( $format );

		// build ApiResult
		$this->fillApiResult( $data, $printer->getResult() );

		// apply printer to result
		$printer->initPrinter();
		$printer->execute();

		$output = $printer->getBuffer();
		$printer->disable();

		return $output;
	}

	/**
	 * @dataProvider serializationProvider
	 */
	public function testSerialization( $expected, $object, $serializerParameter = array() ) {
		$actual = $this->applySerializer( $object, $serializerParameter );

		$this->assertEquals( $expected, $actual );
	}

	/**
	 * @dataProvider serializationJSONProvider
	 */
	public function testSerializationJSON( $expectedJson, $object, $serializerParameter = array() ) {
		$data = $this->applySerializer( $object, $serializerParameter );
		$json = $this->applyApiFormat( $data, 'json' );

		$this->assertJsonStringEqualsJsonString( $expectedJson, $json );
	}

	/**
	 * @dataProvider serializationXMLProvider
	 */
	public function testSerializationXML( $expectedXml, $object, $serializerParameter = array() ) {
		$data = $this->applySerializer( $object, $serializerParameter );
		$xml = $this->applyApiFormat( $data, 'xml' );

		$this->assertXmlStringEqualsXmlString( $expectedXml, $xml );
	}

	/**
	 * @return array an array of array( serialization, object to serialize)
	 */
	public abstract function serializationProvider();

	/**
	 * @return array an array of array( JSON, object to serialize)
	 */
	public abstract function serializationJSONProvider();

	/**
	 * @return array an array of array( XML, object to serialize)
	 */
	public abstract function serializationXMLProvider();

}
