<?php

namespace WikibaseQuality\ExternalValidation\Tests\Serializer;

use DataValues\Serializers\DataValueSerializer;
use DataValues\StringValue;
use Serializers\Serializer;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Reference;
use Wikibase\DataModel\Serializers\ReferenceSerializer;
use Wikibase\DataModel\Serializers\SnakListSerializer;
use Wikibase\DataModel\Serializers\SnakSerializer;
use WikibaseQuality\ExternalValidation\CrossCheck\Result\ComparisonResult;
use WikibaseQuality\ExternalValidation\CrossCheck\Result\CrossCheckResult;
use WikibaseQuality\ExternalValidation\CrossCheck\Result\CrossCheckResultList;
use WikibaseQuality\ExternalValidation\CrossCheck\Result\ReferenceResult;
use WikibaseQuality\ExternalValidation\DumpMetaInformation\DumpMetaInformation;
use WikibaseQuality\ExternalValidation\Serializer\SerializerFactory;

/**
 * @covers \WikibaseQuality\ExternalValidation\Serializer\SerializerFactory
 *
 * @group WikibaseQualityExternalValidation
 *
 * @uses   \WikibaseQuality\ExternalValidation\CrossCheck\Result\ComparisonResult
 * @uses   \WikibaseQuality\ExternalValidation\CrossCheck\Result\ReferenceResult
 * @uses   \WikibaseQuality\ExternalValidation\CrossCheck\Result\CrossCheckResult
 * @uses   \WikibaseQuality\ExternalValidation\CrossCheck\Result\CrossCheckResultList
 * @uses   \WikibaseQuality\ExternalValidation\DumpMetaInformation\DumpMetaInformation
 * @uses   \WikibaseQuality\ExternalValidation\Serializer\IndexedTagsSerializer
 * @uses   \WikibaseQuality\ExternalValidation\Serializer\ComparisonResultSerializer
 * @uses   \WikibaseQuality\ExternalValidation\Serializer\ReferenceResultSerializer
 * @uses   \WikibaseQuality\ExternalValidation\Serializer\CrossCheckResultSerializer
 * @uses   \WikibaseQuality\ExternalValidation\Serializer\CrossCheckResultListSerializer
 * @uses   \WikibaseQuality\ExternalValidation\Serializer\DumpMetaInformationSerializer
 *
 * @author BP2014N1
 * @license GNU GPL v2+
 */
class SerializerFactoryTest extends \MediaWikiTestCase {

	// Test objects
	private $dumpMetaInformation;
	private $comparisonResult;
	private $referenceResult;
	private $crossCheckResult;
	private $crossCheckResultList;

	protected function setUp() {
		parent::setUp();

		// Create test data
		$this->dumpMetaInformation = new DumpMetaInformation(
			'foo',
			new ItemId( 'Q1' ),
			array( new PropertyId( 'P1' ) ),
			'20150101000000',
			'en',
			'http://www.foo.bar',
			42,
			new ItemId( 'Q6938433' )
		);
		$this->comparisonResult = new ComparisonResult(
			new StringValue( 'foobar' ),
			array(
				new StringValue( 'foobar' )
			),
			ComparisonResult::STATUS_MATCH
		);
		$this->referenceResult = new ReferenceResult(
			ReferenceResult::STATUS_REFERENCES_STATED,
			new Reference()
		);
		$this->crossCheckResult = new CrossCheckResult(
			new PropertyId( 'P42' ),
			'Q1$ca59482f-2b87-4849-afa2-a0dd0e521de4',
			'foobar',
			$this->dumpMetaInformation,
			$this->comparisonResult,
			$this->referenceResult
		);
		$this->crossCheckResultList = new CrossCheckResultList(
			array(
				$this->crossCheckResult
			)
		);
	}

	protected function tearDown() {
		unset(
			$this->dumpMetaInformation,
			$this->comparisonResult,
			$this->referenceResult,
			$this->crossCheckResult,
			$this->crossCheckResultList
		);
		parent::tearDown();
	}

	private function buildSerializerFactory() {
		$dataValueSerializer = new DataValueSerializer();
		$referenceSerializer = new ReferenceSerializer(
			new SnakListSerializer( new SnakSerializer( $dataValueSerializer ), false )
		);

		return new SerializerFactory( $dataValueSerializer, $referenceSerializer );
	}

	private function assertSerializesWithoutException( Serializer $serializer, $object ) {
		$serializer->serialize( $object );
		$this->assertTrue( true, 'No exception occurred during serialization' );
	}

	public function testNewDumpMetaInformationSerializer() {
		$this->assertSerializesWithoutException(
			$this->buildSerializerFactory()->newDumpMetaInformationSerializer(),
			$this->dumpMetaInformation
		);
	}

	public function testNewComparisonResultSerializer() {
		$this->assertSerializesWithoutException(
			$this->buildSerializerFactory()->newComparisonResultSerializer(),
			$this->comparisonResult
		);
	}

	public function testNewReferenceResultSerializer() {
		$this->assertSerializesWithoutException(
			$this->buildSerializerFactory()->newReferenceResultSerializer(),
			$this->referenceResult
		);
	}

	public function testNewCrossCheckResultSerializer() {
		$this->assertSerializesWithoutException(
			$this->buildSerializerFactory()->newCrossCheckResultSerializer(),
			$this->crossCheckResult
		);
	}

	public function testNewCrossCheckResultListSerializer() {
		$this->assertSerializesWithoutException(
			$this->buildSerializerFactory()->newCrossCheckResultListSerializer(),
			$this->crossCheckResultList
		);
	}

}
