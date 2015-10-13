<?php

namespace WikibaseQuality\Html;

use InvalidArgumentException;
use Html;

/**
 * @package WikibaseQuality\Html
 * @author BP2014N1
 * @license GNU GPL v2+
 */
class HtmlTableBuilder {

	/**
	 * @var HtmlTableHeaderBuilder[]
	 */
	private $headers = array();

	/**
	 * Array of HtmlTableCellBuilder arrays
	 *
	 * @var array[]
	 */
	private $rows = array();

	/**
	 * @var bool
	 */
	private $isSortable;

	/**
	 * @param array $headers
	 * @throws InvalidArgumentException
	 */
	public function __construct( $headers ) {
		if ( !is_array( $headers ) ) {
			throw new InvalidArgumentException( '$headers must be an array of strings or HtmlTableHeader elements.' );
		}

		foreach ( $headers as $header ) {
			$this->addHeader( $header );
		}
	}

	/**
	 * @param string|HtmlTableHeaderBuilder $header
	 */
	private function addHeader( $header ) {
		if ( is_string( $header ) ) {
			$this->headers[] = new HtmlTableHeaderBuilder( $header );
		} elseif ( $header instanceof HtmlTableHeaderBuilder ) {
			$this->headers[] = $header;

			if ( $header->getIsSortable() ) {
				$this->isSortable = true;
			}
		} else {
			throw new InvalidArgumentException( 'Each element in $headers must be a string or an HtmlTableHeader' );
		}
	}

	/**
	 * @return HtmlTableHeaderBuilder
	 */
	public function getHeaders() {
		return $this->headers;
	}

	/**
	 * @return array[]
	 */
	public function getRows() {
		return $this->rows;
	}

	/**
	 * @return bool
	 */
	public function isSortable() {
		return $this->isSortable;
	}

	/**
	 * Adds row with specified cells to table.
	 *
	 * @param string[]|HtmlTableCellBuilder[] $cells
	 */
	public function appendRow( array $cells ) {
		foreach ( $cells as $key => $cell ) {
			if ( is_string( $cell ) ) {
				$cells[$key] = new HtmlTableCellBuilder( $cell );
			} else if ( !( $cell instanceof HtmlTableCellBuilder ) ) {
				throw new InvalidArgumentException( '$cells must be array of HtmlTableCell objects.' );
			}
		}

		$this->rows[] = $cells;
	}

	/**
	 * Adds rows with specified cells to table.
	 *
	 * @param array $rows
	 */
	public function appendRows( array $rows ) {
		foreach ( $rows as $cells ) {
			if ( !is_array( $cells ) ) {
				throw new InvalidArgumentException( '$rows must be array of arrays of HtmlTableCell objects.' );
			}

			$this->appendRow( $cells );
		}
	}

	/**
	 * Returns table as html.
	 *
	 * @return string
	 */
	public function toHtml() {
		// Open table
		$tableClasses = 'wikitable';
		if ( $this->isSortable ) {
			$tableClasses .= ' sortable jquery-tablesort';
		}
		$html = Html::openElement(
			'table',
			array(
				'class' => $tableClasses
			)
		);

		// Write headers
		$html .= Html::openElement( 'tr' );
		foreach ( $this->headers as $header ) {
			$html .= $header->toHtml();
		}
		$html .= Html::closeElement( 'tr' );

		// Write rows
		foreach ( $this->rows as $row ) {
			$html .= Html::openElement( 'tr' );

			/**
			 * @var HtmlTableCellBuilder $cell
			 */
			foreach ( $row as $cell ) {
				$html .= $cell->toHtml();
			}

			$html .= Html::closeElement( 'tr' );
		}

		// Close table
		$html .= Html::closeElement( 'table' );

		return $html;
	}
}