<?php

namespace WikibaseQuality\Html;

use InvalidArgumentException;
use Html;

/**
 * @package WikibaseQuality\Html
 * @author BP2014N1
 * @license GNU GPL v2+
 */
class HtmlTableHeaderBuilder {

	/**
	 * Html content of the header
	 *
	 * @var string
	 */
	private $content;

	/**
	 * Determines, whether the column should be sortable or not.
	 *
	 * @var bool
	 */
	private $isSortable;

	/**
	 * Determines, whether the content is raw html or should be escaped.
	 *
	 * @var bool
	 */
	private $isRawContent;

	/**
	 * @param string $content HTML
	 * @param bool $isSortable
	 * @param bool $isRawContent
	 *
	 * @throws InvalidArgumentException
	 */
	public function __construct( $content, $isSortable = false, $isRawContent = false ) {
		if ( !is_string( $content ) ) {
			throw new InvalidArgumentException( '$content must be string.' );
		}
		if ( !is_bool( $isSortable ) ) {
			throw new InvalidArgumentException( '$isSortable must be boolean.' );
		}
		if ( !is_bool( $isRawContent ) ) {
			throw new InvalidArgumentException( '$isRawContent must be boolean.' );
		}

		$this->content = $content;
		$this->isSortable = $isSortable;
		$this->isRawContent = $isRawContent;
	}

	/**
	 * @return string
	 */
	public function getContent() {
		return $this->content;
	}

	/**
	 * @return bool
	 */
	public function getIsSortable() {
		return $this->isSortable;
	}

	/**
	 * Returns header as html.
	 *
	 * @return string HTML
	 */
	public function toHtml() {
		$attributes = array(
			'role' => 'columnheader button'
		);
		if ( !$this->isSortable ) {
			$attributes['class'] = 'unsortable';
		}

		$content = $this->content;
		if ( !$this->isRawContent ) {
			$content = htmlspecialchars( $this->content );
		}

		return
			Html::openElement(
				'th',
				$attributes
			)
			. $content
			. Html::closeElement('th');
	}
}
