<?php

namespace WikibaseQuality\ExternalValidation\CrossCheck;

use InvalidArgumentException;
use Wikibase\DataModel\Services\Lookup\EntityLookup;
use Wikibase\DataModel\Services\Statement\StatementGuidParser;
use Wikibase\DataModel\Statement\StatementListProvider;
use Wikimedia\Assert\Assert;
use Wikibase\DataModel\Entity\Entity;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Statement\StatementList;
use WikibaseQuality\ExternalValidation\CrossCheck\Result\CrossCheckResultList;

/**
 * Helper class for interacting with CrossChecker. It makes it possible to run cross-checks for various parameter types
 * and combinations, since the CrossChecker only accepts statements.
 *
 * @package WikibaseQuality\ExternalValidation\CrossCheck
 * @author BP2014N1
 * @license GNU GPL v2+
 */
class CrossCheckInteractor {

	/**
	 * @var EntityLookup
	 */
	private $entityLookup;

	/**
	 * @var StatementGuidParser
	 */
	private $statementGuidParser;

	/**
	 * @var CrossChecker
	 */
	private $crossChecker;

	/**
	 * @param EntityLookup $entityLookup
	 * @param StatementGuidParser $statementGuidParser
	 * @param CrossChecker $crossChecker
	 */
	public function __construct( EntityLookup $entityLookup, StatementGuidParser $statementGuidParser, CrossChecker $crossChecker ) {
		$this->entityLookup = $entityLookup;
		$this->statementGuidParser = $statementGuidParser;
		$this->crossChecker = $crossChecker;
	}

	/**
	 * Runs cross-check for all statements of multiple entities represented by ids.
	 *
	 * @param EntityId $entityId
	 *
	 * @return CrossCheckResultList|null
	 */
	public function crossCheckEntityById( EntityId $entityId ) {
		$entity = $this->entityLookup->getEntity( $entityId );

		if ( $entity instanceof StatementListProvider ) {
			return $this->crossCheckEntity( $entity->getStatements() );
		}

		return null;
	}

	/**
	 * Runs cross-check for all statements of a single entity represented by id.
	 *
	 * @param EntityId[] $entityIds
	 *
	 * @return CrossCheckResultList[]
	 */
	public function crossCheckEntitiesByIds( array $entityIds ) {
		Assert::parameterElementType( 'Wikibase\DataModel\Entity\EntityId',  $entityIds, '$entityIds' );

		$results = array();
		foreach ( $entityIds as $entityId ) {
			$results[$entityId->getSerialization()] = $this->crossCheckEntityById( $entityId );
		}

		return $results;
	}

	/**
	 * Runs cross-check for all statements of a single entity.
	 *
	 * @param StatementList $statements
	 *
	 * @return CrossCheckResultList
	 */
	public function crossCheckEntity( StatementList $statements ) {
		return $this->crossChecker->crossCheckStatements( $statements, $statements );
	}

	/**
	 * Runs cross-check for all statements of multiple entities.
	 *
	 * @param Entity[] $entities
	 *
	 * @return CrossCheckResultList[]
	 * @throws InvalidArgumentException
	 */
	public function crossCheckEntities( array $entities ) {
		Assert::parameterElementType( 'Wikibase\DataModel\Entity\Entity',  $entities, '$entities' );

		$results = array();
		foreach ( $entities as $entity ) {
			$entityId = $entity->getId()->getSerialization();
			if ( $entity instanceof StatementListProvider ) {
				$results[$entityId] = $this->crossCheckEntity( $entity->getStatements() );
			}
		}

		return $results;
	}

	/**
	 * Runs cross-check for all statements with any of the given property ids of a single entity represented by its id.
	 *
	 * @param EntityId $entityId
	 * @param PropertyId[] $propertyIds
	 *
	 * @return CrossCheckResultList|null
	 * @throws InvalidArgumentException
	 */
	public function crossCheckEntityByIdWithProperties( EntityId $entityId, array $propertyIds ) {
		Assert::parameterElementType( 'Wikibase\DataModel\Entity\PropertyId',  $propertyIds, '$propertyIds' );

		$entity = $this->entityLookup->getEntity( $entityId );

		if ( $entity instanceof StatementListProvider ) {
			return $this->crossCheckEntityWithProperties( $entity->getStatements(), $propertyIds );
		}

		return null;
	}

	/**
	 * Runs cross-check for all statements with any of the given property ids of multiple single entity represented by its ids.
	 *
	 * @param EntityId[] $entityIds
	 * @param PropertyId[] $propertyIds
	 *
	 * @return CrossCheckResultList[]
	 * @throws InvalidArgumentException
	 */
	public function crossCheckEntitiesByIdWithProperties( array $entityIds, array $propertyIds ) {
		Assert::parameterElementType( 'Wikibase\DataModel\Entity\EntityId',  $entityIds, '$entityIds' );
		Assert::parameterElementType( 'Wikibase\DataModel\Entity\PropertyId',  $propertyIds, '$propertyIds' );

		$results = array();
		foreach ( $entityIds as $entityId ) {
			$results[$entityId->getSerialization()] = $this->crossCheckEntityByIdWithProperties( $entityId, $propertyIds );
		}

		return $results;
	}

	/**
	 * Runs cross-check for all statements with any of the given property ids of a single entity.
	 *
	 * @param StatementList $entityStatements
	 * @param PropertyId[] $propertyIds
	 *
	 * @return CrossCheckResultList
	 * @throws InvalidArgumentException
	 */
	public function crossCheckEntityWithProperties( StatementList $entityStatements, array $propertyIds ) {
		Assert::parameterElementType( 'Wikibase\DataModel\Entity\PropertyId',  $propertyIds, '$propertyIds' );

		$statements = new StatementList();
		foreach ( $entityStatements->toArray() as $statement ) {
			if ( in_array( $statement->getPropertyId(), $propertyIds ) ) {
				$statements->addStatement( $statement );
			}
		}

		return $this->crossChecker->crossCheckStatements( $entityStatements, $statements );
	}

	/**
	 * Runs cross-check for all statements with any of the given property ids of multiple entities.
	 *
	 * @param Entity[] $entities
	 * @param PropertyId[] $propertyIds
	 *
	 * @return CrossCheckResultList[]
	 * @throws InvalidArgumentException
	 */
	public function crossCheckEntitiesWithProperties( array $entities, array $propertyIds ) {
		Assert::parameterElementType( 'Wikibase\DataModel\Entity\Entity',  $entities, '$entities' );
		Assert::parameterElementType( 'Wikibase\DataModel\Entity\PropertyId',  $propertyIds, '$propertyIds' );

		$results = array();
		foreach ( $entities as $entity ) {
			$entityId = $entity->getId()->getSerialization();
			if ( $entity instanceof StatementListProvider ) {
				$results[$entityId] = $this->crossCheckEntityWithProperties(
					$entity->getStatements(),
					$propertyIds
				);
			}
		}

		return $results;
	}

	/**
	 * Runs cross-check for a single statement.
	 *
	 * @param string $statementGuid
	 *
	 * @param string $claimGuid
	 * @return CrossCheckResultList
	 * @throws InvalidArgumentException
	 */
	public function crossCheckStatement( $statementGuid ) {
		$this->assertIsString( $statementGuid, '$claimGuid' );

		$resultList = $this->crossCheckStatements( array( $statementGuid ) );

		return reset( $resultList );
	}

	/**
	 * Runs cross-check for multiple statements.
	 *
	 * @param string[] $statementGuids
	 *
	 * @return CrossCheckResultList[]
	 * @throws InvalidArgumentException
	 */
	public function crossCheckStatements( array $statementGuids ) {
		$this->assertIsArrayOfStrings( $statementGuids, '$claimGuids' );

		$entityIds = array();
		$groupedStatementGuids = array();
		foreach ( $statementGuids as $statementGuid ) {
			$serializedEntityId = $this->statementGuidParser->parse( $statementGuid )->getEntityId();
			$entityIds[$serializedEntityId->getSerialization()] = $serializedEntityId;
			$groupedStatementGuids[$serializedEntityId->getSerialization()][] = $statementGuid;
		}

		$resultLists = array();
		foreach ( $groupedStatementGuids as $serializedEntityId => $claimGuidsOfEntity ) {
			$entityId = $entityIds[ $serializedEntityId ];
			$resultLists[ $serializedEntityId ] = $this->crossCheckClaimsOfEntity( $entityId, $claimGuidsOfEntity );
		}

		return $resultLists;
	}

	/**
	 * @param EntityId $entityId
	 * @param string[] $clamGuids
	 * @return CrossCheckResultList|null
	 */
	private function crossCheckClaimsOfEntity( EntityId $entityId, $clamGuids ) {
		$entity = $this->entityLookup->getEntity( $entityId );

		if ( $entity instanceof StatementListProvider ) {
			$statements = new StatementList();
			foreach ( $entity->getStatements()->toArray() as $statement ) {
				if ( in_array( $statement->getGuid(), $clamGuids ) ) {
					$statements->addStatement( $statement );
				}
			}

			return $this->crossChecker->crossCheckStatements( $entity->getStatements(), $statements );
		}

		return null;
	}

	/**
	 * @param string $string
	 * @param string $parameterName
	 *
	 * @throws InvalidArgumentException
	 */
	private function assertIsString( $string, $parameterName ) {
		if ( !is_string( $string ) ) {
			throw new InvalidArgumentException( "$parameterName must be string." );
		}
	}

	/**
	 * @param array $strings
	 * @param string $parameterName
	 *
	 * @throws InvalidArgumentException
	 */
	private function assertIsArrayOfStrings( array $strings, $parameterName ) {
		foreach ( $strings as $string ) {
			if ( !is_string( $string ) ) {
				throw new InvalidArgumentException( "Each element of $parameterName must be string." );
			}
		}
	}

}
