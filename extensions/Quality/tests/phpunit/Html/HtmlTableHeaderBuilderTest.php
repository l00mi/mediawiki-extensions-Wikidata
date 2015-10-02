<?php


namespace WikibaseQuality\ExternalValidation\Tests\Html;

use WikibaseQuality\Html\HtmlTableHeaderBuilder;


/**
 * @covers WikibaseQuality\Html\HtmlTableHeaderBuilder
 *
 * @group WikibaseQuality
 *
 * @author BP2014N1
 * @license GNU GPL v2+
 */
class HtmlTableHeaderBuilderTest extends \MediaWikiTestCase {

	/**
	 * @dataProvider constructDataProvider
	 */
	public function testConstruct( $content, $isSortable, $expectedException = null ) {
		$this->setExpectedException( $expectedException );
		$header = new HtmlTableHeaderBuilder( $content, $isSortable );

		$this->assertEquals( $content, $header->getContent() );
		$this->assertEquals( $isSortable, $header->getIsSortable() );
	}

	/**
	 * Test cases for testConstruct
	 *
	 * @return array
	 */
	public function constructDataProvider() {
		return array(
			array(
				'foobar',
				true
			),
			array(
				42,
				true,
				'InvalidArgumentException'
			),
			array(
				'fooar',
				42,
				'InvalidArgumentException'
			)
		);
	}

	/**
	 * @dataProvider toHtmlDataProvider
	 */
	public function testToHtml( $content, $isSortable, $expectedHtml ) {
		$header = new HtmlTableHeaderBuilder( $content, $isSortable );
		$actualHtml = $header->toHtml();

		$this->assertEquals( $expectedHtml, $actualHtml );
	}

	/**
	 * Test cases for testToHtml
	 *
	 * @return array
	 */
	public function toHtmlDataProvider() {
		return array(
			array(
				'foobar',
				true,
				'<th role="columnheader button">foobar</th>'
			),
			array(
				'foobar',
				false,
				'<th role="columnheader button" class="unsortable">foobar</th>'
			)
		);
	}
}
