<?php

namespace WikibaseQuality\ExternalValidation\Tests\Serializer;

use Serializers\Serializer;
use Wikibase\DataModel\Entity\PropertyId;
use WikibaseQuality\ExternalValidation\CrossCheck\Result\ComparisonResult;
use WikibaseQuality\ExternalValidation\CrossCheck\Result\CrossCheckResult;
use WikibaseQuality\ExternalValidation\CrossCheck\Result\ReferenceResult;
use WikibaseQuality\ExternalValidation\DumpMetaInformation\DumpMetaInformation;
use WikibaseQuality\ExternalValidation\Serializer\CrossCheckResultSerializer;

/**
 * @covers WikibaseQuality\ExternalValidation\Serializer\CrossCheckResultSerializer
 *
 * @group WikibaseQualityExternalValidation
 *
 * @uses   WikibaseQuality\ExternalValidation\CrossCheck\Result\CrossCheckResult
 *
 * @author BP2014N1
 * @license GNU GPL v2+
 */
class CrossCheckResultSerializerTest extends SerializerTestBase {

	protected function buildSerializer() {
		$serializerMock = $this->getMock( Serializer::class );
		$serializerMock->expects( $this->any() )
			->method( 'serialize' )
			->will( $this->returnValue( 'foobar' ) );

		return new CrossCheckResultSerializer( $serializerMock, $serializerMock, $serializerMock );
	}

	public function serializableProvider() {
		return array(
			array(
				new CrossCheckResult(
					new PropertyId( 'P42' ),
					'Q42$26ca5e18-90fb-4c5c-bb22-ed8a70f1948f',
					'foobar',
					$this->getMockWithoutConstructor( DumpMetaInformation::class ),
					$this->getMockWithoutConstructor( ComparisonResult::class ),
					$this->getMockWithoutConstructor( ReferenceResult::class )
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
					'propertyId' => 'P42',
					'claimGuid' => 'Q42$26ca5e18-90fb-4c5c-bb22-ed8a70f1948f',
					'externalId' => 'fubar',
					'dataSource' => 'foobar',
					'comparisonResult' => 'foobar',
					'referenceResult' => 'foobar'
				),
				new CrossCheckResult(
					new PropertyId( 'P42' ),
					'Q42$26ca5e18-90fb-4c5c-bb22-ed8a70f1948f',
					'fubar',
					$this->getMockWithoutConstructor( DumpMetaInformation::class ),
					$this->getMockWithoutConstructor( ComparisonResult::class ),
					$this->getMockWithoutConstructor( ReferenceResult::class )
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
				. '"propertyId":"P42",'
				. '"claimGuid":"Q42$26ca5e18-90fb-4c5c-bb22-ed8a70f1948f",'
				. '"externalId":"fubar",'
				. '"dataSource":"foobar",'
				. '"comparisonResult":"foobar",'
				. '"referenceResult":"foobar"'
				. '}',
				new CrossCheckResult(
					new PropertyId( 'P42' ),
					'Q42$26ca5e18-90fb-4c5c-bb22-ed8a70f1948f',
					'fubar',
					$this->getMockWithoutConstructor( DumpMetaInformation::class ),
					$this->getMockWithoutConstructor( ComparisonResult::class ),
					$this->getMockWithoutConstructor( ReferenceResult::class )
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
				. '    propertyId="P42" '
				. '    claimGuid="Q42$26ca5e18-90fb-4c5c-bb22-ed8a70f1948f"'
				. '    externalId="fubar"'
				. '    dataSource="foobar"'
				. '    comparisonResult="foobar"'
				. '    referenceResult="foobar"'
				. '/>',
				new CrossCheckResult(
					new PropertyId( 'P42' ),
					'Q42$26ca5e18-90fb-4c5c-bb22-ed8a70f1948f',
					'fubar',
					$this->getMockWithoutConstructor( DumpMetaInformation::class ),
					$this->getMockWithoutConstructor( ComparisonResult::class ),
					$this->getMockWithoutConstructor( ReferenceResult::class )
				)
			),
		);
	}

	private function getMockWithoutConstructor( $className ) {
		return $this->getMockBuilder( $className )->disableOriginalConstructor()->getMock();
	}

}
