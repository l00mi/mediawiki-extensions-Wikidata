<?php

namespace WikibaseQuality\ConstraintReport\ConstraintCheck\Result;

use DataValues\DataValue;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\DataModel\Statement\Statement;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\PropertyId;
use LogicException;

/**
 * Used for getting information about the result of a constraint check
 *
 * @package WikibaseQuality\ConstraintReport\ConstraintCheck\Result
 * @author BP2014N1
 * @license GNU GPL v2+
 */
class CheckResult {

	// Constants for statuses
	const STATUS_COMPLIANCE = 'compliance';
	const STATUS_VIOLATION = 'violation';
	const STATUS_EXCEPTION = 'exception';
	const STATUS_TODO = 'todo';

	/**
	 * @var EntityId
	 */
	private $entityId;

	/**
	 * @var Statement
	 */
	private $statement;

	/**
	 * @var string
	 */
	private $constraintName;

	/**
	 * @var string
	 */
	private $constraintId;

	/**
	 * @var array
	 * Includes arrays of ItemIds or PropertyIds or strings.
	 */
	private $parameters;

	/**
	 * @var string
	 */
	private $status;

	/**
	 * @var string
	 */
	private $message;

	/**
	 * @param EntityId $entityId
	 * @param Statement $statement
	 * @param string $constraintName
	 * @param string $constraintId
	 * @param array $parameters (string => string[])
	 * @param string $status
	 * @param string $message (sanitized HTML)
	 */
	public function __construct( EntityId $entityId, Statement $statement, $constraintName, $constraintId,  $parameters = array (), $status = self::STATUS_TODO, $message = '' ) {
		$this->entityId = $entityId;
		$this->statement = $statement;
		$this->constraintName = $constraintName;
		$this->constraintId = $constraintId;
		$this->parameters = $parameters;
		$this->status = $status;
		$this->message = $message;
	}

	/**
	 * @return EntityId
	 */
	public function getEntityId() {
		return $this->entityId;
	}

	/**
	 * @return Statement
	 */
	public function getStatement() {
		return $this->statement;
	}

	/**
	 * @return PropertyId
	 */
	public function getPropertyId() {
		return $this->statement->getPropertyId();
	}

	/**
	 * @return string
	 */
	public function getMainSnakType() {
		return $this->statement->getMainSnak()->getType();
	}

	/**
	 * @return DataValue
	 * @throws LogicException
	 */
	public function getDataValue() {
		$mainSnak = $this->statement->getMainSnak();

		if ( $mainSnak instanceof PropertyValueSnak ) {
			return $mainSnak->getDataValue();
		}

		throw new LogicException( 'Cannot get DataValue, MainSnak is of type ' . $this->getMainSnakType() . '.' );
	}

	/**
	 * @return string
	 */
	public function getConstraintName() {
		return $this->constraintName;
	}

	/**
	 * @return string
	 */
	public function getConstraintId() {
		return $this->constraintId;
	}

	/**
	 * @return array
	 */
	public function getParameters() {
		return $this->parameters;
	}

	/**
	 * @return string
	 */
	public function getStatus() {
		return $this->status;
	}

	/**
	 * @return string (sanitized HTML)
	 */
	public function getMessage() {
		return $this->message;
	}

}
