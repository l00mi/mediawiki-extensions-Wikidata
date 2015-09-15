<?php

namespace WikibaseQuality\ExternalValidation\Tests;

use Wikibase\Repo\WikibaseRepo;
use WikibaseQuality\ExternalValidation\ExternalValidationServices;


/**
 * @covers WikibaseQuality\ExternalValidation\ExternalValidationServices
 *
 * @group WikibaseQualityExternalValidation
 *
 * @author BP2014N1
 * @license GNU GPL v2+
 */
class ExternalValidationServicesTest extends \MediaWikiTestCase
{

	public function testGetDefaultInstance()
	{
		$this->assertInstanceOf(
			'WikibaseQuality\ExternalValidation\ExternalValidationServices',
			ExternalValidationServices::getDefaultInstance()
		);
	}

	public function testGetCrossChecker()
	{
		$crossChecker = $this->getFactory()->getCrossChecker();

		$this->assertInstanceOf(
			'WikibaseQuality\ExternalValidation\CrossCheck\CrossChecker',
			$crossChecker
		);
	}

	public function testGetCrossCheckInteractor()
	{
		$crossCheckInteractor = $this->getFactory()->getCrossCheckInteractor();

		$this->assertInstanceOf(
			'WikibaseQuality\ExternalValidation\CrossCheck\CrossCheckInteractor',
			$crossCheckInteractor
		);
	}

	public function testGetDataValueComparerFactory()
	{
		$dataValueComparerFactory = $this->getFactory()->getDataValueComparerFactory();

		$this->assertInstanceOf(
			'WikibaseQuality\ExternalValidation\CrossCheck\Comparer\DataValueComparerFactory',
			$dataValueComparerFactory
		);
	}

	public function testGetDumpMetaInformationLookup()
	{
		$dumpMetaInformationRepo = $this->getFactory()->getDumpMetaInformationLookup();

		$this->assertInstanceOf(
			'WikibaseQuality\ExternalValidation\DumpMetaInformation\DumpMetaInformationLookup',
			$dumpMetaInformationRepo
		);
	}

	public function testGetDumpMetaInformationStore()
	{
		$dumpMetaInformationRepo = $this->getFactory()->getDumpMetaInformationStore();

		$this->assertInstanceOf(
			'WikibaseQuality\ExternalValidation\DumpMetaInformation\DumpMetaInformationStore',
			$dumpMetaInformationRepo
		);
	}

	public function testGetSerializerFactory()
	{
		$serializerFactory = $this->getFactory()->getSerializerFactory();

		$this->assertInstanceOf(
			'WikibaseQuality\ExternalValidation\Serializer\SerializerFactory',
			$serializerFactory
		);
	}

	private function getFactory()
	{
		return new ExternalValidationServices(
			WikibaseRepo::getDefaultInstance(),
			$this->getMockBuilder('Wikibase\Repo\ValueParserFactory')->disableOriginalConstructor()->getMock()
		);
	}
}
