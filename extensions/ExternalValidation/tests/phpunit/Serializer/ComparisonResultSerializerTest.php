<?php

namespace WikibaseQuality\ExternalValidation\Tests\Serializer;

use WikibaseQuality\ExternalValidation\CrossCheck\Result\ComparisonResult;
use WikibaseQuality\ExternalValidation\Serializer\ComparisonResultSerializer;

/**
 * @covers WikibaseQuality\ExternalValidation\Serializer\IndexedTagsSerializer
 * @covers WikibaseQuality\ExternalValidation\Serializer\ComparisonResultSerializer
 *
 * @group WikibaseQualityExternalValidation
 *
 * @uses   WikibaseQuality\ExternalValidation\CrossCheck\Result\ComparisonResult
 *
 * @author BP2014N1
 * @license GNU GPL v2+
 */
class ComparisonResultSerializerTest extends SerializerTestBase {

	public function serializableProvider() {
		return array(
			array(
				new ComparisonResult(
					$this->getDataValueMock(),
					array(
						$this->getDataValueMock()
					),
					ComparisonResult::STATUS_MISMATCH
				)
			),
			array(
				new ComparisonResult(
					$this->getDataValueMock(),
					array(
						$this->getDataValueMock()
					),
					ComparisonResult::STATUS_MATCH
				)
			),
			array(
				new ComparisonResult(
					$this->getDataValueMock(),
					array(
						$this->getDataValueMock()
					),
					ComparisonResult::STATUS_PARTIAL_MATCH
				)
			)
		);
	}

	private function getDataValueMock() {
		return $this->getMock( 'DataValues\DataValue' );
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
					'localValue' => 'foobar',
					'externalValues' => array(
						'foobar'
					),
					'result' => ComparisonResult::STATUS_MISMATCH
				),
				new ComparisonResult(
					$this->getDataValueMock(),
					array(
						$this->getDataValueMock()
					),
					ComparisonResult::STATUS_MISMATCH
				)
			),
			array(
				array(
					'localValue' => 'foobar',
					'externalValues' => array(
						'foobar',
						'foobar'
					),
					'result' => ComparisonResult::STATUS_MATCH
				),
				new ComparisonResult(
					$this->getDataValueMock(),
					array(
						$this->getDataValueMock(),
						$this->getDataValueMock()
					),
					ComparisonResult::STATUS_MATCH
				)
			),
			array(
				array(
					'localValue' => 'foobar',
					'externalValues' => array(
						'foobar',
						'foobar'
					),
					'result' => ComparisonResult::STATUS_PARTIAL_MATCH
				),
				new ComparisonResult(
					$this->getDataValueMock(),
					array(
						$this->getDataValueMock(),
						$this->getDataValueMock()
					),
					ComparisonResult::STATUS_PARTIAL_MATCH
				)
			),
			array(
				array(
					'localValue' => 'foobar',
					'externalValues' => array(
						'foobar',
						'_element' => 'dataValue'
					),
					'result' => ComparisonResult::STATUS_MISMATCH
				),
				new ComparisonResult(
					$this->getDataValueMock(),
					array(
						$this->getDataValueMock()
					),
					ComparisonResult::STATUS_MISMATCH
				),
				array(
					'shouldIndexTags' => true
				)
			),
			array(
				array(
					'localValue' => 'foobar',
					'externalValues' => array(
						0 => 'foobar',
						1 => 'foobar',
						'_element' => 'dataValue'
					),
					'result' => ComparisonResult::STATUS_MATCH
				),
				new ComparisonResult(
					$this->getDataValueMock(),
					array(
						$this->getDataValueMock(),
						$this->getDataValueMock()
					),
					ComparisonResult::STATUS_MATCH
				),
				array(
					'shouldIndexTags' => true
				)
			),
			array(
				array(
					'localValue' => 'foobar',
					'externalValues' => array(
						0 => 'foobar',
						1 => 'foobar',
						'_element' => 'dataValue'
					),
					'result' => ComparisonResult::STATUS_PARTIAL_MATCH
				),
				new ComparisonResult(
					$this->getDataValueMock(),
					array(
						$this->getDataValueMock(),
						$this->getDataValueMock()
					),
					ComparisonResult::STATUS_PARTIAL_MATCH
				),
				array(
					'shouldIndexTags' => true
				)
			)
		);
	}

	protected function buildSerializer( $shouldIndexTags = false ) {
		$serializerMock = $this->getMock( 'Serializers\Serializer' );
		$serializerMock->expects( $this->any() )
		->method( 'serialize' )
		->will( $this->returnValue( 'foobar' ) );

		return new ComparisonResultSerializer( $serializerMock, $shouldIndexTags );
	}
}
