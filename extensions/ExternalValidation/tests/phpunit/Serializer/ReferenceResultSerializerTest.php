<?php

namespace WikibaseQuality\ExternalValidation\Tests\Serializer;

use WikibaseQuality\ExternalValidation\CrossCheck\Result\ReferenceResult;
use WikibaseQuality\ExternalValidation\Serializer\ReferenceResultSerializer;


/**
 * @covers WikibaseQuality\ExternalValidation\Serializer\ReferenceResultSerializer
 *
 * @group WikibaseQualityExternalValidation
 *
 * @uses   WikibaseQuality\ExternalValidation\CrossCheck\Result\ReferenceResult
 *
 * @author BP2014N1
 * @license GNU GPL v2+
 */
class ReferenceResultSerializerTest extends SerializerTestBase {

	public function serializableProvider() {
		return array(
			array(
				new ReferenceResult(
					ReferenceResult::STATUS_REFERENCES_STATED,
					$this->getReferenceMock()
				)
			)
		);
	}

	private function getReferenceMock() {
		return $this->getMock( 'Wikibase\DataModel\Reference' );
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
					'reference' => 'foobar',
					'status' => ReferenceResult::STATUS_REFERENCES_STATED
				),
				new ReferenceResult(
					ReferenceResult::STATUS_REFERENCES_STATED,
					$this->getReferenceMock()
				)
			),
			array(
				array(
					'reference' => 'foobar',
					'status' => ReferenceResult::STATUS_REFERENCES_MISSING
				),
				new ReferenceResult(
					ReferenceResult::STATUS_REFERENCES_MISSING,
					$this->getReferenceMock()
				)
			)
		);
	}

	protected function buildSerializer() {
		$serializerMock = $this->getMock( 'Serializers\Serializer' );
		$serializerMock->expects( $this->any() )
			->method( 'serialize' )
			->will( $this->returnValue( 'foobar' ) );

		return new ReferenceResultSerializer( $serializerMock );
	}
}
