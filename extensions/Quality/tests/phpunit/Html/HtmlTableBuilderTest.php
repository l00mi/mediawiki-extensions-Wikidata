<?php

namespace WikibaseQuality\ExternalValidation\Tests\Html;

use InvalidArgumentException;
use PHPUnit_Framework_TestCase;
use WikibaseQuality\Html\HtmlTableBuilder;
use WikibaseQuality\Html\HtmlTableCellBuilder;
use WikibaseQuality\Html\HtmlTableHeaderBuilder;

/**
 * @covers \WikibaseQuality\Html\HtmlTableBuilder
 *
 * @group WikibaseQuality
 *
 * @uses   \WikibaseQuality\Html\HtmlTableHeaderBuilder
 * @uses   \WikibaseQuality\Html\HtmlTableCellBuilder
 *
 * @author BP2014N1
 * @license GNU GPL v2+
 */
class HtmlTableBuilderTest extends PHPUnit_Framework_TestCase {

	/**
	 * @dataProvider constructDataProvider
	 */
	public function testConstruct(
		$headers,
		array $expectedHeaders = null,
		$expectedIsSortable,
		$expectedException
	) {
		$this->setExpectedException( $expectedException );
		$htmlTable = new HtmlTableBuilder( $headers );

		$this->assertEquals( $expectedHeaders, $htmlTable->getHeaders() );
		$this->assertEquals( $expectedIsSortable, $htmlTable->isSortable() );
	}

	/**
	 * @return array
	 */
	public function constructDataProvider() {
		return array(
			array(
				array(
					'foo',
					'bar'
				),
				array (
					new HtmlTableHeaderBuilder( 'foo' ),
					new HtmlTableHeaderBuilder( 'bar' )
				),
				false,
				null
			),
			array (
				array (
					new HtmlTableHeaderBuilder( 'foo', true ),
					'bar'
				),
				array (
					new HtmlTableHeaderBuilder( 'foo', true ),
					new HtmlTableHeaderBuilder( 'bar' )
				),
				true,
				null
			),
			array (
				array (
					new HtmlTableHeaderBuilder( 'foo', true ),
					new HtmlTableHeaderBuilder( 'bar' )
				),
				array (
					new HtmlTableHeaderBuilder( 'foo', true ),
					new HtmlTableHeaderBuilder( 'bar' )
				),
				true,
				null
			),
			array(
				array( 42 ),
				null,
				false,
				InvalidArgumentException::class
			),
			array(
				'foobar',
				null,
				false,
				InvalidArgumentException::class
			)
		);
	}

	public function testAppendRow() {
		$htmlTable = new HtmlTableBuilder(
			array (
				'fu',
				'bar'
			)
		);
		$htmlTable->appendRow(
			array(
				'foo',
				'bar'
			)
		);

		$this->assertEquals(
			array (
				array (
					new HtmlTableCellBuilder( 'foo' ),
					new HtmlTableCellBuilder( 'bar' )
				)
			),
			$htmlTable->getRows()
		);
	}

	/**
	 * @dataProvider appendRowsDataProvider
	 */
	public function testAppendRows(
		array $rows,
		array $expectedRows = null,
		$expectedException = null
	) {
		if ( $expectedException ) {
			$this->setExpectedException( $expectedException );
		}

		$htmlTable = new HtmlTableBuilder(
			array (
				'fu',
				'bar'
			)
		);
		$htmlTable->appendRows( $rows );

		$this->assertEquals( $expectedRows, $htmlTable->getRows() );
	}

	/**
	 * Test cases for testAppendRows
	 *
	 * @return array
	 */
	public function appendRowsDataProvider() {
		return array(
			array(
				array(
					array(
						'foo',
						'bar'
					)
				),
				array (
					array (
						new HtmlTableCellBuilder( 'foo' ),
						new HtmlTableCellBuilder( 'bar' )
					)
				)
			),
			array (
				array (
					array (
						new HtmlTableCellBuilder( 'foo' ),
						'bar'
					)
				),
				array (
					array (
						new HtmlTableCellBuilder( 'foo' ),
						new HtmlTableCellBuilder( 'bar' )
					)
				)
			),
			array(
				array(
					array(
						'foo',
						42
					)
				),
				null,
				InvalidArgumentException::class
			),
			array(
				array(
					42
				),
				null,
				InvalidArgumentException::class
			)
		);
	}

	/**
	 * @dataProvider toHtmlDataProvider
	 */
	public function testToHtml( $headers, $rows, $expectedHtml ) {
		//Create table
		$htmlTable = new HtmlTableBuilder( $headers );
		$htmlTable->appendRows( $rows );

		// Run assertions
		$actualHtml = $htmlTable->toHtml();
		$this->assertEquals( $expectedHtml, $actualHtml );
	}

	/**
	 * @return array
	 */
	public function toHtmlDataProvider() {
		return array(
			array(
				array(
					$this->getHtmlTableHeaderMock( 'fu' ),
					$this->getHtmlTableHeaderMock( 'bar' )
				),
				array(
					array(
						$this->getHtmlTableCellMock( 'fucked up' ),
						$this->getHtmlTableCellMock( 'beyond all recognition' )
					)
				),
				'<table class="wikitable"><tr><th>fu</th><th>bar</th></tr><tr><td>fucked up</td><td>beyond all recognition</td></tr></table>'
			),
			array(
				array(
					$this->getHtmlTableHeaderMock( 'fu' ),
					$this->getHtmlTableHeaderMock( 'bar', true )
				),
				array(
					array(
						$this->getHtmlTableCellMock( 'fucked up' ),
						$this->getHtmlTableCellMock( 'beyond all recognition' )
					)
				),
				'<table class="wikitable sortable jquery-tablesort"><tr><th>fu</th><th>bar</th></tr><tr><td>fucked up</td><td>beyond all recognition</td></tr></table>'
			)
		);
	}

	/**
	 * Creates HtmlHeaderCell mock, which returns only the content when calling HtmlHeaderCell::toHtml()
	 *
	 * @param string $content
	 * @param bool $isSortable
	 *
	 * @return HtmlTableHeaderBuilder
	 */
	private function getHtmlTableHeaderMock( $content, $isSortable = false ) {
		$cellMock = $this
			->getMockBuilder( HtmlTableHeaderBuilder::class )
			->setConstructorArgs( array( $content, $isSortable ) )
			->setMethods( array( 'toHtml' ) )
			->getMock();
		$cellMock
			->expects( $this->any() )
			->method( 'toHtml' )
			->will( $this->returnValue( "<th>$content</th>" ) );

		return $cellMock;
	}

	/**
	 * Creates HtmlTableCell mock, which returns only the content when calling HtmlTableCell::toHtml()
	 *
	 * @param string $content
	 *
	 * @return HtmlTableCellBuilder
	 */
	private function getHtmlTableCellMock( $content ) {
		$cellMock = $this
			->getMockBuilder( HtmlTableCellBuilder::class )
			->setConstructorArgs( array( $content ) )
			->getMock();
		$cellMock
			->expects( $this->any() )
			->method( 'toHtml' )
			->will( $this->returnValue( "<td>$content</td>" ) );

		return $cellMock;
	}

}
