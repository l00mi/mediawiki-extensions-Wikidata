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
 * @covers \WikibaseQuality\ExternalValidation\Specials\SpecialCrossCheck
 *
 * @group WikibaseQualityExternalValidation
 * @group Database
 * @group medium
 *
 * @uses   \WikibaseQuality\ExternalValidation\DumpMetaInformation\DumpMetaInformation
 * @uses   \WikibaseQuality\ExternalValidation\DumpMetaInformation\SqlDumpMetaInformationRepo
 * @uses   \WikibaseQuality\ExternalValidation\CrossCheck\CrossChecker
 * @uses   \WikibaseQuality\ExternalValidation\CrossCheck\ReferenceChecker
 * @uses   \WikibaseQuality\ExternalValidation\CrossCheck\Comparer\DataValueComparer
 * @uses   \WikibaseQuality\ExternalValidation\CrossCheck\Comparer\StringValueComparer
 * @uses   \WikibaseQuality\ExternalValidation\CrossCheck\Result\ComparisonResult
 * @uses   \WikibaseQuality\ExternalValidation\CrossCheck\Result\ReferenceResult
 * @uses   \WikibaseQuality\ExternalValidation\CrossCheck\Result\CrossCheckResult
 * @uses   \WikibaseQuality\ExternalValidation\CrossCheck\Result\CrossCheckResultList
 * @uses   \WikibaseQuality\Html\HtmlTableBuilder
 * @uses   \WikibaseQuality\Html\HtmlTableHeaderBuilder
 * @uses   \WikibaseQuality\Html\HtmlTableCellBuilder
 *
 * @author BP2014N1
 * @license GNU GPL v2+
 */
class SpecialCrossCheckTest extends SpecialPageTestBase {

	/**
	 * Id of a item that (hopefully) does not exist.
	 */
	const NOT_EXISTENT_ITEM_ID = 'Q2147483647';

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
			assertThat(
				"Failed to assert output: $key",
				$output,
				is( htmlPiece( havingChild( $matcher ) ) )
			);
			$this->addToAssertionCount( 1 ); // To avoid risky tests warning
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
		$matchers['instructions'] = tagMatchingOutline( '<div class="wbqev-infobox"/>' );

		$matchers['entityId'] = tagMatchingOutline(
			'<input
				placeholder="(wbqev-crosscheck-form-entityid-placeholder)"
				name="entityid"
				class="wbqev-crosscheck-form-entity-id"/>'
		);

		$matchers['submit'] = tagMatchingOutline(
			'<input
				type="submit"
				value="(wbqev-crosscheck-form-submit-label)"/>'
		);

		$cases['empty'] = array( '', array(), $userLanguage, $matchers );

		// Invalid input
		$matchers['error'] = both(
			tagMatchingOutline(
				'<p class="wbqev-crosscheck-notice wbqev-crosscheck-notice-error"/>'
			)
		)->andAlso(
			havingTextContents( '(wbqev-crosscheck-invalid-entity-id)' )
		);

		$cases['invalid input 1'] = array( 'Qwertz', array(), $userLanguage, $matchers );
		$cases['invalid input 2'] = array( '300', array(), $userLanguage, $matchers );

		// Valid input but entity does not exist
		unset( $matchers['error'] );
		$matchers['error'] = both(
			tagMatchingOutline(
				'<p class="wbqev-crosscheck-notice wbqev-crosscheck-notice-error"/>'
			)
		)->andAlso(
			havingTextContents( '(wbqev-crosscheck-not-existent-entity)' )
		);

		$cases['valid input - not existing item'] = array(
			self::NOT_EXISTENT_ITEM_ID,
			array(),
			$userLanguage,
			$matchers
		);

		// Valid input and entity exists
		unset( $matchers['error'] );
		$matchers['result for'] = both(
			withTagName( 'h3' )
		)->andAlso(
			havingTextContents( containsString( '(wbqev-crosscheck-result-headline)' ) )
		);

		$matchers['result table'] = tagMatchingOutline(
			'<table class="wikitable sortable jquery-tablesort"/>'
		);

		$matchers['column status'] = both(
			tagMatchingOutline( '<th role="columnheader button"/>' )
		)->andAlso(
			havingTextContents( '(wbqev-crosscheck-result-table-header-status)' )
		);

		$matchers['column references'] = both(
			tagMatchingOutline( '<th role="columnheader button"/>' )
		)->andAlso(
			havingTextContents( '(wbqev-crosscheck-result-table-header-references)' )
		);

		$matchers['column property'] = both(
			tagMatchingOutline( '<th role="columnheader button"/>' )
		)->andAlso(
			havingTextContents( '(datatypes-type-wikibase-property)' )
		);

		$matchers['column local value'] = both(
			tagMatchingOutline( '<th role="columnheader button"/>' )
		)->andAlso(
			havingTextContents( '(wbqev-crosscheck-result-table-header-local-value)' )
		);

		$matchers['column external value'] = both(
			tagMatchingOutline( '<th role="columnheader button"/>' )
		)->andAlso(
			havingTextContents( '(wbqev-crosscheck-result-table-header-external-value)' )
		);

		$matchers['column external source'] = both(
			tagMatchingOutline( '<th role="columnheader button"/>' )
		)->andAlso(
			havingTextContents( '(wbqev-crosscheck-result-table-header-external-source)' )
		);

		$matchers['value status - match'] = both(
			tagMatchingOutline( '<span class="wbqev-status wbqev-status-match"/>' )
		)->andAlso(
			havingTextContents( '(wbqev-crosscheck-status-match)' )
		);

		$matchers['value local value foo'] = both(
			withTagName( 'td' )
		)->andAlso(
			havingTextContents( 'foo' )
		);

		$matchers['value external value foo'] = both(
			withTagName( 'td' )
		)->andAlso(
			havingTextContents( 'foo' )
		);

		$matchers['value status - mismatch'] = both(
			tagMatchingOutline( '<span class="wbqev-status wbqev-status-mismatch"/>' )
		)->andAlso(
			havingTextContents( '(wbqev-crosscheck-status-mismatch)' )
		);

		$matchers['value local value baz'] = both(
			withTagName( 'td' )
		)->andAlso(
			havingTextContents( 'baz' )
		);

		$matchers['value external value bar'] = both(
			withTagName( 'td' )
		)->andAlso(
			havingTextContents( 'bar' )
		);

		$matchers['value status - partial match'] = both(
			tagMatchingOutline( '<span class="wbqev-status wbqev-status-partial-match"/>' )
		)->andAlso(
			havingTextContents( '(wbqev-crosscheck-status-partial-match)' )
		);

		$matchers['value local value partiall'] = both(
			withTagName( 'td' )
		)->andAlso(
			havingTextContents( 'partiall' )
		);

		$matchers['value external value partial'] = both(
			withTagName( 'td' )
		)->andAlso(
			havingTextContents( 'partial' )
		);

		$matchers['value references - references missing'] = both(
			withTagName( 'td' )
		)->andAlso(
			havingTextContents( '(wbqev-crosscheck-status-references-missing)' )
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
