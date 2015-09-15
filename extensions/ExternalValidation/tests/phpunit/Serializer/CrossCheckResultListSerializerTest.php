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

	protected function buildSerializer( $shouldIndexTags = false ) {
		$serializerMock = $this->getMock( 'Serializers\Serializer' );
		$serializerMock->expects( $this->any() )
			->method( 'serialize' )
			->will( $this->returnValue( 'foobar' ) );

		return new CrossCheckResultListSerializer( $serializerMock, $shouldIndexTags );
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
				array(),
				new CrossCheckResultList( array() )
			),
			array(
				array(
					'P42' => array(
						'foobar'
					)
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
						'foobar'
					),
					'P31' => array(
						'foobar'
					)
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
						'foobar'
					),
					'P31' => array(
						'foobar'
					)
				),
				new CrossCheckResultList(
					array(
						$this->getCrossCheckResultMock( new PropertyId( 'P42' ) ),
						$this->getCrossCheckResultMock( new PropertyId( 'P42' ) ),
						$this->getCrossCheckResultMock( new PropertyId( 'P31' ) )
					)
				)
			),
			array(
				array(
					0 => array(
						0 => 'foobar',
						1 => 'foobar',
						'id' => 'P42',
						'_element' => 'result'
					),
					1 => array(
						'foobar',
						'id' => 'P31',
						'_element' => 'result'
					),
					'_element' => 'property'
				),
				new CrossCheckResultList(
					array(
						$this->getCrossCheckResultMock( new PropertyId( 'P42' ) ),
						$this->getCrossCheckResultMock( new PropertyId( 'P42' ) ),
						$this->getCrossCheckResultMock( new PropertyId( 'P31' ) )
					)
				),
				array(
					'shouldIndexTags' => true
				)
			)
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
