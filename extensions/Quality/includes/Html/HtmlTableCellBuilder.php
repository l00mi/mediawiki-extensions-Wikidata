<?php

namespace WikibaseQuality\Html;

use InvalidArgumentException;
use Html;

/**
 * @package WikibaseQuality\Html
 * @author BP2014N1
 * @license GNU GPL v2+
 */
class HtmlTableCellBuilder {

	/**
	 * Html content of the cell.
	 *
	 * @var string
	 */
	private $content;

	/**
	 * @var array
	 */
	private $attributes;

	/**
	 * Determines, whether the content is raw html or should be escaped.
	 *
	 * @var bool
	 */
	private $isRawContent;

	/**
	 * @param string HTML $content
	 * @param array $attributes
	 * @param bool $isRawContent
	 *
	 * @throws InvalidArgumentException
	 */
	public function __construct( $content, array $attributes = array(), $isRawContent = false ) {
		// Check parameters
		if ( !is_string( $content ) ) {
			throw new InvalidArgumentException( '$content must be string.' );
		}
		if ( !is_bool( $isRawContent ) ) {
			throw new InvalidArgumentException( '$isRawContent must be boolean.' );
		}

		$this->content = $content;
		$this->attributes = $attributes;
		$this->isRawContent = $isRawContent;
	}

	/**
	 * @return string HTML
	 */
	public function getContent() {
		return $this->content;
	}

	/**
	 * @return array
	 */
	public function getAttributes() {
		return $this->attributes;
	}

	/**
	 * Returns cell as html.
	 *
	 * @return string HTML
	 */
	public function toHtml() {
		$content = $this->content;
		if ( !$this->isRawContent ) {
			$content = htmlspecialchars( $this->content );
		}

		return
			Html::openElement(
				'td',
				$this->getAttributes()
			)
			. $content
			. Html::closeElement( 'td' );
	}
}
