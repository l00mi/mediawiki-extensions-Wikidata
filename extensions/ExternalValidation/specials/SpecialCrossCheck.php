<?php

namespace WikibaseQuality\ExternalValidation\Specials;

use DataValues\DataValue;
use InvalidArgumentException;
use UnexpectedValueException;
use Html;
use HTMLForm;
use Linker;
use SpecialPage;
use ValueFormatters\FormatterOptions;
use ValueFormatters\ValueFormatter;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\DataModel\Entity\EntityIdParsingException;
use Wikibase\DataModel\Entity\EntityIdValue;
use Wikibase\DataModel\Services\EntityId\EntityIdFormatter;
use Wikibase\DataModel\Services\Lookup\EntityLookup;
use Wikibase\DataModel\Services\Lookup\LanguageLabelDescriptionLookup;
use Wikibase\DataModel\Services\Lookup\TermLookup;
use Wikibase\DataModel\Statement\StatementListProvider;
use Wikibase\Lib\OutputFormatValueFormatterFactory;
use Wikibase\Lib\SnakFormatter;
use Wikibase\Repo\EntityIdHtmlLinkFormatterFactory;
use Wikibase\Repo\EntityIdLabelFormatterFactory;
use Wikibase\Repo\WikibaseRepo;
use WikibaseQuality\ExternalValidation\CrossCheck\CrossCheckInteractor;
use WikibaseQuality\ExternalValidation\CrossCheck\Result\CrossCheckResult;
use WikibaseQuality\ExternalValidation\ExternalValidationServices;
use WikibaseQuality\Html\HtmlTableBuilder;
use WikibaseQuality\Html\HtmlTableCellBuilder;
use WikibaseQuality\Html\HtmlTableHeaderBuilder;

class SpecialCrossCheck extends SpecialPage {

	/**
	 * @var EntityIdParser
	 */
	private $entityIdParser;

	/**
	 * @var EntityLookup
	 */
	private $entityLookup;

	/**
	 * @var ValueFormatter
	 */
	private $dataValueFormatter;

	/**
	 * @var EntityIdFormatter
	 */
	private $entityIdLabelFormatter;

	/**
	 * @var EntityIdFormatter
	 */
	private $entityIdLinkFormatter;

	/**
	 * @var CrossCheckInteractor
	 */
	private $crossCheckInteractor;

	/**
	 * Creates new instance from global state.
	 * @return self
	 */
	public static function newFromGlobalState() {
		$repo = WikibaseRepo::getDefaultInstance();
		$externalValidationServices = ExternalValidationServices::getDefaultInstance();

		return new self(
			$repo->getEntityLookup(),
			$repo->getTermLookup(),
			new EntityIdLabelFormatterFactory(),
			$repo->getEntityIdHtmlLinkFormatterFactory(),
			$repo->getEntityIdParser(),
			$repo->getValueFormatterFactory(),
			$externalValidationServices->getCrossCheckInteractor()
		);
	}

	/**
	 * @param EntityLookup $entityLookup
	 * @param TermLookup $termLookup
	 * @param EntityIdLabelFormatterFactory $entityIdLabelFormatterFactory
	 * @param EntityIdHtmlLinkFormatterFactory $entityIdHtmlLinkFormatterFactory
	 * @param EntityIdParser $entityIdParser
	 * @param OutputFormatValueFormatterFactory $valueFormatterFactory
	 * @param CrossCheckInteractor $crossCheckInteractor
	 */
	public function __construct(
		EntityLookup $entityLookup,
		TermLookup $termLookup,
		EntityIdLabelFormatterFactory $entityIdLabelFormatterFactory,
		EntityIdHtmlLinkFormatterFactory $entityIdHtmlLinkFormatterFactory,
		EntityIdParser $entityIdParser,
		OutputFormatValueFormatterFactory $valueFormatterFactory,
		CrossCheckInteractor $crossCheckInteractor
	) {
		parent::__construct( 'CrossCheck' );

		$this->entityLookup = $entityLookup;
		$this->entityIdParser = $entityIdParser;

		$formatterOptions = new FormatterOptions();
		$formatterOptions->setOption( SnakFormatter::OPT_LANG, $this->getLanguage()->getCode() );
		$this->dataValueFormatter = $valueFormatterFactory->getValueFormatter( SnakFormatter::FORMAT_HTML, $formatterOptions );

		$labelLookup = new LanguageLabelDescriptionLookup( $termLookup, $this->getLanguage()->getCode() );
		$this->entityIdLabelFormatter = $entityIdLabelFormatterFactory->getEntityIdFormatter( $labelLookup );
		$this->entityIdLinkFormatter = $entityIdHtmlLinkFormatterFactory->getEntityIdFormatter( $labelLookup );

		$this->crossCheckInteractor = $crossCheckInteractor;
	}

	/**
	 * @see SpecialPage::getGroupName
	 *
	 * @return string
	 */
	public function getGroupName() {
		return 'wikibasequality';
	}

	/**
	 * @see SpecialPage::getDescription
	 *
	 * @return string (plain text)
	 */
	public function getDescription() {
		return $this->msg( 'wbqev-crosscheck' )->text();
	}

	/**
	 * @see SpecialPage::execute
	 *
	 * @param string|null $subPage
	 *
	 * @throws InvalidArgumentException
	 * @throws EntityIdParsingException
	 * @throws UnexpectedValueException
	 */
	public function execute( $subPage ) {
		$out = $this->getOutput();
		$postRequest = $this->getContext()->getRequest()->getVal( 'entityid' );
		if ( $postRequest ) {
			$out->redirect( $this->getPageTitle( strtoupper( $postRequest ) )->getLocalURL() );
			return;
		}

		$out->addModules( 'SpecialCrossCheckPage' );

		$this->setHeaders();

		$out->addHTML( $this->buildInfoBox() );
		$this->buildEntityIdForm();

		if ( $subPage ) {
			$this->buildResult( $subPage );
		}
	}

	/**
	 * @param string $idSerialization
	 */
	private function buildResult( $idSerialization ) {
		$out = $this->getOutput();

		try {
			$entityId = $this->entityIdParser->parse( $idSerialization );
		} catch ( EntityIdParsingException $ex ) {
			$out->addHTML( $this->buildNotice( 'wbqev-crosscheck-invalid-entity-id', true ) );
			return;
		}

		$out->addHTML( $this->buildResultHeader( $entityId ) );

		$entity = $this->entityLookup->getEntity( $entityId );
		if ( $entity === null ) {
			$out->addHTML( $this->buildNotice( 'wbqev-crosscheck-not-existent-entity', true ) );
			return;
		}

		$results = $this->getCrossCheckResultsFromEntity( $entity );

		if ( $results === null || $results->toArray() === array() ) {
			$out->addHTML( $this->buildNotice( 'wbqev-crosscheck-empty-result' ) );
		} else {
			$out->addHTML(
				$this->buildSummary( $results )
				. $this->buildResultTable( $results )
			);
		}
	}

	/**
	 * @param EntityDocument $entity
	 *
	 * @return CrossCheckResultList|null
	 */
	private function getCrossCheckResultsFromEntity( EntityDocument $entity ) {
		if ( $entity instanceof StatementListProvider ) {
			return $this->crossCheckInteractor->crossCheckStatementList( $entity->getStatements() );
		}

		return null;
	}

	/**
	 * Builds html form for entity id input
	 */
	private function buildEntityIdForm() {
		$formDescriptor = array(
			'entityid' => array(
				'class' => 'HTMLTextField',
				'section' => 'section',
				'name' => 'entityid',
				'label-message' => 'wbqev-crosscheck-form-entityid-label',
				'cssclass' => 'wbqev-crosscheck-form-entity-id',
				'placeholder' => $this->msg( 'wbqev-crosscheck-form-entityid-placeholder' )->escaped()
			)
		);
		$htmlForm = new HTMLForm( $formDescriptor, $this->getContext(), 'wbqev-crosscheck-form' );
		$htmlForm->setSubmitText( $this->msg( 'wbqev-crosscheck-form-submit-label' )->escaped() );
		$htmlForm->setSubmitCallback( function() {
			return false;
		} );
		$htmlForm->setMethod( 'post' );
		$htmlForm->show();
	}

	/**
	 * Builds infobox with explanation for this special page
	 *
	 * @return string HTML
	 */
	private function buildInfoBox() {
		$externalDbLink = Linker::specialLink( 'ExternalDbs', 'wbqev-externaldbs' );
		$infoBox =
			Html::openElement(
				'div',
				array( 'class' => 'wbqev-infobox' )
			)
			. $this->msg( 'wbqev-crosscheck-explanation-general' )->parse()
			. sprintf( ' %s.', $externalDbLink )
			. Html::element( 'br' )
			. Html::element( 'br' )
			. $this->msg( 'wbqev-crosscheck-explanation-detail' )->parse()
			. Html::closeElement( 'div' );

		return $infoBox;
	}

	/**
	 * Builds notice with given message. Optionally notice can be handles as error by settings $error to true
	 *
	 * @param string $messageKey
	 * @param bool $error
	 *
	 * @throws InvalidArgumentException
	 *
	 * @return string HTML
	 */
	private function buildNotice( $messageKey, $error = false ) {
		$cssClasses = 'wbqev-crosscheck-notice';
		if ( $error ) {
			$cssClasses .= ' wbqev-crosscheck-notice-error';
		}

		return
			Html::element(
				'p',
				array( 'class' => $cssClasses ),
				$this->msg( $messageKey )->text()
			);
	}

	/**
	 * Returns html text of the result header
	 *
	 * @param EntityId $entityId
	 *
	 * @return string HTML
	 */
	private function buildResultHeader( EntityId $entityId ) {
		$entityLink = sprintf(
			'%s (%s)',
			$this->entityIdLinkFormatter->formatEntityId( $entityId ),
			htmlspecialchars( $entityId->getSerialization() )
		);

		return
			Html::rawElement(
				'h3',
				array(),
				sprintf( '%s %s', $this->msg( 'wbqev-crosscheck-result-headline' )->escaped(), $entityLink )
			);
	}

	/**
	 * Builds summary from given results
	 *
	 * @param CrossCheckResult[] $results
	 *
	 * @return string HTML
	 */
	private function buildSummary( $results ) {
		$statuses = array();
		foreach ( $results as $result ) {
			$status = strtolower( $result->getComparisonResult()->getStatus() );
			if ( array_key_exists( $status, $statuses ) ) {
				$statuses[$status]++;
			} else {
				$statuses[$status] = 1;
			}
		}

		$statusElements = array();
		foreach ( $statuses as $status => $count ) {
			if ( $count > 0 ) {
				$statusElements[] = $this->formatStatus( $status ) . ': ' . $count;
			}
		}
		$summary =
			Html::openElement( 'p' )
			. implode( ', ', $statusElements )
			. Html::closeElement( 'p' );

		return $summary;
	}

	/**
	 * Formats given status to html
	 *
	 * @param string $status (plain text)
	 *
	 * @throws InvalidArgumentException
	 *
	 * @return string HTML
	 */
	private function formatStatus( $status ) {
		$messageKey = 'wbqev-crosscheck-status-' . strtolower( $status );

		$formattedStatus =
			Html::element(
				'span',
				array (
					'class' => 'wbqev-status wbqev-status-' . htmlspecialchars( $status )
				),
				$this->msg( $messageKey )->text()
			);

		return $formattedStatus;
	}

	/**
	 * Parses data values to human-readable string
	 *
	 * @param DataValue|array $dataValues
	 * @param bool $linking
	 * @param string $separator HTML
	 *
	 * @throws InvalidArgumentException
	 *
	 * @return string HTML
	 */
	private function formatDataValues( $dataValues, $linking = true, $separator = null ) {
		if ( $dataValues instanceof DataValue ) {
			$dataValues = array( $dataValues );
		}

		$formattedDataValues = array();
		foreach ( $dataValues as $dataValue ) {
			if ( $dataValue instanceof EntityIdValue ) {
				if ( $linking ) {
					$formattedDataValues[] = $this->entityIdLinkFormatter->formatEntityId( $dataValue->getEntityId() );
				} else {
					$formattedDataValues[] = $this->entityIdLabelFormatter->formatEntityId( $dataValue->getEntityId() );
				}
			} else {
				$formattedDataValues[] = $this->dataValueFormatter->format( $dataValue );
			}
		}

		if ( $separator ) {
			return implode( $separator, $formattedDataValues );
		}

		return $this->getLanguage()->commaList( $formattedDataValues );
	}

	/**
	 * @param CrossCheckResult[] $results
	 *
	 * @return string HTML
	 */
	private function buildResultTable( $results ) {
		$table = new HtmlTableBuilder(
			array(
				new HtmlTableHeaderBuilder(
					$this->msg( 'wbqev-crosscheck-result-table-header-status' )->escaped(),
					true
				),
				new HtmlTableHeaderBuilder(
					$this->msg( 'datatypes-type-wikibase-property' )->escaped(),
					true
				),
				new HtmlTableHeaderBuilder(
					$this->msg( 'wbqev-crosscheck-result-table-header-local-value' )->escaped()
				),
				new HtmlTableHeaderBuilder(
					$this->msg( 'wbqev-crosscheck-result-table-header-external-value' )->escaped()
				),
				new HtmlTableHeaderBuilder(
					$this->msg( 'wbqev-crosscheck-result-table-header-references' )->escaped(),
					true
				),
				new HtmlTableHeaderBuilder(
					Linker::linkKnown(
						self::getTitleFor( 'ExternalDbs' ),
						$this->msg( 'wbqev-crosscheck-result-table-header-external-source' )->escaped()
					),
					true,
					true
				)
			),
			true
		);

		foreach ( $results as $result ) {
			$status = $this->formatStatus( $result->getComparisonResult()->getStatus() );
			$propertyId = $this->entityIdLinkFormatter->formatEntityId( $result->getPropertyId() );
			$localValue = $this->formatDataValues( $result->getComparisonResult()->getLocalValue() );
			$externalValue = $this->formatDataValues(
				$result->getComparisonResult()->getExternalValues(),
				true,
				Html::element( 'br' )
			);
			$referenceStatus = $this->msg(
				'wbqev-crosscheck-status-' . $result->getReferenceResult()->getStatus()
			)->text();
			$dataSource = $this->entityIdLinkFormatter->formatEntityId( $result->getDumpMetaInformation()->getSourceItemId() );

			$table->appendRow(
				array(
					new HtmlTableCellBuilder( $status, array(), true ),
					new HtmlTableCellBuilder( $propertyId, array(), true ),
					new HtmlTableCellBuilder( $localValue, array(), true ),
					new HtmlTableCellBuilder( $externalValue, array(), true ),
					new HtmlTableCellBuilder( $referenceStatus, array() ),
					new HtmlTableCellBuilder( $dataSource, array(), true )
				)
			);
		}

		return $table->toHtml();
	}

}
