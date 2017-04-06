<?php

namespace WikibaseQuality\ExternalValidation\Tests\Specials\SpecialExternalDatabases;

use SpecialPageTestBase;
use Wikibase\Repo\WikibaseRepo;
use WikibaseQuality\ExternalValidation\DumpMetaInformation\SqlDumpMetaInformationRepo;
use WikibaseQuality\ExternalValidation\ExternalValidationServices;
use WikibaseQuality\ExternalValidation\Specials\SpecialExternalDatabases;

/**
 * @covers \WikibaseQuality\ExternalValidation\Specials\SpecialExternalDatabases
 *
 * @group WikibaseQualityExternalValidation
 * @group Database
 * @group medium
 *
 * @uses   \WikibaseQuality\ExternalValidation\DumpMetaInformation\DumpMetaInformation
 * @uses   \WikibaseQuality\ExternalValidation\DumpMetaInformation\SqlDumpMetaInformationRepo
 * @uses   \WikibaseQuality\Html\HtmlTableBuilder
 * @uses   \WikibaseQuality\Html\HtmlTableHeaderBuilder
 * @uses   \WikibaseQuality\Html\HtmlTableCellBuilder
 *
 * @author BP2014N1
 * @license GNU GPL v2+
 */
class SpecialExternalDatabasesTest extends SpecialPageTestBase {

	protected function setUp() {
		parent::setUp();

		$this->tablesUsed[ ] = SqlDumpMetaInformationRepo::META_TABLE_NAME;
		$this->tablesUsed[ ] = SqlDumpMetaInformationRepo::IDENTIFIER_PROPERTIES_TABLE_NAME;
	}

	protected function newSpecialPage() {
		$externalValidationFactory = ExternalValidationServices::getDefaultInstance();
		$wikibaseRepo = WikibaseRepo::getDefaultInstance();

		return new SpecialExternalDatabases(
			$wikibaseRepo->getTermLookup(),
			$wikibaseRepo->getEntityIdHtmlLinkFormatterFactory(),
			$externalValidationFactory->getDumpMetaInformationLookup()
		);
	}

	/**
	 * Adds temporary test data to database
	 * @throws \DBUnexpectedError
	 */
	public function addDBData() {
		// Truncate table
		$this->db->delete(
			SqlDumpMetaInformationRepo::META_TABLE_NAME,
			'*'
		);
		$this->db->delete(
			SqlDumpMetaInformationRepo::IDENTIFIER_PROPERTIES_TABLE_NAME,
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
				'identifier_pid' => 'P227'
			)
		);
	}

	/**
	 * @dataProvider executeProvider
	 */
	public function testExecute( $subPage, $request, $userLanguage, $matchers ) {
		$request = new \FauxRequest( $request );

		// Truncate table if checking for no database available
		if ( isset( $matchers['no databases'] ) ) {
			$this->db->delete(
				SqlDumpMetaInformationRepo::META_TABLE_NAME,
				'*'
			);
		}

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
		$cases = array();
		$matchers = array();

		// Empty input with database
		$matchers['instructions'] = both( withTagName( 'p' ) )
			->andAlso( havingTextContents( '(wbqev-externaldbs-instructions)' ) );

		$matchers['headline'] = both( withTagName( 'h3' ) )
			->andAlso( havingTextContents( '(wbqev-externaldbs-overview-headline)' ) );

		$matchers['database table'] = tagMatchingOutline( '<table class="wikitable"/>' );

		$matchers['column name'] = both(
			tagMatchingOutline( '<th role="columnheader button"/>' )
		)->andAlso(
			havingTextContents( '(wbqev-externaldbs-name)' )
		);

		$matchers['column import date'] = both(
			tagMatchingOutline( '<th role="columnheader button"/>' )
		)->andAlso(
			havingTextContents( '(wbqev-externaldbs-import-date)' )
		);

		$matchers['column data language'] = both(
			tagMatchingOutline( '<th role="columnheader button"/>' )
		)->andAlso(
			havingTextContents( '(wbqev-externaldbs-language)' )
		);

		$matchers['column source urls'] = both(
			tagMatchingOutline( '<th role="columnheader button"/>' )
		)->andAlso(
			havingTextContents( '(wbqev-externaldbs-source-urls)' )
		);

		$matchers['column size'] = both(
			tagMatchingOutline( '<th role="columnheader button"/>' )
		)->andAlso(
			havingTextContents( '(wbqev-externaldbs-size)' )
		);

		$matchers['column license'] = both(
			tagMatchingOutline( '<th role="columnheader button"/>' )
		)->andAlso(
			havingTextContents( '(wbqev-externaldbs-license)' )
		);

		$matchers['value name'] = both(
			tagMatchingOutline( '<td rowspan="1"/>' )
		)->andAlso(
			havingTextContents( containsString( 'Q36578' ) )
		);

		$matchers['value import date'] = both(
			withTagName( 'td' )
		)->andAlso(
			havingTextContents( '00:00, 1 (january) 2015' )
		);

		$matchers['value data language'] = both(
			withTagName( 'td' )
		)->andAlso(
			havingTextContents( 'English' )
		);

		$matchers['value source urls'] = both(
			withTagName( 'td' )
		)->andAlso(
			havingTextContents( 'http://www.foo.bar' )
		);

		$matchers['value size'] = both(
			withTagName( 'td' )
		)->andAlso(
			havingTextContents( '(size-bytes)' )
		);

		$matchers['value license'] = both(
			withTagName( 'td' )
		)->andAlso(
			havingTextContents( containsString( 'Q6938433' ) )
		);

		$cases['empty with database'] = array( '', array(), $userLanguage, $matchers );

		// Empty input without databases
		unset( $matchers );
		$matchers['instructions'] = both(
			withTagName( 'p' )
		)->andAlso(
			havingTextContents( '(wbqev-externaldbs-instructions)' )
		);

		$matchers['headline'] = both(
			withTagName( 'h3' )
		)->andAlso(
			havingTextContents( '(wbqev-externaldbs-overview-headline)' )
		);

		$matchers['no databases'] = both(
			withTagName( 'p' )
		)->andAlso(
			havingTextContents( '(wbqev-externaldbs-no-databases)' )
		);

		$cases['empty without databases'] = array( '', array(), $userLanguage, $matchers );

		return $cases;
	}

}
