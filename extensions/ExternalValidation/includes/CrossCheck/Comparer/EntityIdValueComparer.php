<?php

namespace WikibaseQuality\ExternalValidation\CrossCheck\Comparer;

use DataValues\DataValue;
use DataValues\MonolingualTextValue;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\EntityIdValue;
use Wikibase\TermIndexEntry;
use Wikibase\TermIndex;
use Wikimedia\Assert\Assert;

/**
 * @fixme This class does not compares EntityIdValues!
 * @package WikibaseQuality\ExternalValidation\CrossCheck\Comparer
 * @author BP2014N1
 * @license GNU GPL v2+
 */
class EntityIdValueComparer implements DataValueComparer {

	/**
	 * @var TermIndex
	 */
	private $termIndex;

	/**
	 * @var StringComparer
	 */
	private $stringComparer;

	/**
	 * @param TermIndex $termIndex
	 * @param StringComparer $stringComparer
	 */
	public function __construct( TermIndex $termIndex, StringComparer $stringComparer ) {
		$this->termIndex = $termIndex;
		$this->stringComparer = $stringComparer;
	}

	/**
	 * @see DataValueComparer::compare
	 *
	 * @param DataValue $value
	 * @param DataValue $comparativeValue
	 *
	 * @return string|null One of the ComparisonResult::STATUS_... constants.
	 */
	public function compare( DataValue $value, DataValue $comparativeValue ) {
		Assert::parameterType( EntityIdValue::class, $value, '$value' );
		Assert::parameterType( MonolingualTextValue::class, $comparativeValue, '$comparativeValue' );

		/**
		 * @var EntityIdValue $value
		 * @var MonolingualTextValue $comparativeValue
		 */

		$entityId = $value->getEntityId();
		$language = $comparativeValue->getLanguageCode();
		$terms = $this->getTerms( $entityId, $language );

		if ( $terms ) {
			return $this->stringComparer->compareWithArray( $comparativeValue->getText(), $terms );
		}

		return null;
	}

	/**
	 * Retrieves terms (label and aliases) of a given entity in the given language
	 *
	 * @param EntityId $entityId
	 * @param string $language
	 *
	 * @return array
	 */
	private function getTerms( EntityId $entityId, $language ) {
		$terms = $this->termIndex->getTermsOfEntity(
			$entityId,
			array(
				TermIndexEntry::TYPE_LABEL,
				TermIndexEntry::TYPE_ALIAS
			),
			array( $language )
		);

		return array_map(
			function( TermIndexEntry $term ) {
				return $term->getText();
			},
			$terms
		);
	}

	/**
	 * @see DataValueComparer::canCompare
	 *
	 * @param DataValue $value
	 * @param DataValue $comparativeValue
	 * @return bool
	 */
	public function canCompare( DataValue $value, DataValue $comparativeValue ) {
		return $value instanceof EntityIdValue && $comparativeValue instanceof MonolingualTextValue;
	}

}
