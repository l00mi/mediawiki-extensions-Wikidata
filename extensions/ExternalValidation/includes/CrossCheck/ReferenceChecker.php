<?php

namespace WikibaseQuality\ExternalValidation\CrossCheck;

use DataValues\StringValue;
use InvalidArgumentException;
use Wikibase\DataModel\Entity\EntityIdValue;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Reference;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\DataModel\Snak\SnakList;
use Wikibase\DataModel\Statement\Statement;
use WikibaseQuality\ExternalValidation\CrossCheck\Result\ReferenceResult;
use WikibaseQuality\ExternalValidation\DumpMetaInformation\DumpMetaInformation;
use Wikimedia\Assert\Assert;


/**
 * Class ReferenceChecker
 *
 * Checks, if a statement has statements and generated a new reference using an ID
 * of an external data source.
 *
 * @package WikibaseQuality\ExternalValidation\CrossCheck
 * @author BP2014N1
 * @license GNU GPL v2+
 */
class ReferenceChecker {

	/**
	 * Checks, if given statement has references and generates new reference using the external data source.
	 *
	 * @param Statement $statement
	 * @param PropertyId $identifierPropertyId - used for generating new reference
	 * @param string $externalId - used for generating new reference
	 * @param DumpMetaInformation $dumpMetaInformation
	 * @throws InvalidArgumentException
	 * @return ReferenceResult
	 */
	public function checkForReferences( Statement $statement, PropertyId $identifierPropertyId, $externalId, DumpMetaInformation $dumpMetaInformation ) {
		Assert::parameterType( 'string', $externalId, '$externalId' );

		if ( count( $statement->getReferences() ) === 0 ) {
			$status = ReferenceResult::STATUS_REFERENCES_MISSING;
		} else {
			$status = ReferenceResult::STATUS_REFERENCES_STATED;
		}

		$externalReference = $this->buildReference( $identifierPropertyId, $externalId, $dumpMetaInformation );
		return new ReferenceResult( $status, $externalReference );
	}

	/**
	 * Builds reference for external source.
	 *
	 * @param PropertyId $identifierPropertyId
	 * @param string $externalId
	 * @param DumpMetaInformation $dumpMetaInformation
	 * @return Reference
	 */
	private function buildReference( PropertyId $identifierPropertyId, $externalId, DumpMetaInformation $dumpMetaInformation ) {
		$sourceItemId = $dumpMetaInformation->getSourceItemId();
		$statedInAuthoritySnak = new PropertyValueSnak(
			new PropertyId( STATED_IN_PID ),
			new EntityIdValue( $sourceItemId )
		);
		$authorityWithExternalIdSnak = new PropertyValueSnak(
			$identifierPropertyId,
			new StringValue( $externalId )
		);

		return new Reference(
			new SnakList(
				array(
					$statedInAuthoritySnak,
					$authorityWithExternalIdSnak
				)
			)
		);
	}
}