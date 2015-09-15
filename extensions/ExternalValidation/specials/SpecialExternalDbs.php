<?php

namespace WikibaseQuality\ExternalValidation\Specials;


use Html;
use Language;
use Linker;
use SpecialPage;
use Wikibase\DataModel\Services\Lookup\LanguageLabelDescriptionLookup;
use Wikibase\DataModel\Services\Lookup\TermLookup;
use Wikibase\Lib\Store\EntityTitleLookup;
use Wikibase\Repo\EntityIdHtmlLinkFormatterFactory;
use Wikibase\Repo\WikibaseRepo;
use WikibaseQuality\ExternalValidation\DumpMetaInformation\DumpMetaInformation;
use WikibaseQuality\ExternalValidation\DumpMetaInformation\DumpMetaInformationLookup;
use WikibaseQuality\ExternalValidation\DumpMetaInformation\SqlDumpMetaInformationRepo;
use WikibaseQuality\ExternalValidation\ExternalValidationServices;
use WikibaseQuality\Html\HtmlTableBuilder;
use WikibaseQuality\Html\HtmlTableCellBuilder;


class SpecialExternalDbs extends SpecialPage {

	/**
	 * @var EntityIdHtmlLinkFormatterFactory
	 */
	private $entityIdLinkFormatter;

	/**
	 * @var SqlDumpMetaInformationRepo
	 */
	private $dumpMetaInformationRepo;

	/**
	 * Creates new instance from global state.
	 * @return SpecialExternalDbs
	 */
	public static function newFromGlobalState() {
		$repo = WikibaseRepo::getDefaultInstance();
		$externalValidationServices = ExternalValidationServices::getDefaultInstance();

		return new self(
			$repo->getTermLookup(),
			$repo->getEntityIdHtmlLinkFormatterFactory(),
			$externalValidationServices->getDumpMetaInformationLookup()
		);
	}

	/**
	 * @param TermLookup $termLookup
	 * @param EntityTitleLookup $entityTitleLookup
	 * @param DumpMetaInformationLookup $dumpMetaInformationRepo
	 */
	public function __construct(
		TermLookup $termLookup,
		EntityIdHtmlLinkFormatterFactory $entityIdHtmlLinkFormatterFactory,
		DumpMetaInformationLookup $dumpMetaInformationRepo ) {
		parent::__construct( 'ExternalDbs' );

		$this->entityIdLinkFormatter = $entityIdHtmlLinkFormatterFactory->getEntityIdFormatter(
			new LanguageLabelDescriptionLookup( $termLookup, $this->getLanguage()->getCode() )
		);

		$this->dumpMetaInformationRepo = $dumpMetaInformationRepo;
	}

	/**
	 * @see SpecialPage::getGroupName
	 *
	 * @return string
	 */
	function getGroupName() {
		return 'wikibasequality';
	}

	/**
	 * @see SpecialPage::getDescription
	 *
	 * @return string
	 */
	public function getDescription() {
		return $this->msg( 'wbqev-externaldbs' )->text();
	}

	/**
	 * @see SpecialPage::execute
	 *
	 * @param string|null $subPage
	 */
	public function execute( $subPage ) {
		$out = $this->getOutput();

		$this->setHeaders();

		$out->addHTML(
			Html::openElement( 'p' )
			. $this->msg( 'wbqev-externaldbs-instructions' )->parse()
			. Html::closeElement( 'p' )
			. Html::openElement( 'h3' )
			. $this->msg( 'wbqev-externaldbs-overview-headline' )->parse()
			. Html::closeElement( 'h3' )
		);

		$dumps = $this->dumpMetaInformationRepo->getAll();
		if ( count( $dumps ) > 0 ) {
			$groupedDumpMetaInformation = array();
			foreach ( $dumps as $dump ) {
				$sourceItemId = $dump->getSourceItemId()->getSerialization();
				$groupedDumpMetaInformation[$sourceItemId][] = $dump;
			}

			$table = new HtmlTableBuilder(
				array(
					$this->msg( 'wbqev-externaldbs-name' )->escaped(),
					$this->msg( 'wbqev-externaldbs-id' )->escaped(),
					$this->msg( 'wbqev-externaldbs-import-date' )->escaped(),
					$this->msg( 'wbqev-externaldbs-language' )->escaped(),
					$this->msg( 'wbqev-externaldbs-source-urls' )->escaped(),
					$this->msg( 'wbqev-externaldbs-size' )->escaped(),
					$this->msg( 'wbqev-externaldbs-license' )->escaped()
				),
				true
			);

			foreach ( $groupedDumpMetaInformation as $sourceItemId => $dumpMetaInformation ) {
				$table->appendRows( $this->getRowGroup( $dumpMetaInformation ) );
			}

			$out->addHTML( $table->toHtml() );
		} else {
			$out->addHTML(
				Html::openElement( 'p' )
				. $this->msg( 'wbqev-externaldbs-no-databases' )->escaped()
				. Html::closeElement( 'p' )
			);
		}
	}

	/**
	 * Build grouped rows for dump meta information with same source item id
	 *
	 * @param DumpMetaInformation[] $dumpMetaInformationGroup
	 *
	 * @return array
	 */
	private function getRowGroup( array $dumpMetaInformationGroup ) {
		$rows = array();

		foreach ( $dumpMetaInformationGroup as $dumpMetaInformation ) {
			$dumpId = $dumpMetaInformation->getDumpId();
			$importDate = $this->getLanguage()->timeanddate( $dumpMetaInformation->getImportDate() );
			$language = Language::fetchLanguageName(
				$dumpMetaInformation->getLanguageCode(),
				$this->getLanguage()->getCode()
			);
			$sourceUrl = Linker::makeExternalLink(
				$dumpMetaInformation->getSourceUrl(),
				$dumpMetaInformation->getSourceUrl()
			);
			$size = $this->getLanguage()->formatSize( $dumpMetaInformation->getSize() );
			$license = $this->entityIdLinkFormatter->formatEntityId( $dumpMetaInformation->getLicenseItemId() );
			$rows[] = array(
				new HtmlTableCellBuilder( $dumpId ),
				new HtmlTableCellBuilder( $importDate ),
				new HtmlTableCellBuilder( $language ),
				new HtmlTableCellBuilder( $sourceUrl, array(), true ),
				new HtmlTableCellBuilder( $size ),
				new HtmlTableCellBuilder( $license, array(), true )
			);
		}

		array_unshift(
			$rows[0],
			new HtmlTableCellBuilder(
				$this->entityIdLinkFormatter->formatEntityId( $dumpMetaInformationGroup[0]->getSourceItemId() ),
				array( 'rowspan' => (string)count( $dumpMetaInformationGroup ) ),
				true
			)
		);

		return $rows;
	}
}
