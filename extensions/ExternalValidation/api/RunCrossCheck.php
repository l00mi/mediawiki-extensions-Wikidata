<?php

namespace WikibaseQuality\ExternalValidation\Api;

use ApiBase;
use ApiMain;
use RequestContext;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\DataModel\Services\Statement\StatementGuidValidator;
use Wikibase\Repo\Api\ApiErrorReporter;
use Wikibase\Repo\Api\ApiHelperFactory;
use Wikibase\Repo\Api\ResultBuilder;
use Wikibase\Repo\WikibaseRepo;
use WikibaseQuality\ExternalValidation\CrossCheck\CrossCheckInteractor;
use WikibaseQuality\ExternalValidation\ExternalValidationServices;
use WikibaseQuality\ExternalValidation\Serializer\SerializerFactory;

/**
 * API module that performs cross-checks of entities or claims.
 *
 * @package WikibaseQuality\ExternalValidation\Api
 * @author BP2014N1
 * @license GNU GPL v2+
 */
class RunCrossCheck extends ApiBase {

	/**
	 * @var EntityIdParser
	 */
	private $entityIdParser;

	/**
	 * @var StatementGuidValidator
	 */
	private $statementGuidValidator;

	/**
	 * @var CrossCheckInteractor
	 */
	private $crossCheckInteractor;

	/**
	 * @var SerializerFactory
	 */
	private $serializerFactory;

	/**
	 * @var ApiErrorReporter
	 */
	private $errorReporter;

	/**
	 * @var ResultBuilder
	 */
	private $resultBuilder;

	/**
	 * Creates new instance from global state.
	 *
	 * @param ApiMain $main
	 * @param $name
	 * @param string $prefix
	 * @return RunCrossCheck
	 */
	public static function newFromGlobalState( ApiMain $main, $name, $prefix = '' ) {
		$repo = WikibaseRepo::getDefaultInstance();
		$externalValidationServices = ExternalValidationServices::getDefaultInstance();

		return new self(
			$main,
			$name,
			$prefix,
			$repo->getEntityIdParser(),
			$repo->getStatementGuidValidator(),
			$externalValidationServices->getCrossCheckInteractor(),
			$externalValidationServices->getSerializerFactory(),
			$repo->getApiHelperFactory( RequestContext::getMain() )
		);
	}

	/**
	 * @param ApiMain $main
	 * @param string $name
	 * @param string $prefix
	 * @param EntityIdParser $entityIdParser
	 * @param StatementGuidValidator $statementGuidValidator
	 * @param CrossCheckInteractor $crossCheckInteractor
	 * @param SerializerFactory $serializerFactory
	 * @param ApiHelperFactory $apiHelperFactory
	 */
	public function __construct( ApiMain $main, $name, $prefix = '', EntityIdParser $entityIdParser,
								 StatementGuidValidator $statementGuidValidator, CrossCheckInteractor $crossCheckInteractor,
								 SerializerFactory $serializerFactory, ApiHelperFactory $apiHelperFactory ) {
		parent::__construct( $main, $name, $prefix );

		$this->entityIdParser = $entityIdParser;
		$this->statementGuidValidator = $statementGuidValidator;
		$this->crossCheckInteractor = $crossCheckInteractor;
		$this->serializerFactory = $serializerFactory;
		$this->resultBuilder = $apiHelperFactory->getResultBuilder( $this );
		$this->errorReporter = $apiHelperFactory->getErrorReporter( $this );
	}

	/**
	 * Evaluates the parameters, runs the requested crosscheck, and sets up the result
	 */
	public function execute() {
		$params = $this->extractRequestParams();

		if ( $params['entities'] && $params['claims'] ) {
			$this->errorReporter->dieError(
				'Either provide the ids of entities or ids of claims, that should be cross-checked.',
				'param-invalid'
			);
		} elseif ( $params['entities'] ) {
			$entityIds = $this->parseEntityIds( $params['entities'] );
			if ( $params['properties'] ) {
				$propertyIds = $this->parseEntityIds( $params['properties'] );
				$resultLists = $this->crossCheckInteractor->crossCheckEntitiesByIdWithProperties( $entityIds, $propertyIds );
			} else {
				$resultLists = $this->crossCheckInteractor->crossCheckEntitiesByIds( $entityIds );
			}
		} elseif ( $params['claims'] ) {
			$guids = $params['claims'];
			$this->assertAreValidClaimGuids( $guids );
			$resultLists = $this->crossCheckInteractor->crossCheckStatementsByGuids( $guids );
		} else {
			$this->errorReporter->dieError(
				'Either provide the ids of entities or ids of claims, that should be cross-checked.',
				'param-missing'
			);
		}

		// Print result lists
		$this->writeResultOutput( $resultLists );
	}

	/**
	 * @param string[] $entityIds
	 *
	 * @return EntityId[]
	 */
	private function parseEntityIds( array $entityIds ) {
		return array_map(
			array( $this->entityIdParser, 'parse' ),
			$entityIds
		);
	}

	/**
	 * @param string[] $guids
	 */
	private function assertAreValidClaimGuids( array $guids ) {
		foreach ( $guids as $guid ) {
			if ( $this->statementGuidValidator->validateFormat( $guid ) === false ) {
				$this->errorReporter->dieError( 'Invalid claim guid.', 'invalid-guid' );
			}
		}
	}

	/**
	 * Writes output for CrossCheckResultList
	 *
	 * @param array $resultLists
	 *
	 * @return array
	 */
	private function writeResultOutput( array $resultLists ) {
		$serializer = $this->serializerFactory->newCrossCheckResultListSerializer();

		$output = array();
		foreach ( $resultLists as $entityId => $resultList ) {
			if ( $resultList ) {
				$serializedResultList = $serializer->serialize( $resultList );

				$output[$entityId] = $serializedResultList;
			} else {
				$output[$entityId] = array(
					'missing' => ''
				);
			}
		}

		$this->getResult()->setIndexedTagName( $output, 'entity' );
		$this->getResult()->setArrayType( $output, 'kvp', 'id' );
		$this->getResult()->addValue( null, 'results', $output );
		$this->resultBuilder->markSuccess( 1 );
	}

	/**
	 * Returns an array of allowed parameters
	 *
	 * @return array
	 * @codeCoverageIgnore
	 */
	public function getAllowedParams() {
		return array(
			'entities' => array(
				ApiBase::PARAM_TYPE => 'string',
				ApiBase::PARAM_ISMULTI => true
			),
			'properties' => array(
				ApiBase::PARAM_TYPE => 'string',
				ApiBase::PARAM_ISMULTI => true
			),
			'claims' => array(
				ApiBase::PARAM_TYPE => 'string',
				ApiBase::PARAM_ISMULTI => true
			)
		);
	}

	/**
	 * Returns usage examples for this module
	 *
	 * @return array
	 * @codeCoverageIgnore
	 */
	public function getExamplesMessages() {
		return array(
			'action=wbqevcrosscheck&entities=Q76' => 'apihelp-wbqevcrosscheck-examples-1',
			'action=wbqevcrosscheck&entities=Q76|Q567' => 'apihelp-wbqevcrosscheck-examples-2',
			'action=wbqevcrosscheck&entities=Q76|Q567&properties=P19' => 'apihelp-wbqevcrosscheck-examples-3',
			'action=wbqevcrosscheck&entities=Q76|Q567&properties=P19|P31' => 'apihelp-wbqevcrosscheck-examples-4',
			'action=wbqevcrosscheck&claims=Q42$D8404CDA-25E4-4334-AF13-A3290BCD9C0F' => 'apihelp-wbqevcrosscheck-examples-5'
		);
	}

}
