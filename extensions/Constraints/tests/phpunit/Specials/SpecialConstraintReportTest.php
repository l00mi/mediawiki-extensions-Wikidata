<?php

namespace WikibaseQuality\ConstraintReport\Tests\Specials\SpecialConstraintReport;

use SpecialPageTestBase;
use Wikibase\DataModel\Services\Statement\GuidGenerator;
use Wikibase\Repo\EntityIdLabelFormatterFactory;
use DataValues\StringValue;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\DataModel\Statement\Statement;
use Wikibase\Repo\WikibaseRepo;
use Wikibase\DataModel\Entity\EntityId;
use WikibaseQuality\ConstraintReport\ConstraintReportFactory;
use WikibaseQuality\ConstraintReport\Specials\SpecialConstraintReport;

/**
 * @covers \WikibaseQuality\ConstraintReport\Specials\SpecialConstraintReport
 *
 * @group WikibaseQualityConstraints
 * @group Database
 * @group medium
 *
 * @uses   \WikibaseQuality\ConstraintReport\ConstraintCheck\Checker\SingleValueChecker
 * @uses   \WikibaseQuality\ConstraintReport\ConstraintCheck\Checker\MultiValueChecker
 * @uses   \WikibaseQuality\ConstraintReport\ConstraintCheck\Helper\ConstraintParameterParser
 * @uses   \WikibaseQuality\ConstraintReport\ConstraintCheck\DelegatingConstraintChecker
 * @uses   \WikibaseQuality\ConstraintReport\ConstraintCheck\Result\CheckResult
 * @uses   \WikibaseQuality\Html\HtmlTableBuilder
 * @uses   \WikibaseQuality\Html\HtmlTableCellBuilder
 * @uses   \WikibaseQuality\Html\HtmlTableHeaderBuilder
 *
 * @author BP2014N1
 * @license GNU GPL v2+
 */
class SpecialConstraintReportTest extends SpecialPageTestBase {

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
	private static $claimGuids = array ();

	/**
	 * @var bool
	 */
	private static $hasSetup;

	protected function setUp() {
		parent::setUp();
		$this->tablesUsed[ ] = CONSTRAINT_TABLE;
	}

	protected function tearDown() {
		parent::tearDown();
	}

	protected function newSpecialPage() {
		$constraintReportFactory = ConstraintReportFactory::getDefaultInstance();
		$wikibaseRepo = WikibaseRepo::getDefaultInstance();

		return new SpecialConstraintReport(
			$wikibaseRepo->getEntityLookup(),
			$wikibaseRepo->getEntityTitleLookup(),
			new EntityIdLabelFormatterFactory(),
			$wikibaseRepo->getEntityIdHtmlLinkFormatterFactory(),
			$wikibaseRepo->getLanguageFallbackLabelDescriptionLookupFactory(),
			$wikibaseRepo->getEntityIdParser(),
			$wikibaseRepo->getValueFormatterFactory(),
			$constraintReportFactory->getConstraintChecker()
		);
	}

	/**
	 * Adds temporary test data to database
	 *
	 * @throws \DBUnexpectedError
	 */
	public function addDBData() {
		if ( !self::$hasSetup ) {
			$store = WikibaseRepo::getDefaultInstance()->getEntityStore();

			$propertyP1 = Property::newFromType( 'string' );
			$store->saveEntity( $propertyP1, 'TestEntityP1', $GLOBALS[ 'wgUser' ], EDIT_NEW );
			self::$idMap[ 'P1' ] = $propertyP1->getId();

			$itemQ1 = new Item();
			$store->saveEntity( $itemQ1, 'TestEntityQ1', $GLOBALS[ 'wgUser' ], EDIT_NEW );
			self::$idMap[ 'Q1' ] = $itemQ1->getId();

			$statementGuidGenerator = new GuidGenerator();

			$dataValue = new StringValue( 'foo' );
			$snak = new PropertyValueSnak( self::$idMap[ 'P1' ], $dataValue );
			$statement = new Statement( $snak );
			$statementGuid = $statementGuidGenerator->newGuid( self::$idMap[ 'Q1' ] );
			self::$claimGuids[ 'P1' ] = $statementGuid;
			$statement->setGuid( $statementGuid );
			$itemQ1->getStatements()->addStatement( $statement );

			$store->saveEntity( $itemQ1, 'TestEntityQ1', $GLOBALS[ 'wgUser' ], EDIT_UPDATE );

			self::$hasSetup = true;
		}

		// Truncate table
		$this->db->delete(
			CONSTRAINT_TABLE,
			'*'
		);

		$this->db->insert(
			CONSTRAINT_TABLE,
			array (
				array (
					'constraint_guid' => '1',
					'pid' => self::$idMap[ 'P1' ]->getNumericId(),
					'constraint_type_qid' => 'Multi value',
					'constraint_parameters' => '{}'
				),
				array (
					'constraint_guid' => '3',
					'pid' => self::$idMap[ 'P1' ]->getNumericId(),
					'constraint_type_qid' => 'Single value',
					'constraint_parameters' => '{}'
				)
			)
		);
	}

	/**
	 * @dataProvider executeProvider
	 */
	public function testExecute( $subPage, array $request, $userLanguage, array $matchers ) {
		$request = new \FauxRequest( $request );

		// the added item is Q1; this solves the problem that the provider is executed before the test
		$id = self::$idMap[ 'Q1' ];
		$subPage = str_replace( '$id', $id->getSerialization(), $subPage );

		// assert matchers
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

	public function executeProvider() {
		$userLanguage = 'qqx';
		$cases = array ();
		$matchers = array ();

		// Empty input
		$matchers['explanationOne'] = both( withTagName( 'div' ) )
			->andAlso( havingTextContents( '(wbqc-constraintreport-explanation-part-one)' ) );

		$matchers['explanationTwo'] = both( withTagName( 'div' ) )
			->andAlso( havingTextContents( '(wbqc-constraintreport-explanation-part-two)' ) );

		$matchers['entityId'] = tagMatchingOutline(
			'<input
				placeholder="(wbqc-constraintreport-form-entityid-placeholder)"
				name="entityid"
				class="wbqc-constraintreport-form-entity-id"/>'
		);

		$matchers['submit'] = tagMatchingOutline(
			'<input
				type="submit"
				value="(wbqc-constraintreport-form-submit-label)"/>'
		);

		$cases[ 'empty' ] = array ( '', array (), $userLanguage, $matchers );

		// Invalid input
		$matchers['error'] = both(
			tagMatchingOutline(
				'<p class="wbqc-constraintreport-notice wbqc-constraintreport-notice-error"/>'
			)
		)->andAlso( havingTextContents( '(wbqc-constraintreport-invalid-entity-id)' ) );

		$cases[ 'invalid input 1' ] = array ( 'Qwertz', array (), $userLanguage, $matchers );
		$cases[ 'invalid input 2' ] = array ( '300', array (), $userLanguage, $matchers );

		// Valid input but entity does not exist
		unset( $matchers[ 'error' ] );

		$matchers['error'] = both(
			tagMatchingOutline(
				'<p class="wbqc-constraintreport-notice wbqc-constraintreport-notice-error"/>'
			)
		)->andAlso( havingTextContents( '(wbqc-constraintreport-not-existent-entity)' ) );

		$cases[ 'valid input - not existing item' ] = array (
			self::NOT_EXISTENT_ITEM_ID,
			array (),
			$userLanguage,
			$matchers
		);

		// Valid input and entity exists
		unset( $matchers[ 'error' ] );
		$matchers[ 'result for' ] = array (
			'tag' => 'h3',
			'content' => '(wbqc-constraintreport-result-headline) '
		);

		$matchers['result for'] = both(
			withTagName( 'h3' )
		)->andAlso(
			havingTextContents( containsString( '(wbqc-constraintreport-result-headline) ' ) )
		);

		$matchers['result table'] = tagMatchingOutline(
			'<table class="wikitable sortable jquery-tablesort"/>'
		);

		$matchers['column status'] = both(
			tagMatchingOutline( '<th role="columnheader button"/>' )
		)->andAlso(
			havingTextContents( '(wbqc-constraintreport-result-table-header-status)' )
		);

		$matchers['column claim'] = both(
			tagMatchingOutline( '<th role="columnheader button"/>' )
		)->andAlso(
			havingTextContents( '(wbqc-constraintreport-result-table-header-claim)' )
		);

		$matchers['column constraint'] = both(
			tagMatchingOutline( '<th role="columnheader button"/>' )
		)->andAlso(
			havingTextContents( '(wbqc-constraintreport-result-table-header-constraint)' )
		);

		$matchers['value status - violation'] = both(
			tagMatchingOutline( '<span class="wbqc-status wbqc-status-violation"/>' )
		)->andAlso(
			havingTextContents( '(wbqc-constraintreport-status-violation)' )
		);

		$matchers['value status - compliance'] = both(
			tagMatchingOutline( '<span class="wbqc-status wbqc-status-compliance"/>' )
		)->andAlso(
			havingTextContents( '(wbqc-constraintreport-status-compliance)' )
		);

		$cases[ 'valid input - existing item' ] = array ( '$id', array (), $userLanguage, $matchers );

		return $cases;
	}

}
