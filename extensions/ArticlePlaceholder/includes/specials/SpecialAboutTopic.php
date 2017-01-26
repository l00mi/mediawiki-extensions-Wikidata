<?php

namespace ArticlePlaceholder\Specials;

use Config;
use HTMLForm;
use MediaWiki\MediaWikiServices;
use SpecialPage;
use ArticlePlaceholder\AboutTopicRenderer;
use Wikibase\Client\Store\TitleFactory;
use Wikibase\Client\WikibaseClient;
use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\DataModel\Entity\EntityIdParsingException;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Services\Lookup\EntityLookup;
use Wikibase\Lib\Store\SiteLinkLookup;
use Wikimedia\Assert\Assert;

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
		$settings = $wikibaseClient->getSettings();

		// TODO: Remove the feature flag when not needed any more!
		$settings->setSetting( 'enableLuaEntityFormatStatements', true );

		$articlePlaceholderSearchEngineIndexed = MediaWikiServices::getInstance()->getMainConfig()->get(
			'ArticlePlaceholderSearchEngineIndexed'
		);

		return new self(
			new AboutTopicRenderer(
				$wikibaseClient->getLanguageFallbackLabelDescriptionLookupFactory(),
				$wikibaseClient->getStore()->getSiteLinkLookup(),
				MediaWikiServices::getInstance()->getSiteLookup(),
				$wikibaseClient->getLangLinkSiteGroup(),
				new TitleFactory(),
				$wikibaseClient->getOtherProjectsSidebarGeneratorFactory()
			),
			$wikibaseClient->getEntityIdParser(),
			$wikibaseClient->getStore()->getSiteLinkLookup(),
			new TitleFactory(),
			$settings->getSetting( 'siteGlobalID' ),
			$wikibaseClient->getStore()->getEntityLookup(),
			$articlePlaceholderSearchEngineIndexed
		);
	}

	/**
	 * @var AboutTopicRenderer
	 */
	private $aboutTopicRenderer;

	/**
	 * @var EntityIdParser
	 */
	private $idParser;

	/**
	 * @var SiteLinkLookup
	 */
	private $siteLinkLookup;

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
	 * @var bool|string $searchEngineIndexed
	 */
	private $searchEngineIndexed;

	/**
	 * @param AboutTopicRenderer $aboutTopicRenderer
	 * @param EntityIdParser $idParser
	 * @param SiteLinkLookup $siteLinkLookup
	 * @param TitleFactory $titleFactory
	 * @param string $siteGlobalID
	 * @param EntityLookup $entityLookup
	 * @param bool|string $searchEngineIndexed
	 * @throws InvalidArgumentException
	 */
	public function __construct(
		AboutTopicRenderer $aboutTopicRenderer,
		EntityIdParser $idParser,
		SiteLinkLookup $siteLinkLookup,
		TitleFactory $titleFactory,
		$siteGlobalID,
		EntityLookup $entityLookup,
		$searchEngineIndexed
	) {
		parent::__construct( 'AboutTopic' );

		Assert::parameterType(
			'boolean|string',
			$searchEngineIndexed,
			'$searchEngineIndexed'
		);

		$this->aboutTopicRenderer = $aboutTopicRenderer;
		$this->idParser = $idParser;
		$this->siteLinkLookup = $siteLinkLookup;
		$this->titleFactory = $titleFactory;
		$this->siteGlobalID = $siteGlobalID;
		$this->entityLookup = $entityLookup;
		$this->searchEngineIndexed = $searchEngineIndexed;
	}

	/**
	 * @param string|null $sub
	 */
	public function execute( $sub ) {
		$this->showContent( $sub );
	}

	/**
	 * @param string|null $itemIdString
	 */
	private function showContent( $itemIdString ) {
		$itemId = $this->getItemIdParam( 'entityid', $itemIdString );

		if ( $itemId !== null ) {
			$this->getOutput()->setProperty( 'wikibase_item', $itemId->getSerialization() );
		}
		$this->setHeaders();

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

		$articleOnWiki = $this->getArticleUrl( $itemId );

		if ( $articleOnWiki !== null ) {
			$this->getOutput()->redirect( $articleOnWiki );
		} else {
			$this->aboutTopicRenderer->showPlaceholder(
				$itemId,
				$this->getLanguage(),
				$this->getUser(),
				$this->getOutput()
			);
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
	 * @param ItemId $entityId
	 *
	 * @return string|null
	 */
	private function getArticleUrl( ItemId $entityId ) {
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

	/**
	 * @return string
	 */
	protected function getRobotPolicy() {
		if ( $this->searchEngineIndexed === true ) {
			return 'index,follow';
		}

		if ( is_string( $this->searchEngineIndexed ) ) {
			$wikibaseItem = $this->getOutput()->getProperty( 'wikibase_item' );

			$entityId = new ItemId( $wikibaseItem );

			$maxEntityId = new ItemId( $this->searchEngineIndexed );

			if ( $entityId->getNumericId() <= $maxEntityId->getNumericId() ) {
				return 'index,follow';
			}
		}

		return parent::getRobotPolicy();
	}

}
