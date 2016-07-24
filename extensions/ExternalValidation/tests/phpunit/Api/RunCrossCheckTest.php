<?php

namespace WikibaseQuality\ExternalValidation\Tests\Api;

use DataValues\StringValue;
use UsageException;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\EntityIdValue;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Services\Statement\V4GuidGenerator;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\DataModel\Statement\StatementGuid;
use Wikibase\Repo\WikibaseRepo;
use Wikibase\Test\Repo\Api\WikibaseApiTestCase;
use WikibaseQuality\ExternalValidation\DumpMetaInformation\SqlDumpMetaInformationRepo;
use WikibaseQuality\ExternalValidation\ExternalDataRepo;

/**
 * @covers WikibaseQuality\ExternalValidation\Api\RunCrossCheck
 *
 * @group WikibaseQualityExternalValidation
 * @group Database
 * @group API
 * @group medium
 *
 * @uses   WikibaseQuality\ExternalValidation\DumpMetaInformation\DumpMetaInformation
 * @uses   WikibaseQuality\ExternalValidation\CrossCheck\CrossChecker
 * @uses   WikibaseQuality\ExternalValidation\CrossCheck\ReferenceChecker
 * @uses   WikibaseQuality\ExternalValidation\CrossCheck\Comparer\DataValueComparer
 * @uses   WikibaseQuality\ExternalValidation\CrossCheck\Comparer\StringValueComparer
 * @uses   WikibaseQuality\ExternalValidation\CrossCheck\Result\ComparisonResult
 * @uses   WikibaseQuality\ExternalValidation\CrossCheck\Result\ReferenceResult
 * @uses   WikibaseQuality\ExternalValidation\CrossCheck\Result\CrossCheckResult
 * @uses   WikibaseQuality\ExternalValidation\CrossCheck\Result\CrossCheckResultList
 * @uses   WikibaseQuality\ExternalValidation\Serializer\IndexedTagsSerializer
 * @uses   WikibaseQuality\ExternalValidation\Serializer\ComparisonResultSerializer
 * @uses   WikibaseQuality\ExternalValidation\Serializer\ReferenceResultSerializer
 * @uses   WikibaseQuality\ExternalValidation\Serializer\CrossCheckResultSerializer
 * @uses   WikibaseQuality\ExternalValidation\Serializer\CrossCheckResultListSerializer
 * @uses   WikibaseQuality\ExternalValidation\Serializer\DumpMetaInformationSerializer
 * @uses   WikibaseQuality\ExternalValidation\Serializer\SerializerFactory
 *
 * @author BP2014N1
 * @license GNU GPL v2+
 */
class RunCrossCheckTest extends WikibaseApiTestCase {

	/**
	 * Id of a item that (hopefully) does not exist.
	 */
	const NOT_EXISTENT_ITEM_ID = 'Q2147483647';

	/** @var EntityId[] */
	private static $idMap;

	/**
	 * @var array
	 */
	private static $claimGuids = array();

	/** @var bool */
	private static $hasSetup;

	protected function setUp() {
		parent::setUp();

		$this->tablesUsed[] = SqlDumpMetaInformationRepo::META_TABLE_NAME;
		$this->tablesUsed[] = SqlDumpMetaInformationRepo::IDENTIFIER_PROPERTIES_TABLE_NAME;
		$this->tablesUsed[] = ExternalDataRepo::TABLE_NAME;
	}

	public function addDBData() {
		if ( !self::$hasSetup ) {
			$store = WikibaseRepo::getDefaultInstance()->getEntityStore();

			$propertyP1 = Property::newFromType( 'string' );
			$store->saveEntity( $propertyP1, 'TestEntityP1', $GLOBALS['wgUser'], EDIT_NEW );
			self::$idMap['P1'] = $propertyP1->getId();

			$propertyP2 = Property::newFromType( 'string' );
			$store->saveEntity( $propertyP2, 'TestEntityP2', $GLOBALS['wgUser'], EDIT_NEW );
			self::$idMap['P2'] = $propertyP2->getId();

			$propertyP3 = Property::newFromType( 'string' );
			$store->saveEntity( $propertyP3, 'TestEntityP3', $GLOBALS['wgUser'], EDIT_NEW );
			self::$idMap['P3'] = $propertyP3->getId();

			$itemQ1 = new Item();
			$store->saveEntity( $itemQ1, 'TestEntityQ1', $GLOBALS['wgUser'], EDIT_NEW );
			self::$idMap['Q1'] = $itemQ1->getId();

			$dataValue = new EntityIdValue( new ItemId( IDENTIFIER_PROPERTY_QID ) );
			$snak = new PropertyValueSnak( new PropertyId( INSTANCE_OF_PID ), $dataValue );
			$guid = $this->makeStatementGuid( self::$idMap['P3'] );
			$propertyP3->getStatements()->addNewStatement( $snak, null, null, $guid );
			$store->saveEntity( $propertyP3, 'TestEntityP3',  $GLOBALS['wgUser'], EDIT_UPDATE );

			$dataValue = new StringValue( 'foo' );
			$snak = new PropertyValueSnak( self::$idMap['P1'], $dataValue );
			$guid = $this->makeStatementGuid( self::$idMap['Q1'] );
			self::$claimGuids['P1'] = $guid;
			$itemQ1->getStatements()->addNewStatement( $snak, null, null, $guid );

			$dataValue = new StringValue( 'baz' );
			$snak = new PropertyValueSnak( self::$idMap['P2'], $dataValue );
			$guid = $this->makeStatementGuid( self::$idMap['Q1'] );
			self::$claimGuids['P2'] = $guid;
			$itemQ1->getStatements()->addNewStatement( $snak, null, null, $guid );

			$dataValue = new StringValue( '1234' );
			$snak = new PropertyValueSnak( self::$idMap['P3'], $dataValue );
			$guid = $this->makeStatementGuid( self::$idMap['Q1'] );
			self::$claimGuids['P3'] = $guid;
			$itemQ1->getStatements()->addNewStatement( $snak, null, null, $guid );

			$store->saveEntity( $itemQ1, 'TestEntityQ1', $GLOBALS['wgUser'], EDIT_UPDATE );

			self::$hasSetup = true;
		}

		// Truncate tables
		$this->db->delete(
			SqlDumpMetaInformationRepo::META_TABLE_NAME,
			'*'
		);
		$this->db->delete(
			SqlDumpMetaInformationRepo::IDENTIFIER_PROPERTIES_TABLE_NAME,
			'*'
		);
		$this->db->delete(
			ExternalDataRepo::TABLE_NAME,
			'*'
		);

		// Insert external test data
		$this->db->insert(
			SqlDumpMetaInformationRepo::META_TABLE_NAME,
			array(
				'id' => 'foobar',
				'source_qid' => 'Q36578',
				'import_date' => '20150101000000',
				'language' => 'en',
				'source_url' => 'http://www.foo.bar',
				'size' => 42,
				'license_qid' => 'Q6938433'
			)
		);
		$this->db->insert(
			SqlDumpMetaInformationRepo::IDENTIFIER_PROPERTIES_TABLE_NAME,
			array(
				'identifier_pid' => self::$idMap['P3']->getSerialization(),
				'dump_id' => 'foobar'
			)
		);
		$this->db->insert(
			ExternalDataRepo::TABLE_NAME,
			array(
				array(
					'dump_id' => 'foobar',
					'external_id' => '1234',
					'pid' => self::$idMap['P1']->getSerialization(),
					'external_value' => 'foo'
				),
				array(
					'dump_id' => 'foobar',
					'external_id' => '1234',
					'pid' => self::$idMap['P2']->getSerialization(),
					'external_value' => 'bar'
				)
			)
		);
	}

	private function makeStatementGuid( EntityId $id ) {
		$guidGenerator = new V4GuidGenerator();

		return $id->getSerialization() . StatementGuid::SEPARATOR . $guidGenerator->newGuid();
	}

	public function testExecuteInvalidParams() {
		$params = array(
			'action' => 'wbqevcrosscheck',
			'entities' => 'Q1',
			'claims' => 'randomClaimGuid'
		);

		$this->setExpectedException(
			UsageException::class,
			'Either provide the ids of entities or ids of claims, that should be cross-checked.'
		);

		$this->doApiRequest( $params );
	}

	public function testExecuteMissingParams() {
		$params = array(
			'action' => 'wbqevcrosscheck'
		);

		$this->setExpectedException(
			UsageException::class,
			'A parameter that is required was missing. (Either provide the ids of entities or '
				. 'ids of claims, that should be cross-checked.)'
		);

		$this->doApiRequest( $params );
	}

	public function testExecuteWholeItem() {
		$params = array(
			'action' => 'wbqevcrosscheck',
			'entities' => self::$idMap['Q1'],
			'format' => 'xml'
		);
		$result = $this->doApiRequest( $params );
		$entityIdQ1 = self::$idMap['Q1']->getSerialization();
		$entityIdP1 = self::$idMap['P1']->getSerialization();
		$entityIdP2 = self::$idMap['P2']->getSerialization();
		$this->assertArrayHasKey( $entityIdQ1, $result[0]['results'] );
		$this->assertArrayHasKey( $entityIdP1, $result[0]['results'][$entityIdQ1] );
		$this->assertArrayHasKey( $entityIdP2, $result[0]['results'][$entityIdQ1] );
	}

	public function testExecutePropertyFilter() {
		$params = array(
			'action' => 'wbqevcrosscheck',
			'entities' => self::$idMap['Q1'],
			'properties' => self::$idMap['P1']
		);
		$result = $this->doApiRequest( $params );
		$entityIdQ1 = self::$idMap['Q1']->getSerialization();
		$entityIdP1 = self::$idMap['P1']->getSerialization();
		$entityIdP2 = self::$idMap['P2']->getSerialization();
		$this->assertArrayHasKey( $entityIdQ1, $result[0]['results'] );
		$this->assertArrayHasKey( $entityIdP1, $result[0]['results'][$entityIdQ1] );
		$this->assertArrayNotHasKey( $entityIdP2, $result[0]['results'][$entityIdQ1] );
	}

	public function testExecuteNotExistentItem() {
		$params = array(
			'action' => 'wbqevcrosscheck',
			'entities' => self::NOT_EXISTENT_ITEM_ID
		);
		$result = $this->doApiRequest( $params );
		$this->assertArrayHasKey( self::NOT_EXISTENT_ITEM_ID, $result[0]['results'] );
		$this->assertArrayHasKey( 'missing', $result[0]['results'][self::NOT_EXISTENT_ITEM_ID] );
	}

	public function testExecuteSingleClaim() {
		$params = array(
			'action' => 'wbqevcrosscheck',
			'claims' => self::$claimGuids['P1'],
		);
		$result = $this->doApiRequest( $params );
		$entityIdQ1 = self::$idMap['Q1']->getSerialization();
		$entityIdP1 = self::$idMap['P1']->getSerialization();
		$this->assertArrayHasKey( $entityIdQ1, $result[0]['results'] );
		$this->assertArrayHasKey( $entityIdP1, $result[0]['results'][ $entityIdQ1 ] );
		foreach ( $result[0]['results'][ $entityIdQ1 ][ $entityIdP1 ] as $comparisonResult ) {
			$this->assertArrayHasKey( 'claimGuid', $comparisonResult );
			$this->assertEquals( self::$claimGuids['P1'], $comparisonResult['claimGuid'] );
		}
	}

	public function testExecuteNotExistentClaim() {
		$params = array(
			'action' => 'wbqevcrosscheck',
			'claims' => self::NOT_EXISTENT_ITEM_ID . '$7e8ddd02-42e3-478a-adc5-63b1059f6034',
		);
		$result = $this->doApiRequest( $params );
		$this->assertArrayHasKey( self::NOT_EXISTENT_ITEM_ID, $result[0]['results'] );
		$this->assertArrayHasKey( 'missing', $result[0]['results'][self::NOT_EXISTENT_ITEM_ID] );
	}

	public function testExecuteInvalidClaimGuid() {
		$params = array(
			'action' => 'wbqevcrosscheck',
			'claims' => 'broken-claim-guid',
		);
		$this->setExpectedException( UsageException::class, 'Invalid claim guid.' );
		$this->doApiRequest( $params );
	}

}
