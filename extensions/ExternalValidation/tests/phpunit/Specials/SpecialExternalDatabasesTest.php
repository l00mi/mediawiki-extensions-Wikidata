<?php

namespace WikibaseQuality\ExternalValidation\Tests\Specials\SpecialExternalDatabases;

use SpecialPageTestBase;
use Wikibase\Repo\WikibaseRepo;
use WikibaseQuality\ExternalValidation\DumpMetaInformation\SqlDumpMetaInformationRepo;
use WikibaseQuality\ExternalValidation\ExternalValidationServices;
use WikibaseQuality\ExternalValidation\Specials\SpecialExternalDatabases;

/**
 * @covers WikibaseQuality\ExternalValidation\Specials\SpecialExternalDatabases
 *
 * @group WikibaseQualityExternalValidation
 * @group Database
 * @group medium
 *
 * @uses   WikibaseQuality\ExternalValidation\DumpMetaInformation\DumpMetaInformation
 * @uses   WikibaseQuality\ExternalValidation\DumpMetaInformation\SqlDumpMetaInformationRepo
 * @uses   WikibaseQuality\Html\HtmlTableBuilder
 * @uses   WikibaseQuality\Html\HtmlTableHeaderBuilder
 * @uses   WikibaseQuality\Html\HtmlTableCellBuilder
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
			$this->assertTag( $matcher, $output, "Failed to assert output: $key" );
		}
	}

	public function executeProvider() {
		$userLanguage = 'qqx';
		$cases = array();
		$matchers = array();

		// Empty input with database
		$matchers['instructions'] = array(
			'tag' => 'p',
			'content' => '(wbqev-externaldbs-instructions)'
		);

		$matchers['headline'] = array(
			'tag' => 'h3',
			'content' => '(wbqev-externaldbs-overview-headline)'
		);

		$matchers['database table'] = array(
			'tag' => 'table',
			'attributes' => array(
				'class' => 'wikitable'
			)
		);

		$matchers['column name'] = array(
			'tag' => 'th',
			'attributes' => array(
				'role' => 'columnheader button'
			),
			'content' => '(wbqev-externaldbs-name)'
		);

		$matchers['column import date'] = array(
			'tag' => 'th',
			'attributes' => array(
				'role' => 'columnheader button'
			),
			'content' => '(wbqev-externaldbs-import-date)'
		);

		$matchers['column data language'] = array(
			'tag' => 'th',
			'attributes' => array(
				'role' => 'columnheader button'
			),
			'content' => '(wbqev-externaldbs-language)'
		);

		$matchers['column source urls'] = array(
			'tag' => 'th',
			'attributes' => array(
				'role' => 'columnheader button'
			),
			'content' => '(wbqev-externaldbs-source-urls)'
		);

		$matchers['column size'] = array(
			'tag' => 'th',
			'attributes' => array(
				'role' => 'columnheader button'
			),
			'content' => '(wbqev-externaldbs-size)'
		);

		$matchers['column license'] = array(
			'tag' => 'th',
			'attributes' => array(
				'role' => 'columnheader button'
			),
			'content' => '(wbqev-externaldbs-license)'
		);

		$matchers['value name'] = array(
			'tag' => 'td',
			'attributes' => array(
				'rowspan' => '1'
			),
			'content' => 'Q36578'
		);

		$matchers['value import date'] = array(
			'tag' => 'td'
		);

		$matchers['value data language'] = array(
			'tag' => 'td',
			'content' => 'English'
		);

		$matchers['value source urls'] = array(
			'tag' => 'td',
			'content' => 'http://www.foo.bar'
		);

		$matchers['value size'] = array(
			'tag' => 'td',
			'content' => '(size-bytes)'
		);

		$matchers['value license'] = array(
			'tag' => 'td',
			'content' => 'Q6938433'
		);

		$cases['empty with database'] = array( '', array(), $userLanguage, $matchers );

		// Empty input without databases
		unset( $matchers );
		$matchers['instructions'] = array(
			'tag' => 'p',
			'content' => '(wbqev-externaldbs-instructions)'
		);

		$matchers['headline'] = array(
			'tag' => 'h3',
			'content' => '(wbqev-externaldbs-overview-headline)'
		);

		$matchers['no databases'] = array(
			'tag' => 'p',
			'content' => '(wbqev-externaldbs-no-databases)'
		);

		$cases['empty without databases'] = array( '', array(), $userLanguage, $matchers );

		return $cases;
	}

}
