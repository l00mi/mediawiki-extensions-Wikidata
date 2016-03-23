<?php

namespace WikibaseQuality\ExternalValidation\Tests;

use Wikibase\Repo\WikibaseRepo;
use WikibaseQuality\ExternalValidation\CrossCheck\Comparer\DataValueComparerFactory;
use WikibaseQuality\ExternalValidation\CrossCheck\CrossChecker;
use WikibaseQuality\ExternalValidation\CrossCheck\CrossCheckInteractor;
use WikibaseQuality\ExternalValidation\DumpMetaInformation\DumpMetaInformationLookup;
use WikibaseQuality\ExternalValidation\DumpMetaInformation\DumpMetaInformationStore;
use WikibaseQuality\ExternalValidation\ExternalValidationServices;
use WikibaseQuality\ExternalValidation\Serializer\SerializerFactory;

/**
 * @covers WikibaseQuality\ExternalValidation\ExternalValidationServices
 *
 * @group WikibaseQualityExternalValidation
 *
 * @author BP2014N1
 * @license GNU GPL v2+
 */
class ExternalValidationServicesTest extends \MediaWikiTestCase {

	public function testGetDefaultInstance() {
		$this->assertInstanceOf(
			ExternalValidationServices::class,
			ExternalValidationServices::getDefaultInstance()
		);
	}

	public function testGetCrossChecker() {
		$crossChecker = $this->getFactory()->getCrossChecker();
		$this->assertInstanceOf( CrossChecker::class, $crossChecker );
	}

	public function testGetCrossCheckInteractor() {
		$crossCheckInteractor = $this->getFactory()->getCrossCheckInteractor();
		$this->assertInstanceOf( CrossCheckInteractor::class, $crossCheckInteractor );
	}

	public function testGetDataValueComparerFactory() {
		$dataValueComparerFactory = $this->getFactory()->getDataValueComparerFactory();
		$this->assertInstanceOf( DataValueComparerFactory::class, $dataValueComparerFactory );
	}

	public function testGetDumpMetaInformationLookup() {
		$dumpMetaInformationRepo = $this->getFactory()->getDumpMetaInformationLookup();
		$this->assertInstanceOf( DumpMetaInformationLookup::class, $dumpMetaInformationRepo );
	}

	public function testGetDumpMetaInformationStore() {
		$dumpMetaInformationRepo = $this->getFactory()->getDumpMetaInformationStore();
		$this->assertInstanceOf( DumpMetaInformationStore::class, $dumpMetaInformationRepo );
	}

	public function testGetSerializerFactory() {
		$serializerFactory = $this->getFactory()->getSerializerFactory();
		$this->assertInstanceOf( SerializerFactory::class, $serializerFactory );
	}

	private function getFactory() {
		return new ExternalValidationServices( WikibaseRepo::getDefaultInstance() );
	}

}
