<?php

namespace ArticlePlaceholder\Specials;

use HTMLForm;
use OOUI;
use SiteStore;
use SpecialPage;
use Title;
use Wikibase\Client\Store\TitleFactory;
use Wikibase\Client\WikibaseClient;
use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\DataModel\Entity\EntityIdParsingException;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Services\Lookup\EntityLookup;
use Wikibase\Lib\Store\LanguageFallbackLabelDescriptionLookupFactory;
use Wikibase\Lib\Store\SiteLinkLookup;

/**
 * The AboutTopic SpecialPage for the ArticlePlaceholder extension
 *
 * @ingroup Extensions
 * @author Lucie-Aimée Kaffee
 * @license GNU General Public Licence 2.0 or later
 */
class SpecialAboutTopic extends SpecialPage {

	public static function newFromGlobalState() {
		$wikibaseClient = WikibaseClient::getDefaultInstance();
		return new self(
			$wikibaseClient->getEntityIdParser(),
			$wikibaseClient->getLanguageFallbackLabelDescriptionLookupFactory(),
			$wikibaseClient->getStore()->getSiteLinkLookup(),
			$wikibaseClient->getSiteStore(),
			new TitleFactory(),
			$wikibaseClient->getSettings()->getSetting( 'siteGlobalID' ),
			$wikibaseClient->getStore()->getEntityLookup()
		);
	}

	/**
	 * @var EntityIdParser
	 */
	private $idParser;

	/**
	 * @var LanguageFallbackLabelDescriptionLookupFactory
	 */
	private $termLookupFactory;

	/**
	 * @var SiteLinkLookup
	 */
	private $siteLinkLookup;

	/**
	 * @var SiteStore
	 */
	private $siteStore;

	/**
	 * @var TitleFactory
	 */
	private $titleFactory;

	/**
	 * @var string
	 */
	private $siteGlobalID;

	/**
	 * @var EntityLookup
	 */
	private $entityLookup;

	/**
	 * @param EntityIdParser $idParser
	 * @param LanguageFallbackLabelDescriptionLookupFactory $termLookupFactory
	 * @param SiteLinkLookup $siteLinkLookup
	 * @param SiteStore $siteStore
	 * @param TitleFactory $titleFactory
	 * @param string $siteGlobalID
	 * @param EntityLookup $entityLookup
	 */
	public function __construct(
		EntityIdParser $idParser,
		LanguageFallbackLabelDescriptionLookupFactory $termLookupFactory,
		SiteLinkLookup $siteLinkLookup,
		SiteStore $siteStore,
		TitleFactory $titleFactory,
		$siteGlobalID,
		EntityLookup $entityLookup
	) {
		parent::__construct( 'AboutTopic' );

		$this->idParser = $idParser;
		$this->termLookupFactory = $termLookupFactory;
		$this->siteLinkLookup = $siteLinkLookup;
		$this->siteStore = $siteStore;
		$this->titleFactory = $titleFactory;
		$this->siteGlobalID = $siteGlobalID;
		$this->entityLookup = $entityLookup;
	}

	/**
	 * @param string|null $sub
	 */
	public function execute( $sub ) {
		$this->setHeaders();
		$this->showContent( $sub );
	}

	/**
	 * @param string|null $itemIdString
	 */
	private function showContent( $itemIdString ) {
		$itemId = $this->getItemIdParam( 'entityid', $itemIdString );

		if ( $itemId === null ) {
			$this->createForm();
			return;
		}

		if ( !$this->entityLookup->hasEntity( $itemId ) ) {
			$this->createForm();
			$message = $this->msg( 'articleplaceholder-abouttopic-no-entity-error' );
			$this->getOutput()->addWikiText( $message->text() );
			return;
		}

		$articleOnWiki = $this->getArticleOnWiki( $itemId );

		if ( $articleOnWiki !== null ) {
			$this->getOutput()->redirect( $articleOnWiki );
		} else {
			$this->showPlaceholder( $itemId );
		}
	}

	/**
	 * @see SpecialPage::getDescription
	 *
	 * @return string
	 */
	public function getDescription() {
		return $this->msg( 'articleplaceholder-abouttopic' )->text();
	}

	protected function getGroupName() {
		return 'other';
	}

	/**
	 * Create html elements
	 */
	protected function createForm() {
		$form = HTMLForm::factory( 'ooui', [
			'text' => [
				'type' => 'text',
				'name' => 'entityid',
				'id' => 'ap-abouttopic-entityid',
				'cssclass' => 'ap-input',
				'label-message' => 'articleplaceholder-abouttopic-entityid',
				'default' => $this->getRequest()->getVal( 'entityid' ),
			]
		], $this->getContext() );

		$form
			->setMethod( 'get' )
			->setId( 'ap-abouttopic-form1' )
			->setHeaderText( $this->msg( 'articleplaceholder-abouttopic-intro' )->parse() )
			->setWrapperLegend( '' )
			->setSubmitTextMsg( 'articleplaceholder-abouttopic-submit' )
			->prepareForm()
			->displayForm( false );
	}

	private function getTextParam( $name, $fallback ) {
		$value = $this->getRequest()->getText( $name, $fallback );
		return trim( $value );
	}

	/**
	 * @param string $name
	 * @param string $fallback
	 *
	 * @return ItemId|null
	 * @throws @todo UserInputException
	 */
	private function getItemIdParam( $name, $fallback ) {
		$rawId = $this->getTextParam( $name, $fallback );

		if ( $rawId === '' ) {
			return null;
		}

		try {
			$id = $this->idParser->parse( $rawId );
			if ( !( $id instanceof ItemId ) ) {
				throw new EntityIdParsingException();
			}

			return $id;
		} catch ( EntityIdParsingException $ex ) {
			$message = $this->msg( 'articleplaceholder-abouttopic-no-entity-error' );
			$this->getOutput()->addWikiText( $message->text() );
		}

		return null;
	}

	/**
	 * Show placeholder and include template to call lua module
	 * @param ItemId $entityId
	 */
	private function showPlaceholder( ItemId $entityId ) {
		$this->getOutput()->addWikiText( '{{aboutTopic|' . $entityId->getSerialization() . '}}' );
		$label = $this->getLabel( $entityId );
		$this->showTitle( $label );
		$labelTitle = Title::newFromText( $label );
		if ( $labelTitle && $labelTitle->quickUserCan( 'createpage', $this->getUser() ) ) {
			$this->showCreateArticle( $labelTitle );
		}
		$this->showLanguageLinks( $entityId );
	}

	private function showCreateArticle( Title $labelTitle ) {
		$output = $this->getOutput();

		$output->enableOOUI();
		$output->addModuleStyles( 'ext.articleplaceholder.defaultDisplay' );
		$output->addModules( 'ext.articleplaceholder.createArticle' );
		$output->addJsConfigVars( 'apLabel', $labelTitle->getPrefixedText() );

		$button = new OOUI\ButtonWidget( [
			'id' => 'new-empty-article-button',
			'infusable' => true,
			'label' => $this->msg( 'articleplaceholder-abouttopic-create-article-button' )->text(),
			'href' => SpecialPage::getTitleFor( 'CreateTopicPage', $labelTitle->getPrefixedText() )
				->getLocalURL( [ 'ref' => 'button' ] ),
			'target' => 'blank'
		] );

		$output->addHTML( $button );
	}

	/**
	 * @param ItemId $entityId
	 * @return string|null label
	 */
	private function getLabel( ItemId $entityId ) {
		$label = $this->termLookupFactory->newLabelDescriptionLookup( $this->getLanguage() )
			->getLabel( $entityId );

		if ( $label !== null ) {
			return $label->getText();
		}

		return null;
	}

	/**
	 * Show label as page title
	 * @param string|null $label
	 */
	private function showTitle( $label ) {
		if ( $label !== null ) {
			$this->getOutput()->setPageTitle( htmlspecialchars( $label ) );
		}
	}

	/**
	 * Set language links
	 * @param ItemId $entityId
	 * @todo set links to other projects in sidebar, too!
	 */
	private function showLanguageLinks( ItemId $entityId ) {
		$siteLinks = $this->siteLinkLookup->getSiteLinksForItem( $entityId );
		$languageLinks = [];

		foreach ( $siteLinks as $siteLink ) {
			$languageCode = $this->siteStore->getSite( $siteLink->getSiteId() )->getLanguageCode();

			if ( $languageCode !== null ) {
				$languageLinks[$languageCode] = $languageCode . ':' . $siteLink->getPageName();
			}
		}

		$this->getOutput()->setLanguageLinks( $languageLinks );
	}

	/**
	 * @param ItemId $entityId
	 * @return Title
	 */
	private function getArticleOnWiki( ItemId $entityId ) {
		$sitelinkTitles = $this->siteLinkLookup->getLinks(
			[ $entityId->getNumericId() ],
			[ $this->siteGlobalID ]
		);

		if ( isset( $sitelinkTitles[0][1] ) ) {
			$sitelinkTitle = $sitelinkTitles[0][1];
			return $this->titleFactory->newFromText( $sitelinkTitle )->getLinkURL();
		}

		return null;
	}

}