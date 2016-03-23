<?php

namespace WikibaseQuality\ExternalValidation\CrossCheck;

use DataValues\DataValue;
use DataValues\StringValue;
use InvalidArgumentException;
use ValueParsers\ParseException;
use Wikibase\DataModel\Entity\EntityIdValue;
use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Services\Lookup\EntityLookup;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\DataModel\Statement\Statement;
use Wikibase\DataModel\Statement\StatementList;
use WikibaseQuality\ExternalValidation\CrossCheck\ValueParser\ComparativeValueParser;
use WikibaseQuality\ExternalValidation\CrossCheck\ValueParser\ComparativeValueParserFactory;
use WikibaseQuality\ExternalValidation\CrossCheck\Comparer\DataValueComparer;
use WikibaseQuality\ExternalValidation\CrossCheck\Result\ComparisonResult;
use WikibaseQuality\ExternalValidation\CrossCheck\Result\CrossCheckResult;
use WikibaseQuality\ExternalValidation\CrossCheck\Result\CrossCheckResultList;
use WikibaseQuality\ExternalValidation\DumpMetaInformation\DumpMetaInformation;
use WikibaseQuality\ExternalValidation\DumpMetaInformation\DumpMetaInformationLookup;
use WikibaseQuality\ExternalValidation\ExternalDataRepo;

/**
 * Performs cross-checks with external data sources for a list of statements of a single entity.
 *
 * @package WikibaseQuality\ExternalValidation\CrossCheck
 * @author BP2014N1
 * @license GNU GPL v2+
 */
class CrossChecker {

	/**
	 * @var EntityLookup
	 */
	private $entityLookup;

	/**
	 * @var ComparativeValueParserFactory
	 */
	private $valueParserFactory;

	/**
	 * @var DataValueComparer
	 */
	private $dataValueComparer;

	/**
	 * @var ReferenceChecker
	 */
	private $referenceHandler;

	/**
	 * @var DumpMetaInformationLookup
	 */
	private $dumpMetaInformationLookup;

	/**
	 * @var ExternalDataRepo
	 */
	private $externalDataRepo;

	/**
	 * @param EntityLookup $entityLookup
	 * @param ComparativeValueParserFactory $comparativeValueParserFactory
	 * @param DataValueComparer $dataValueComparer
	 * @param ReferenceChecker $referenceHandler
	 * @param DumpMetaInformationLookup $dumpMetaInformationLookup
	 * @param ExternalDataRepo $externalDataRepo
	 */
	public function __construct(
		EntityLookup $entityLookup,
		ComparativeValueParserFactory $comparativeValueParserFactory,
		DataValueComparer $dataValueComparer,
		ReferenceChecker $referenceHandler,
		DumpMetaInformationLookup $dumpMetaInformationLookup,
		ExternalDataRepo $externalDataRepo
	) {
		$this->entityLookup = $entityLookup;
		$this->valueParserFactory = $comparativeValueParserFactory;
		$this->dataValueComparer = $dataValueComparer;
		$this->referenceHandler = $referenceHandler;
		$this->dumpMetaInformationLookup = $dumpMetaInformationLookup;
		$this->externalDataRepo = $externalDataRepo;
	}

	/**
	 * Runs cross-check for specific statements of a entity.
	 *
	 * @param StatementList $entityStatements
	 * @param StatementList $statements
	 *
	 * @return CrossCheckResultList
	 * @throws InvalidArgumentException
	 */
	public function crossCheckStatements( StatementList $entityStatements, StatementList $statements ) {
		$statementsOfEntity = $entityStatements->toArray();

		foreach ( $statements as $statement ) {
			if ( !in_array( $statement, $statementsOfEntity ) ) {
				throw new InvalidArgumentException( 'All statements in $statements must belong to the entity.' );
			}
		}

		$resultList = new CrossCheckResultList();
		if ( $statements->count() > 0 ) {
			$applicableDumps = $this->getApplicableDumps( $entityStatements );
			foreach ( $applicableDumps as $identifierPropertyId => $dumpMetaInformationList ) {
				$identifierPropertyId = new PropertyId( $identifierPropertyId );

				if( $this->isIdentifierProperty( $identifierPropertyId ) ) {
					$resultList->merge(
						$this->crossCheckStatementsWithIdentifier(
							$entityStatements,
							$statements,
							$identifierPropertyId,
							$dumpMetaInformationList
						)
					);
				}
			}
		}

		return $resultList;
	}

	/**
	 * Gets those dump ids from database, that are applicable for cross-checks with the given entity
	 *
	 * @param StatementList $statements
	 *
	 * @return array[]
	 */
	private function getApplicableDumps( StatementList $statements ) {
		$applicableDumps = array();
		$identifierPropertyIds = $statements->getPropertyIds();
		$dumpMetaInformation = $this->dumpMetaInformationLookup->getWithIdentifierProperties(
			$identifierPropertyIds
		);

		foreach ( $dumpMetaInformation as $dump ) {
			foreach ( $dump->getIdentifierPropertyIds() as $identifierPropertyId ) {
				$serialization = $identifierPropertyId->getSerialization();
				$applicableDumps[ $serialization ][ $dump->getDumpId() ] = $dump;
			}
		}

		return $applicableDumps;
	}

	/**
	 * Runs cross-check for a single identifier property
	 *
	 * @param StatementList $entityStatements
	 * @param StatementList $statements
	 * @param PropertyId $identifierPropertyId
	 * @param DumpMetaInformation[] $dumpMetaInformationList
	 *
	 * @return CrossCheckResultList
	 */
	private function crossCheckStatementsWithIdentifier(
		StatementList $entityStatements,
		StatementList $statements,
		PropertyId $identifierPropertyId,
		array $dumpMetaInformationList
	) {
		$resultList = new CrossCheckResultList();

		$externalIds = $this->getExternalIds( $entityStatements, $identifierPropertyId );
		if( !$externalIds ) {
			return $resultList;
		}

		$dumpIds = array_map(
			function ( DumpMetaInformation $dumpMetaInformation ) {
				return $dumpMetaInformation->getDumpId();
			},
			$dumpMetaInformationList
		);
		$externalData = $this->externalDataRepo->getExternalData( $dumpIds, $externalIds, $statements->getPropertyIds() );

		foreach ( $externalData as $dumpId => $externalDataPerDump ) {
			$dumpMetaInformation = $dumpMetaInformationList[ $dumpId ];
			$comparativeValueParser = $this->valueParserFactory->newComparativeValueParser(
				$dumpMetaInformation->getLanguageCode()
			);

			foreach ( $externalDataPerDump as $externalId => $externalDataPerId ) {
				$externalId = (string)$externalId;
				foreach ( $externalDataPerId as $propertyId => $externalValues ) {
					$propertyId = new PropertyId( $propertyId );
					$resultList->merge(
						$this->crossCheckExternalValues(
							$dumpMetaInformation,
							$identifierPropertyId,
							$externalId,
							$externalValues,
							$statements->getByPropertyId( $propertyId ),
							$comparativeValueParser
						)
					);
				}
			}
		}

		return $resultList;
	}

	/**
	 * @param DumpMetaInformation $dumpMetaInformation
	 * @param PropertyId $identifierPropertyId
	 * @param string $externalId
	 * @param array $externalValues
	 * @param StatementList $statements
	 * @param ComparativeValueParser $comparativeValueParser
	 *
	 * @return CrossCheckResultList
	 */
	private function crossCheckExternalValues(
		DumpMetaInformation $dumpMetaInformation,
		PropertyId $identifierPropertyId,
		$externalId,
		array $externalValues,
		StatementList $statements,
		ComparativeValueParser $comparativeValueParser
	) {
		$resultList = new CrossCheckResultList();

		foreach ( $statements->toArray() as $statement ) {
			$comparisonResult = $this->compareStatement( $statement, $externalValues, $comparativeValueParser );

			if ( $comparisonResult ) {
				$referencesResult = $this->referenceHandler->checkForReferences(
					$statement,
					$identifierPropertyId,
					$externalId,
					$dumpMetaInformation
				);

				$resultList->add(
					new CrossCheckResult(
						$statement->getPropertyId(),
						$statement->getGuid(),
						$externalId,
						$dumpMetaInformation,
						$comparisonResult,
						$referencesResult
					)
				);
			}
		}

		return $resultList;
	}

	/**
	 * Compares data value of a single statement against given external values
	 *
	 * @param Statement $statement
	 * @param array $externalValues
	 * @param ComparativeValueParser $comparativeValueParser
	 *
	 * @return ComparisonResult|bool
	 */
	private function compareStatement(
		Statement $statement,array $externalValues,
		ComparativeValueParser $comparativeValueParser
	) {
		$mainSnak = $statement->getMainSnak();
		if ( $mainSnak instanceof PropertyValueSnak ) {
			$dataValue = $mainSnak->getDataValue();

			$results = array();
			$comparativeValues = $this->parseExternalValues( $dataValue, $externalValues, $comparativeValueParser );
			foreach ( $comparativeValues as $comparativeValue ) {
				$result = $this->dataValueComparer->compare( $dataValue, $comparativeValue );
				if( $result ) {
					$results[] = $result;
				}
			}

			if( $results ) {
				$result = ComparisonResult::STATUS_MISMATCH;
				if( in_array( ComparisonResult::STATUS_MATCH, $results ) ) {
					$result = ComparisonResult::STATUS_MATCH;
				}
				elseif( in_array( ComparisonResult::STATUS_PARTIAL_MATCH, $results ) ) {
					$result = ComparisonResult::STATUS_PARTIAL_MATCH;
				}

				return new ComparisonResult( $dataValue, $comparativeValues, $result );
			}
		}

		return false;
	}

	/**
	 * @param DataValue $dataValue
	 * @param array $externalValues
	 * @param ComparativeValueParser $comparativeValueParser
	 *
	 * @return DataValue[]
	 */
	private function parseExternalValues(
		DataValue $dataValue,
		array $externalValues,
		ComparativeValueParser $comparativeValueParser
	) {
		$parsedValues = array();
		foreach ( $externalValues as $externalValue ) {
			try {
				$parsedValue = $comparativeValueParser->parse( $externalValue, $dataValue->getType() );
				if( $parsedValue ) {
					$parsedValues[] = $parsedValue;
				}
			}
			catch( ParseException $e ) {}
		}

		return $parsedValues;
	}

	/**
	 * Gets external ids for a identifier property of a given entity
	 *
	 * @param StatementList $statements
	 * @param PropertyId $identifierPropertyId
	 *
	 * @return string[]
	 */
	private function getExternalIds( StatementList $statements, PropertyId $identifierPropertyId ) {
		$externalIds = array();
		$identifierStatements = $statements->getByPropertyId( $identifierPropertyId );
		$values = $this->getDataValues( $identifierStatements );
		foreach ( $values as $value ) {
			if ( $value instanceof StringValue ) {
				$externalIds[] = $value->getValue();
			}
		}

		return $externalIds;
	}

	/**
	 * @param PropertyId $identifierPropertyId
	 * @return bool
	 */
	private function isIdentifierProperty( PropertyId $identifierPropertyId ) {
		/** @var Property $property */
		$property = $this->entityLookup->getEntity( $identifierPropertyId );
		$instanceOfPropertyId = new PropertyId( INSTANCE_OF_PID );
		$statements = $property->getStatements()->getByPropertyId( $instanceOfPropertyId );
		$values = $this->getDataValues( $statements );
		foreach ( $values as $value ) {
			if( $value instanceof EntityIdValue ) {
				if( $value->getEntityId()->getSerialization() === IDENTIFIER_PROPERTY_QID ) {
					return true;
				}
			}
		}

		return false;
	}

	/**
	 * Returns DataValues of given statements.
	 *
	 * @param StatementList $statementList
	 * @return DataValue[]
	 */
	private function getDataValues( StatementList $statementList ){
		$dataValues = array();

		foreach ( $statementList->toArray() as $statement ) {
			$mainSnak = $statement->getMainSnak();
			if ( $mainSnak instanceof PropertyValueSnak ) {
				$dataValues[] = $mainSnak->getDataValue();
			}
		}

		return $dataValues;
	}

}
