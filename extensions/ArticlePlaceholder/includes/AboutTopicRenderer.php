<?php

namespace ArticlePlaceholder;

use ExtensionRegistry;
use Html;
use Language;
use OOUI;
use SiteLookup;
use SpecialPage;
use User;
use OutputPage;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Client\Store\TitleFactory;
use Wikibase\Client\Hooks\OtherProjectsSidebarGeneratorFactory;
use Wikibase\Lib\Store\LanguageFallbackLabelDescriptionLookupFactory;
use Wikibase\Lib\Store\SiteLinkLookup;

/**
 * The AboutTopic SpecialPage for the ArticlePlaceholder extension
 * The AboutTopicRenderer assumes the 'wikibase_item' OutputPage property
 * is set in SpecialAboutTopic
 *
 * @ingroup Extensions
 * @author Lucie-Aimée Kaffee
 * @license GNU General Public Licence 2.0 or later
 */
class AboutTopicRenderer {

	/**
	 * @var LanguageFallbackLabelDescriptionLookupFactory
	 */
	private $termLookupFactory;

	/**
	 * @var SiteLinkLookup
	 */
	private $siteLinkLookup;

	/**
	 * @var SiteLookup
	 */
	private $siteLookup;

	/**
	 * @var string
	 */
	private $langLinkSiteGroup;

	/**
	 * @var TitleFactory
	 */
	private $titleFactory;

	/**
	 * @var OtherProjectsSidebarGeneratorFactory
	 */
	private $otherProjectsSidebarGeneratorFactory;

	/**
	 * @param LanguageFallbackLabelDescriptionLookupFactory $termLookupFactory
	 * @param SiteLinkLookup $siteLinkLookup
	 * @param SiteLookup $siteLookup
	 * @param string $langLinkSiteGroup
	 * @param TitleFactory $titleFactory
	 * @param OtherProjectsSidebarGeneratorFactory $otherProjectsSidebarGeneratorFactory
	 */
	public function __construct(
		LanguageFallbackLabelDescriptionLookupFactory $termLookupFactory,
		SiteLinkLookup $siteLinkLookup,
		SiteLookup $siteLookup,
		$langLinkSiteGroup,
		TitleFactory $titleFactory,
		OtherProjectsSidebarGeneratorFactory $otherProjectsSidebarGeneratorFactory
	) {
		$this->termLookupFactory = $termLookupFactory;
		$this->siteLinkLookup = $siteLinkLookup;
		$this->siteLookup = $siteLookup;
		$this->langLinkSiteGroup = $langLinkSiteGroup;
		$this->titleFactory = $titleFactory;
		$this->otherProjectsSidebarGeneratorFactory = $otherProjectsSidebarGeneratorFactory;
	}

	/**
	 * Show content of the ArticlePlaceholder
	 *
	 * @param ItemId $entityId
	 * @param Language $language
	 * @param User $user
	 * @param OutputPage $output
	 */
	public function showPlaceholder(
		ItemId $entityId,
		Language $language,
		User $user,
		OutputPage $output
	) {
		$output->addModuleStyles( 'ext.articleplaceholder.defaultDisplay' );
		$output->addWikiText( '{{aboutTopic|' . $entityId->getSerialization() . '}}' );

		$label = $this->getLabel( $entityId, $language );
		$labelTitle = null;
		if ( $label ) {
			$this->showTitle( $label, $output );
			$labelTitle = $this->titleFactory->newFromText( $label );
		}
		if ( $labelTitle && $labelTitle->quickUserCan( 'createpage', $user ) ) {
			$this->showCreateArticle( $entityId, $label, $output );
		}

		$this->showLanguageLinks( $entityId, $output );
		$this->setOtherProjectsLinks( $entityId, $output );
	}

	/**
	 * Adds a button to create an article
	 *
	 * @param ItemId $itemId
	 * @param string $label
	 * @param OutputPage $output
	 */
	private function showCreateArticle( ItemId $itemId, $label, OutputPage $output ) {
		$siteLinks = $this->siteLinkLookup->getSiteLinksForItem( $itemId );

		$output->enableOOUI();
		$output->addModules( 'ext.articleplaceholder.createArticle' );
		$output->addJsConfigVars( 'apLabel', $label );

		$contents = new OOUI\ButtonWidget( [
			'id' => 'new-article-button',
			'flags' => [ 'primary', 'progressive' ],
			'infusable' => true,
			'label' => wfMessage( 'articleplaceholder-abouttopic-create-article-button' )->text(),
			'href' => SpecialPage::getTitleFor( 'CreateTopicPage', $label )
				->getLocalURL( [ 'ref' => 'button' ] ),
			'target' => 'blank'
		] );

		// TODO: Button should be hidden if the only sitelink links to the current wiki.
		// $wikibaseClient->getSettings()->getSetting( 'siteGlobalID' ) should be injected here!
		if ( ExtensionRegistry::getInstance()->isLoaded( 'ContentTranslation' ) && $siteLinks ) {
			$output->addJsConfigVars( 'apContentTranslation', true );
		}

		$output->addHTML( Html::rawElement(
			'div',
			[ 'class' => 'mw-articleplaceholder-createarticle-buttons' ],
			$contents
		) );
	}

	/**
	 * @param ItemId $entityId
	 * @param Language $language
	 *
	 * @return string|null null if the item doesn't have a label
	 */
	private function getLabel( ItemId $entityId, Language $language ) {
		$label = $this->termLookupFactory->newLabelDescriptionLookup( $language )
			->getLabel( $entityId );

		if ( $label !== null ) {
			return $label->getText();
		}

		return null;
	}

	/**
	 * Show label as page title
	 *
	 * @param string $label
	 * @param OutputPage $output
	 */
	private function showTitle( $label, OutputPage $output ) {
		$output->setPageTitle( htmlspecialchars( $label ) );
	}

	/**
	 * Set language links
	 *
	 * @param ItemId $entityId
	 * @param OutputPage $output
	 */
	private function showLanguageLinks( ItemId $entityId, OutputPage $output ) {
		$siteLinks = $this->siteLinkLookup->getSiteLinksForItem( $entityId );
		$languageLinks = [];
		$languageNames = [];
		$pageNames = [];

		foreach ( $siteLinks as $siteLink ) {
			$site = $this->siteLookup->getSite( $siteLink->getSiteId() );
			$languageCode = $site->getLanguageCode();
			$group = $site->getGroup();
			// TODO: This should not contain the current wiki.
			// $wikibaseClient->getSettings()->getSetting( 'siteGlobalID' ) should be injected here!
			if ( $languageCode !== null && $group === $this->langLinkSiteGroup ) {
				$languageLinks[$languageCode] = $languageCode . ':' . $siteLink->getPageName();

				// TODO: We may want to filter with user languages
				$languageNames[] = [
					'data' => $languageCode,
					'label' => Language::fetchLanguageName( $languageCode ),
				];
				$pageNames[ $languageCode ] = $siteLink->getPageName();
			}
		}

		$output->setLanguageLinks( $languageLinks );
		$output->addJsConfigVars( 'apLanguages', $languageNames );
		$output->addJsConfigVars( 'apPageNames', $pageNames );
	}

	/**
	 * Set other projects links
	 *
	 * @param ItemId $itemId
	 * @param OutputPage $output
	 */
	private function setOtherProjectsLinks( ItemId $itemId, OutputPage $output ) {
		$otherProjectsSidebarGenerator = $this->otherProjectsSidebarGeneratorFactory
			->getOtherProjectsSidebarGenerator();

		$otherProjects = $otherProjectsSidebarGenerator->buildProjectLinkSidebarFromItemId( $itemId );
		$output->setProperty( 'wikibase-otherprojects-sidebar', $otherProjects );
	}

}
