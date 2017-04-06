<?php

namespace WikibaseQuality\ExternalValidation\Tests\Serializer;

use Serializers\Serializer;
use Wikibase\DataModel\Reference;
use WikibaseQuality\ExternalValidation\CrossCheck\Result\ReferenceResult;
use WikibaseQuality\ExternalValidation\Serializer\ReferenceResultSerializer;

/**
 * @covers \WikibaseQuality\ExternalValidation\Serializer\ReferenceResultSerializer
 *
 * @group WikibaseQualityExternalValidation
 *
 * @uses   \WikibaseQuality\ExternalValidation\CrossCheck\Result\ReferenceResult
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

	/**
	 * @return Reference
	 */
	private function getReferenceMock() {
		return $this->getMock( Reference::class );
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

	/**
	 * @return array an array of array( JSON, object to serialize)
	 */
	public function serializationJSONProvider() {
		return array(
			array(
				'{'
				. '"reference":"foobar",'
				. '"status":"references-missing"'
				. '}',
				new ReferenceResult(
					ReferenceResult::STATUS_REFERENCES_MISSING,
					$this->getReferenceMock()
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
				'<api'
				. '    reference="foobar" '
				. '    status="references-missing"'
				. '/>',
				new ReferenceResult(
					ReferenceResult::STATUS_REFERENCES_MISSING,
					$this->getReferenceMock()
				)
			),
		);
	}

	protected function buildSerializer() {
		$serializerMock = $this->getMock( Serializer::class );
		$serializerMock->expects( $this->any() )
			->method( 'serialize' )
			->will( $this->returnValue( 'foobar' ) );

		return new ReferenceResultSerializer( $serializerMock );
	}

}
