<?php

namespace WikibaseQuality\ExternalValidation\Tests\Specials\SpecialCrossCheck;

use FauxRequest;
use SpecialPageTestBase;
use DataValues\StringValue;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\EntityIdValue;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\DataModel\Statement\Statement;
use Wikibase\DataModel\Statement\StatementGuid;
use Wikibase\DataModel\Services\Statement\V4GuidGenerator;
use Wikibase\Repo\EntityIdLabelFormatterFactory;
use Wikibase\Repo\WikibaseRepo;
use WikibaseQuality\ExternalValidation\DumpMetaInformation\SqlDumpMetaInformationRepo;
use WikibaseQuality\ExternalValidation\ExternalDataRepo;
use WikibaseQuality\ExternalValidation\ExternalValidationServices;
use WikibaseQuality\ExternalValidation\Specials\SpecialCrossCheck;

/**
 * @covers WikibaseQuality\ExternalValidation\Specials\SpecialCrossCheck
 *
 * @group WikibaseQualityExternalValidation
 * @group Database
 * @group medium
 *
 * @uses   WikibaseQuality\ExternalValidation\DumpMetaInformation\DumpMetaInformation
 * @uses   WikibaseQuality\ExternalValidation\DumpMetaInformation\SqlDumpMetaInformationRepo
 * @uses   WikibaseQuality\ExternalValidation\CrossCheck\CrossChecker
 * @uses   WikibaseQuality\ExternalValidation\CrossCheck\ReferenceChecker
 * @uses   WikibaseQuality\ExternalValidation\CrossCheck\Comparer\DataValueComparer
 * @uses   WikibaseQuality\ExternalValidation\CrossCheck\Comparer\StringValueComparer
 * @uses   WikibaseQuality\ExternalValidation\CrossCheck\Result\ComparisonResult
 * @uses   WikibaseQuality\ExternalValidation\CrossCheck\Result\ReferenceResult
 * @uses   WikibaseQuality\ExternalValidation\CrossCheck\Result\CrossCheckResult
 * @uses   WikibaseQuality\ExternalValidation\CrossCheck\Result\CrossCheckResultList
 * @uses   WikibaseQuality\Html\HtmlTableBuilder
 * @uses   WikibaseQuality\Html\HtmlTableHeaderBuilder
 * @uses   WikibaseQuality\Html\HtmlTableCellBuilder
 *
 * @author BP2014N1
 * @license GNU GPL v2+
 */
class SpecialCrossCheckTest extends SpecialPageTestBase {

	/**
	 * Id of a item that (hopefully) does not exist.
	 */
	const NOT_EXISTENT_ITEM_ID = 'Q5678765432345678';

	/**
	 * @var EntityId[]
	 */
	private static $idMap;

	/**
	 * @var array
	 */
	private static $statementGuids = array();

	/**
	 * @var bool
	 */
	private static $hasSetup;

	protected function setUp() {
		parent::setUp();
		$this->tablesUsed[] = SqlDumpMetaInformationRepo::META_TABLE_NAME;
		$this->tablesUsed[] = SqlDumpMetaInformationRepo::IDENTIFIER_PROPERTIES_TABLE_NAME;
		$this->tablesUsed[] = ExternalDataRepo::TABLE_NAME;
	}

	protected function newSpecialPage() {
		$externalValidationFactory = ExternalValidationServices::getDefaultInstance();
		$wikibaseRepo = WikibaseRepo::getDefaultInstance();

		return new SpecialCrossCheck(
			$wikibaseRepo->getEntityLookup(),
			$wikibaseRepo->getTermLookup(),
			new EntityIdLabelFormatterFactory(),
			$wikibaseRepo->getEntityIdHtmlLinkFormatterFactory(),
			$wikibaseRepo->getEntityIdParser(),
			$wikibaseRepo->getValueFormatterFactory(),
			$externalValidationFactory->getCrossCheckInteractor()
		);
	}

	/**
	 * Adds temporary test data to database
	 * @throws \DBUnexpectedError
	 */
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

			$propertyP4 = Property::newFromType( 'string' );
			$store->saveEntity( $propertyP4, 'TestEntityP4', $GLOBALS['wgUser'], EDIT_NEW );
			self::$idMap['P4'] = $propertyP4->getId();

			$itemQ1 = new Item();
			$store->saveEntity( $itemQ1, 'TestEntityQ1', $GLOBALS['wgUser'], EDIT_NEW );
			self::$idMap['Q1'] = $itemQ1->getId();

			$dataValue = new EntityIdValue( new ItemId( IDENTIFIER_PROPERTY_QID ) );
			$snak = new PropertyValueSnak( new PropertyId( INSTANCE_OF_PID ), $dataValue );
			$guid = $this->makeStatementGuid( self::$idMap['P3'] );
			$propertyP3->getStatements()->addNewStatement( $snak, null, null, $guid );
			$store->saveEntity( $propertyP3, 'TestEntityP3', $GLOBALS['wgUser'], EDIT_UPDATE );

			$dataValue = new StringValue( 'foo' );
			$snak = new PropertyValueSnak( self::$idMap['P1'], $dataValue );
			$statementGuid = $this->makeStatementGuid( self::$idMap['Q1'] );
			$statement = new Statement( $snak );
			self::$statementGuids['P1'] = $statementGuid;
			$statement->setGuid( $statementGuid );
			$itemQ1->getStatements()->addStatement( $statement );

			$dataValue = new StringValue( 'baz' );
			$snak = new PropertyValueSnak( self::$idMap['P2'], $dataValue );
			$statementGuid = $this->makeStatementGuid( self::$idMap['Q1'] );
			$statement = new Statement( $snak );
			self::$statementGuids['P2'] = $statementGuid;
			$statement->setGuid( $statementGuid );
			$itemQ1->getStatements()->addStatement( $statement );

			$dataValue = new StringValue( '1234' );
			$snak = new PropertyValueSnak( self::$idMap['P3'], $dataValue );
			$statement = new Statement( $snak );
			$statementGuid = $this->makeStatementGuid( self::$idMap['Q1'] );
			self::$statementGuids['P3'] = $statementGuid;
			$statement->setGuid( $statementGuid );
			$itemQ1->getStatements()->addStatement( $statement );

			$dataValue = new StringValue( 'partiall' );
			$snak = new PropertyValueSnak( self::$idMap['P4'], $dataValue );
			$statement = new Statement( $snak );
			$statementGuid = $this->makeStatementGuid( self::$idMap['Q1'] );
			self::$statementGuids['P4'] = $statementGuid;
			$statement->setGuid( $statementGuid );
			$itemQ1->getStatements()->addStatement( $statement );

			$store->saveEntity( $itemQ1, 'TestEntityQ1', $GLOBALS['wgUser'], EDIT_UPDATE );

			self::$hasSetup = true;
		}

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
				'dump_id' => 'foobar',
				'identifier_pid' => self::$idMap['P3']->getSerialization()
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
				),
				array(
					'dump_id' => 'foobar',
					'external_id' => '1234',
					'pid' => self::$idMap['P4']->getSerialization(),
					'external_value' => 'partial'
				)
			)
		);
	}

	private function makeStatementGuid( EntityId $id ) {
		$guidGenerator = new V4GuidGenerator();

		return $id->getSerialization() . StatementGuid::SEPARATOR . $guidGenerator->newGuid();
	}

	/**
	 * @dataProvider executeProvider
	 */
	public function testExecute( $subPage, $request, $userLanguage, $matchers ) {
		$request = new FauxRequest( $request );

		// The added item is Q1. This solves the problem that the provider is executed before the test
		$id = self::$idMap['Q1'];
		$subPage = str_replace( '$id', $id->getSerialization(), $subPage );

		// Assert matchers
		list( $output, ) = $this->executeSpecialPage( $subPage, $request, $userLanguage );
		foreach ( $matchers as $key => $matcher ) {
			$this->assertTag( $matcher, $output, "Failed to assert output: $key" );
		}
	}

	/**
	 * Test cases for testExecute
	 * @return array
	 */
	public function executeProvider() {
		$userLanguage = 'qqx';
		$cases = array();
		$matchers = array();

		// Empty input
		$matchers['instructions'] = array(
			'tag' => 'div',
			'attributes' => array(
				'class' => 'wbqev-infobox'
			)
		);

		$matchers['entityId'] = array(
			'tag' => 'input',
			'attributes' => array(
				'placeholder' => '(wbqev-crosscheck-form-entityid-placeholder)',
				'name' => 'entityid',
				'class' => 'wbqev-crosscheck-form-entity-id'
			)
		);

		$matchers['submit'] = array(
			'tag' => 'input',
			'attributes' => array(
				'type' => 'submit',
				'value' => '(wbqev-crosscheck-form-submit-label)'
			)
		);

		$cases['empty'] = array( '', array(), $userLanguage, $matchers );

		// Invalid input
		$matchers['error'] = array(
			'tag' => 'p',
			'attributes' => array(
				'class' => 'wbqev-crosscheck-notice wbqev-crosscheck-notice-error'
			),
			'content' => '(wbqev-crosscheck-invalid-entity-id)'
		);

		$cases['invalid input 1'] = array( 'Qwertz', array(), $userLanguage, $matchers );
		$cases['invalid input 2'] = array( '300', array(), $userLanguage, $matchers );

		// Valid input but entity does not exist
		unset( $matchers['error'] );
		$matchers['error'] = array(
			'tag' => 'p',
			'attributes' => array(
				'class' => 'wbqev-crosscheck-notice wbqev-crosscheck-notice-error'
			),
			'content' => '(wbqev-crosscheck-not-existent-entity)'
		);

		$cases['valid input - not existing item'] = array(
			self::NOT_EXISTENT_ITEM_ID,
			array(),
			$userLanguage,
			$matchers
		);

		// Valid input and entity exists
		unset( $matchers['error'] );
		$matchers['result for'] = array(
			'tag' => 'h3',
			'content' => '(wbqev-crosscheck-result-headline)'
		);

		$matchers['result table'] = array(
			'tag' => 'table',
			'attributes' => array(
				'class' => 'wikitable sortable jquery-tablesort'
			)
		);

		$matchers['column status'] = array(
			'tag' => 'th',
			'attributes' => array(
				'role' => 'columnheader button'
			),
			'content' => '(wbqev-crosscheck-result-table-header-status)'
		);

		$matchers['column references'] = array(
			'tag' => 'th',
			'attributes' => array(
				'role' => 'columnheader button'
			),
			'content' => '(wbqev-crosscheck-result-table-header-references)'
		);

		$matchers['column property'] = array(
			'tag' => 'th',
			'attributes' => array(
				'role' => 'columnheader button'
			),
			'content' => '(datatypes-type-wikibase-property)'
		);

		$matchers['column local value'] = array(
			'tag' => 'th',
			'attributes' => array(
				'role' => 'columnheader button'
			),
			'content' => '(wbqev-crosscheck-result-table-header-local-value)'
		);

		$matchers['column external value'] = array(
			'tag' => 'th',
			'attributes' => array(
				'role' => 'columnheader button'
			),
			'content' => '(wbqev-crosscheck-result-table-header-external-value)'
		);

		$matchers['column external source'] = array(
			'tag' => 'th',
			'attributes' => array(
				'role' => 'columnheader button'
			),
			'content' => '(wbqev-crosscheck-result-table-header-external-source)'
		);

		$matchers['value status - match'] = array(
			'tag' => 'span',
			'attributes' => array(
				'class' => 'wbqev-status wbqev-status-match'
			),
			'content' => '(wbqev-crosscheck-status-match)'
		);

		$matchers['value local value foo'] = array(
			'tag' => 'td',
			'content' => 'foo'
		);

		$matchers['value external value foo'] = array(
			'tag' => 'td',
			'content' => 'foo'
		);

		$matchers['value status - mismatch'] = array(
			'tag' => 'span',
			'attributes' => array(
				'class' => 'wbqev-status wbqev-status-mismatch'
			),
			'content' => '(wbqev-crosscheck-status-mismatch)'
		);

		$matchers['value local value baz'] = array(
			'tag' => 'td',
			'content' => 'baz'
		);

		$matchers['value external value bar'] = array(
			'tag' => 'td',
			'content' => 'bar'
		);

		$matchers['value status - partial match'] = array(
			'tag' => 'span',
			'attributes' => array(
				'class' => 'wbqev-status wbqev-status-partial-match'
			),
			'content' => '(wbqev-crosscheck-status-partial-match)'
		);

		$matchers['value local value partiall'] = array(
			'tag' => 'td',
			'content' => 'partiall'
		);

		$matchers['value external value partial'] = array(
			'tag' => 'td',
			'content' => 'partial'
		);

		$matchers['value references - references missing'] = array(
			'tag' => 'td',
			'content' => '(wbqev-crosscheck-status-references-missing)'
		);

		$cases['valid input - existing item without references'] = array(
			'$id',
			array(),
			$userLanguage,
			$matchers
		);

		return $cases;
	}

}
