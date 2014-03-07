<?php

namespace Wikibase\Test;

use Wikibase\DataModel\SimpleSiteLink;
use Wikibase\EntityContent;
use Wikibase\ItemContent;

/**
 * @covers Wikibase\ItemContent
 *
 * @group Wikibase
 * @group WikibaseItem
 * @group WikibaseRepo
 * @group WikibaseContent
 * @group WikibaseItemContent
 *
 * @group Database
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author aude
 */
class ItemContentTest extends EntityContentTest {

	public function setUp() {
		parent::setUp();

		$site = new \MediaWikiSite();
		$site->setGlobalId( 'eswiki' );
		$site->setPath( \MediaWikiSite::PATH_PAGE, "https://es.wikipedia.org/wiki/$1" );

		$sitesTable = \SiteSQLStore::newInstance();
		$sitesTable->clear();
		$sitesTable->saveSites( array( $site ) );
	}

	/**
	 * @see EntityContentTest::getContentClass
	 */
	protected function getContentClass() {
		return '\Wikibase\ItemContent';
	}

	/**
	 * @dataProvider siteLinkConflictProvider
	 */
	public function testSiteLinkConflict( SimpleSiteLink $siteLink, $expected ) {
		$content = ItemContent::newEmpty();
		$content->getItem()->addSiteLink( $siteLink );

		$status = $content->save( 'add item', null, EDIT_NEW );

		$this->assertTrue( $status->isOK(), 'item creation succeeded' );

		$content1 = ItemContent::newEmpty();
		$content1->getItem()->addSiteLink( $siteLink );

		$status = $content1->save( 'add item', null, EDIT_NEW );

		$this->assertFalse( $status->isOK(), "saving an item with a site link conflict should fail" );

		$html = $status->getHTML();
		$expected = preg_replace( '(\$1)', $content->getTitle()->getFullText(), $html );

		$this->assertEquals( $expected, $status->getHTML() );
	}

	public function siteLinkConflictProvider() {
		$prefix = get_class( $this ) . '/';

		$siteLink = new SimpleSiteLink( 'eswiki', $prefix . 'Pelecanus' );

		return array(
			array(
				$siteLink,
				'Site link [https://es.wikipedia.org/wiki/Pelecanus Pelecanus] already used by item [[$1]].'
			)
		);
	}

	public function provideEquals() {
		return array(
			array( #0
				array(),
				array(),
				true
			),
			array( #1
				array( 'labels' => array() ),
				array( 'descriptions' => null ),
				true
			),
			array( #2
				array( 'entity' => 'q23' ),
				array(),
				true
			),
			array( #3
				array( 'entity' => 'q23' ),
				array( 'entity' => 'q24' ),
				false
			),
			array( #4
				array( 'labels' => array(
					'en' => 'foo',
					'de' => 'bar',
				) ),
				array( 'labels' => array(
					'en' => 'foo',
				) ),
				false
			),
			array( #5
				array( 'labels' => array(
					'en' => 'foo',
					'de' => 'bar',
				) ),
				array( 'labels' => array(
					'de' => 'bar',
					'en' => 'foo',
				) ),
				true
			),
			array( #6
				array( 'aliases' => array(
					'en' => array( 'foo', 'FOO' ),
				) ),
				array( 'aliases' => array(
					'en' => array( 'foo', 'FOO', 'xyz' ),
				) ),
				false
			),
		);
	}

	/**
	 * @dataProvider provideEquals
	 */
	public function testEquals( array $a, array $b, $equals ) {
		$itemA = $this->newFromArray( $a );
		$itemB = $this->newFromArray( $b );

		$actual = $itemA->equals( $itemB );
		$this->assertEquals( $equals, $actual );

		$actual = $itemB->equals( $itemA );
		$this->assertEquals( $equals, $actual );
	}

	/**
	 * Tests @see Wikibase\Entity::getTextForSearchIndex
	 *
	 * @dataProvider getTextForSearchIndexProvider
	 *
	 * @param EntityContent $itemContent
	 * @param string $pattern
	 */
	public function testGetTextForSearchIndex( EntityContent $itemContent, $pattern ) {
		$text = $itemContent->getTextForSearchIndex();
		$this->assertRegExp( $pattern . 'm', $text );
	}

	public function getTextForSearchIndexProvider() {
		$itemContent = $this->newEmpty();
		$itemContent->getEntity()->setLabel( 'en', "cake" );
		$itemContent->getEntity()->addSiteLink( new SimpleSiteLink( 'dewiki', 'Berlin' ) );

		return array(
			array( $itemContent, '!^cake$!' ),
			array( $itemContent, '!^Berlin$!' )
		);
	}

}
