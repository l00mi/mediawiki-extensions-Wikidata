<?php

namespace WikibaseQuality\ExternalValidation\Tests\Serializer;

use Wikibase\DataModel\Entity\PropertyId;
use WikibaseQuality\ExternalValidation\CrossCheck\Result\CrossCheckResultList;
use WikibaseQuality\ExternalValidation\Serializer\CrossCheckResultListSerializer;


/**
 * @covers WikibaseQuality\ExternalValidation\Serializer\IndexedTagsSerializer
 * @covers WikibaseQuality\ExternalValidation\Serializer\CrossCheckResultListSerializer
 *
 * @group WikibaseQualityExternalValidation
 *
 * @uses   WikibaseQuality\ExternalValidation\CrossCheck\Result\CrossCheckResultList
 *
 * @author BP2014N1
 * @license GNU GPL v2+
 */
class CrossCheckResultListSerializerTest extends SerializerTestBase {

	protected function buildSerializer() {
		$serializerMock = $this->getMock( 'Serializers\Serializer' );
		$serializerMock->expects( $this->any() )
			->method( 'serialize' )
			->will( $this->returnValue( 'foobar' ) );

		return new CrossCheckResultListSerializer( $serializerMock );
	}

	public function serializableProvider() {
		return array(
			array(
				new CrossCheckResultList(
					array(
						$this->getCrossCheckResultMock( new PropertyId( 'P42' ) )
					)
				)
			)
		);
	}

	public function nonSerializableProvider() {
		return array(
			array(
				42
			),
			array(
				array()
			)
		);
	}

	public function serializationProvider() {
		return array(
			array(
				array(
					'_element' => 'property',
					'_type' => 'kvp',
					'_kvpkeyname' => 'id',
				),
				new CrossCheckResultList( array() )
			),
			array(
				array(
					'P42' => array(
						'foobar',
						'_element' => 'result'
					),
					'_element' => 'property',
					'_type' => 'kvp',
					'_kvpkeyname' => 'id',
				),
				new CrossCheckResultList(
					array(
						$this->getCrossCheckResultMock( new PropertyId( 'P42' ) )
					)
				)
			),
			array(
				array(
					'P42' => array(
						'foobar',
						'_element' => 'result'
					),
					'P31' => array(
						'foobar',
						'_element' => 'result'
					),
					'_element' => 'property',
					'_type' => 'kvp',
					'_kvpkeyname' => 'id',
				),
				new CrossCheckResultList(
					array(
						$this->getCrossCheckResultMock( new PropertyId( 'P42' ) ),
						$this->getCrossCheckResultMock( new PropertyId( 'P31' ) )
					)
				)
			),
			array(
				array(
					'P42' => array(
						'foobar',
						'foobar',
						'_element' => 'result'
					),
					'P31' => array(
						'foobar',
						'_element' => 'result'
					),
					'_element' => 'property',
					'_type' => 'kvp',
					'_kvpkeyname' => 'id',
				),
				new CrossCheckResultList(
					array(
						$this->getCrossCheckResultMock( new PropertyId( 'P42' ) ),
						$this->getCrossCheckResultMock( new PropertyId( 'P42' ) ),
						$this->getCrossCheckResultMock( new PropertyId( 'P31' ) )
					)
				)
			),
		);
	}

	/**
	 * @return array an array of array( JSON, object to serialize)
	 */
	public function serializationJSONProvider() {
		return array(
			array(
				'{'
				. '"P42":["foobar","foobar"],'
				. '"P31":["foobar"]'
				. '}',
				new CrossCheckResultList(
					array(
						$this->getCrossCheckResultMock( new PropertyId( 'P42' ) ),
						$this->getCrossCheckResultMock( new PropertyId( 'P42' ) ),
						$this->getCrossCheckResultMock( new PropertyId( 'P31' ) )
					)
				)
			),
		);
	}

	/**
	 * @return array an array of array( XML, object to serialize)
	 */
	public function serializationXMLProvider() {
		return array(
			array(
				'<api>'
				. '    <property id="P42">'
				. '      <result>foobar</result>'
				. '      <result>foobar</result>'
				. '    </property>'
				. '    <property id="P31">'
				. '      <result>foobar</result>'
				. '    </property>'
				. '</api>',
				new CrossCheckResultList(
					array(
						$this->getCrossCheckResultMock( new PropertyId( 'P42' ) ),
						$this->getCrossCheckResultMock( new PropertyId( 'P42' ) ),
						$this->getCrossCheckResultMock( new PropertyId( 'P31' ) )
					)
				)
			),
		);
	}

	private function getCrossCheckResultMock( PropertyId $propertyId ) {
		$mock = $this
			->getMockBuilder( 'WikibaseQuality\ExternalValidation\CrossCheck\Result\CrossCheckResult' )
			->disableOriginalConstructor()
			->getMock();
		$mock->expects( $this->any() )
			->method( 'getPropertyId' )
			->will( $this->returnValue( $propertyId ) );

		return $mock;
	}

}
