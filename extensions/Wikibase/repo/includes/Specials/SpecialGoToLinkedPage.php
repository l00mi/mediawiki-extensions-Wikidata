<?php

namespace Wikibase\Repo\Specials;

use HTMLForm;
use SiteLookup;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\DataModel\Entity\EntityIdParsingException;
use Wikibase\DataModel\Services\Lookup\EntityLookup;
use Wikibase\DataModel\Services\Lookup\EntityLookupException;
use Wikibase\DataModel\Services\Lookup\EntityRedirectLookup;
use Wikibase\Lib\Store\SiteLinkLookup;

/**
 * Enables accessing a linked page on a site by providing the item id and site
 * id.
 *
 * @license GPL-2.0+
 * @author Jan Zerebecki
 */
class SpecialGoToLinkedPage extends SpecialWikibasePage {

	/**
	 * @var SiteLookup
	 */
	private $siteLookup;

	/**
	 * @var SiteLinkLookup
	 */
	private $siteLinkLookup;

	/**
	 * @var EntityRedirectLookup
	 */
	private $redirectLookup;

	/**
	 * @var EntityIdParser
	 */
	private $idParser;

	/**
	 * @var EntityLookup
	 */
	private $entityLookup;

	/**
	 * @var string|null
	 */
	private $errorMessageKey = null;

	/**
	 * @see SpecialWikibasePage::__construct
	 *
	 * @param SiteLookup $siteLookup
	 * @param SiteLinkLookup $siteLinkLookup
	 * @param EntityRedirectLookup $redirectLookup
	 * @param EntityIdParser $idParser
	 * @param EntityLookup $entityLookup
	 */
	public function __construct(
		SiteLookup $siteLookup,
		SiteLinkLookup $siteLinkLookup,
		EntityRedirectLookup $redirectLookup,
		EntityIdParser $idParser,
		EntityLookup $entityLookup
	) {
		parent::__construct( 'GoToLinkedPage' );

		$this->siteLookup = $siteLookup;
		$this->siteLinkLookup = $siteLinkLookup;
		$this->redirectLookup = $redirectLookup;
		$this->idParser = $idParser;
		$this->entityLookup = $entityLookup;
	}

	/**
	 * @param string|null $subPage
	 *
	 * @return array array( string $site, string $itemString )
	 */
	private function getArguments( $subPage ) {
		$request = $this->getRequest();
		$parts = ( $subPage === '' ) ? array() : explode( '/', $subPage, 2 );
		$site = trim( $request->getVal( 'site', isset( $parts[0] ) ? $parts[0] : '' ) );
		$itemString = trim( $request->getVal( 'itemid', isset( $parts[1] ) ? $parts[1] : 0 ) );

		return array( $site, $itemString );
	}

	/**
	 * @param string $site
	 * @param string|null $itemString
	 *
	 * @return string|null the URL to redirect to or null if the sitelink does not exist
	 */
	private function getTargetUrl( $site, $itemString = null ) {
		$itemId = $this->getItemId( $itemString );

		if ( $site === '' || $itemId === null ) {
			return null;
		}

		$site = $this->stringNormalizer->trimToNFC( $site );

		if ( !$this->siteLookup->getSite( $site ) ) {
			// HACK: If the site ID isn't known, add "wiki" to it; this allows the wikipedia
			// subdomains to be used to refer to wikipedias, instead of requiring their
			// full global id to be used.
			// @todo: Ideally, if the site can't be looked up by global ID, we
			// should try to look it up by local navigation ID.
			// Support for this depends on bug T50934.
			$site .= 'wiki';
		}

		$links = $this->loadLinks( $site, $itemId );

		if ( isset( $links[0] ) ) {
			list( , $pageName, ) = $links[0];
			$siteObj = $this->siteLookup->getSite( $site );
			$url = $siteObj->getPageUrl( $pageName );
			return $url;
		} else {
			$this->errorMessageKey = "page-not-found";
		}

		return null;
	}

	/**
	 * Parses a string to itemId
	 *
	 * @param string $itemString
	 *
	 * @return ItemId|null
	 */
	private function getItemId( $itemString ) {
		try {
			$itemId = $this->idParser->parse( $itemString );

			if ( $itemId instanceof ItemId && $this->entityLookup->hasEntity( $itemId ) ) {
				return $itemId;
			}

			$this->errorMessageKey = 'item-not-found';
		} catch ( EntityIdParsingException $e ) {
			$this->errorMessageKey = 'item-id-invalid';
		} catch ( EntityLookupException $e ) {
			$this->errorMessageKey = 'item-not-found';
		}

		return null;
	}

	/**
	 * Load the sitelink using a SiteLinkLookup. Resolves item redirects, if needed.
	 *
	 * @param string $site
	 * @param ItemId $itemId
	 *
	 * @return array[]
	 */
	private function loadLinks( $site, ItemId $itemId ) {
		$links = $this->siteLinkLookup->getLinks( array( $itemId->getNumericId() ), array( $site ) );
		if ( isset( $links[0] ) ) {
			return $links;
		}

		// Maybe the item is a redirect: Try to resolve the redirect and load
		// the links from there.
		$redirectTarget = $this->redirectLookup->getRedirectForEntityId( $itemId );

		if ( $redirectTarget instanceof ItemId ) {
			return $this->siteLinkLookup->getLinks( array( $redirectTarget->getNumericId() ), array( $site ) );
		}

		return array();
	}

	/**
	 * @see SpecialWikibasePage::execute
	 *
	 * @param string|null $subPage
	 */
	public function execute( $subPage ) {
		parent::execute( $subPage );
		list( $site, $itemString ) = $this->getArguments( $subPage );

		if ( !empty( $site ) || !empty( $itemString ) ) {
			$url = $this->getTargetUrl( $site, $itemString );
			if ( null !== $url ) {
				$this->getOutput()->redirect( $url );
				return;
			}
		}

		$this->outputError();
		$this->outputForm( $site, $itemString );
	}

	/**
	 * Output a form via the context's OutputPage object to go to a
	 * sitelink (linked page) for an item and site id.
	 *
	 * @param string $site
	 * @param string $itemString
	 */
	private function outputForm( $site, $itemString ) {
		$formDescriptor = array(
			'site' => array(
				'name' => 'site',
				'default' => $site ?: '',
				'type' => 'text',
				'id' => 'wb-gotolinkedpage-sitename',
				'size' => 12,
				'label-message' => 'wikibase-gotolinkedpage-lookup-site'
			),
			'itemid' => array(
				'name' => 'itemid',
				'default' => $itemString ?: '',
				'type' => 'text',
				'id' => 'wb-gotolinkedpage-itemid',
				'size' => 36,
				'label-message' => 'wikibase-gotolinkedpage-lookup-item'
			)
		);

		HTMLForm::factory( 'ooui', $formDescriptor, $this->getContext() )
			->setId( 'wb-gotolinkedpage-form1' )
			->setMethod( 'get' )
			->setSubmitID( 'wb-gotolinkedpage-submit' )
			->setSubmitTextMsg( 'wikibase-gotolinkedpage-submit' )
			->setWrapperLegendMsg( 'wikibase-gotolinkedpage-lookup-fieldset' )
			->setSubmitCallback( function () {// no-op
			} )->show();
	}

	/**
	 * Outputs an error message
	 */
	private function outputError() {
		if ( $this->errorMessageKey !== null ) {
			$this->showErrorHTML(
				$this->msg( 'wikibase-gotolinkedpage-error-' . $this->errorMessageKey ) );
		}
	}

}
