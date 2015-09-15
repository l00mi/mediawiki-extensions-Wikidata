<?php

namespace WikibaseQuality\ExternalValidation;

use DataValues\Serializers\DataValueSerializer;
use Wikibase\Repo\ValueParserFactory;
use Wikibase\Repo\WikibaseRepo;
use Wikibase\StringNormalizer;
use WikibaseQuality\ExternalValidation\CrossCheck\Comparer\DataValueComparerFactory;
use WikibaseQuality\ExternalValidation\CrossCheck\CrossChecker;
use WikibaseQuality\ExternalValidation\CrossCheck\CrossCheckInteractor;
use WikibaseQuality\ExternalValidation\CrossCheck\ReferenceChecker;
use WikibaseQuality\ExternalValidation\CrossCheck\ValueParser\ComparativeValueParserFactory;
use WikibaseQuality\ExternalValidation\DumpMetaInformation\DumpMetaInformationLookup;
use WikibaseQuality\ExternalValidation\DumpMetaInformation\DumpMetaInformationStore;
use WikibaseQuality\ExternalValidation\DumpMetaInformation\SqlDumpMetaInformationRepo;
use WikibaseQuality\ExternalValidation\Serializer\SerializerFactory;


/**
 * Class ExternalValidationServices
 *
 * @package WikibaseQuality\ExternalValidation
 * @author BP2014N1
 * @license GNU GPL v2+
 */
class ExternalValidationServices
{

	/**
	 * @var WikibaseRepo
	 */
	private $wikibaseRepo;

	/**
	 * @var CrossChecker
	 */
	private $crossChecker;

	/**
	 * @var CrossCheckInteractor
	 */
	private $crossCheckInteractor;

	/**
	 * @var DataValueComparerFactory
	 */
	private $dataValueComparerFactory;

	/**
	 * @var ComparativeValueParserFactory
	 */
	private $comparativeValueParserFactory;

	/**
	 * @var DumpMetaInformationLookup
	 */
	private $dumpMetaInformationLookup;

	/**
	 * @var DumpMetaInformationStore
	 */
	private $dumpMetaInformationStore;

	/**
	 * @var ExternalDataRepo
	 */
	private $externalDataRepo;

	/**
	 * @var SerializerFactory
	 */
	private $serializerFactory;

	/**
	 * @param WikibaseRepo $wikibaseRepo
	 *
	 * @fixme inject specific things needed here instead of the WikibaseRepo factory. (T112105)
	 */
	public function __construct( WikibaseRepo $wikibaseRepo ) {
		$this->wikibaseRepo = $wikibaseRepo;
	}

	/**
	 * Returns the default instance.
	 * IMPORTANT: Use only when it is not feasible to inject an instance properly.
	 *
	 * @return ExternalValidationServices
	 */
	public static function getDefaultInstance()
	{
		static $instance = null;

		if ($instance === null) {
			$wikibaseRepo = WikibaseRepo::getDefaultInstance();

			$instance = new self( $wikibaseRepo );
		}

		return $instance;
	}

	/**
	 * @return CrossChecker
	 */
	public function getCrossChecker()
	{
		if ($this->crossChecker === null) {
			$this->crossChecker = new CrossChecker(
				$this->wikibaseRepo->getEntityLookup(),
				$this->getComparativeValueParserFactory(),
				$this->getDataValueComparerFactory()->newDispatchingDataValueComparer(),
				new ReferenceChecker(),
				$this->getDumpMetaInformationLookup(),
				$this->getExternalDataRepo()
			);
		}

		return $this->crossChecker;
	}

	/**
	 * @return CrossCheckInteractor
	 */
	public function getCrossCheckInteractor()
	{
		if ($this->crossCheckInteractor === null) {
			$this->crossCheckInteractor = new CrossCheckInteractor(
				$this->wikibaseRepo->getEntityLookup(),
				$this->wikibaseRepo->getStatementGuidParser(),
				$this->getCrossChecker());
		}

		return $this->crossCheckInteractor;
	}

	/**
	 * @return ComparativeValueParserFactory
	 */
	public function getComparativeValueParserFactory()
	{
		if ($this->comparativeValueParserFactory === null) {
			$this->comparativeValueParserFactory = new ComparativeValueParserFactory(
				$this->wikibaseRepo->getDataTypeDefinitions(),
				new StringNormalizer()
			);
		}

		return $this->comparativeValueParserFactory;
	}

	/**
	 * @return DataValueComparerFactory
	 */
	public function getDataValueComparerFactory()
	{
		if ($this->dataValueComparerFactory === null) {
			$this->dataValueComparerFactory = new DataValueComparerFactory(
				$this->wikibaseRepo->getStore()->getTermIndex(),
				$this->wikibaseRepo->getStringNormalizer()
			);
		}

		return $this->dataValueComparerFactory;
	}

	/**
	 * @return DumpMetaInformationLookup
	 */
	public function getDumpMetaInformationLookup()
	{
		if ($this->dumpMetaInformationLookup === null) {
			$this->dumpMetaInformationLookup = new SqlDumpMetaInformationRepo();
		}

		return $this->dumpMetaInformationLookup;
	}

	/**
	 * @return DumpMetaInformationStore
	 */
	public function getDumpMetaInformationStore()
	{
		if ($this->dumpMetaInformationStore === null) {
			$this->dumpMetaInformationStore = new SqlDumpMetaInformationRepo();
		}

		return $this->dumpMetaInformationStore;
	}

	/**
	 * @return ExternalDataRepo
	 */
	public function getExternalDataRepo()
	{
		if ($this->externalDataRepo === null) {
			$this->externalDataRepo = new ExternalDataRepo();
		}

		return $this->externalDataRepo;
	}

	/**
	 * @return SerializerFactory
	 */
	public function getSerializerFactory()
	{
		if ($this->serializerFactory === null) {
			$dataValueSerializer = new DataValueSerializer();
			$dataModelSerializerFactory = new \Wikibase\DataModel\SerializerFactory($dataValueSerializer);
			$this->serializerFactory = new SerializerFactory(
				$dataValueSerializer,
				$dataModelSerializerFactory->newReferenceSerializer()
			);
		}

		return $this->serializerFactory;
	}

}
