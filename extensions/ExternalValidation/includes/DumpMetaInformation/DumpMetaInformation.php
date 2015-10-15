<?php

namespace WikibaseQuality\ExternalValidation\DumpMetaInformation;

use DateTime;
use Language;
use InvalidArgumentException;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\PropertyId;
use Wikimedia\Assert\Assert;

/**
 * @package WikibaseQuality\ExternalValidation\DumpMetaInformation
 * @author BP2014N1
 * @license GNU GPL v2+
 */
class DumpMetaInformation {

	/**
	 * Id of the dump
	 *
	 * @var string
	 */
	private $dumpId;

	/**
	 * Id of the item that represents the data source of the dump
	 *
	 * @var ItemId
	 */
	private $sourceItemId;

	/**
	 * Properties for identifiers of the dump.
	 *
	 * @var PropertyId[]
	 */
	private $identifierPropertyIds;

	/**
	 * Date of import
	 *
	 * @var DateTime
	 */
	private $importDate;

	/**
	 * Language code of values of the dump
	 *
	 * @var string
	 */
	private $languageCode;

	/**
	 * Source url of the downloaded dump
	 *
	 * @var string
	 */
	private $sourceUrl;

	/**
	 * Size of the imported dump in byte
	 *
	 * @var int
	 */
	private $size;

	/**
	 * Id of the item that represents the license of the database
	 *
	 * @var ItemId
	 */
	private $licenseItemId;

	/**
	 * @param string $dumpId
	 * @param ItemId $sourceItemId
	 * @param PropertyId[] $identifierPropertyIds
	 * @param string $importDate
	 * @param string $languageCode
	 * @param string $sourceUrl
	 * @param int $size
	 * @param ItemId $licenseItemId
	 *
	 * @throws InvalidArgumentException
	 */
	public function __construct( $dumpId,
								 ItemId $sourceItemId,
								 array $identifierPropertyIds,
								 $importDate,
								 $languageCode,
								 $sourceUrl,
								 $size,
								 ItemId $licenseItemId ) {
		Assert::parameterElementType(
			'Wikibase\DataModel\Entity\PropertyId',
			$identifierPropertyIds,
			'$identifierPropertyIds'
		);

		$this->setDumpId( $dumpId );
		$this->sourceItemId = $sourceItemId;
		$this->identifierPropertyIds = $identifierPropertyIds;
		$this->setImportDate( $importDate );
		$this->setLanguageCode( $languageCode );
		$this->setSourceUrl( $sourceUrl );
		$this->setSize( $size );
		$this->licenseItemId = $licenseItemId;
	}

	/**
	 * @return string
	 */
	public function getDumpId() {
		return $this->dumpId;
	}

	/**
	 * @return ItemId
	 */
	public function getSourceItemId() {
		return $this->sourceItemId;
	}

	/**
	 * @return \Wikibase\DataModel\Entity\PropertyId[]
	 */
	public function getIdentifierPropertyIds() {
		return $this->identifierPropertyIds;
	}

	/**
	 * @return DateTime
	 */
	public function getImportDate() {
		return $this->importDate;
	}

	/**
	 * @return string
	 */
	public function getLanguageCode() {
		return $this->languageCode;
	}

	/**
	 * @return string
	 */
	public function getSourceUrl() {
		return $this->sourceUrl;
	}

	/**
	 * @return int
	 */
	public function getSize() {
		return $this->size;
	}

	/**
	 * @return ItemId
	 */
	public function getLicenseItemId() {
		return $this->licenseItemId;
	}

	/**
	 * @param string $dumpId
	 */
	private function setDumpId( $dumpId ) {
		Assert::parameterType( 'string', $dumpId, '$dumpId' );
		$length = strlen( $dumpId );
		if( $length === 0 && $length > 25 ) {
			throw new InvalidArgumentException('$dumpId must be between 1 and 25 characters.');
		}

		$this->dumpId = $dumpId;
	}

	/**
	 * @param string $languageCode
	 */
	private function setLanguageCode( $languageCode ) {
		Assert::parameterType( 'string', $languageCode, '$languageCode' );
		if( !Language::isValidCode( $languageCode ) ) {
			throw new InvalidArgumentException( '$languageCode is not valid.' );
		}

		$this->languageCode = $languageCode;
	}
	/**
	 * @param string $importDate
	 */
	private function setImportDate( $importDate ) {
		Assert::parameterType( 'string', $importDate, '$importDate' );

		$timestamp = wfTimestamp( TS_MW, $importDate );
		if( !$timestamp ) {
			throw new InvalidArgumentException( '$updatedAt has invalid timestamp format.' );
		}

		$this->importDate = $importDate;
	}

	/**
	 * @param string $sourceUrl
	 */
	private function setSourceUrl( $sourceUrl ) {
		Assert::parameterType( 'string', $sourceUrl, '$sourceUrl' );
		if( strlen( $sourceUrl ) > 300 ) {
			throw new InvalidArgumentException( '$sourceUrl must not be longer than 300 characters.' );
		}
		if( !filter_var($sourceUrl, FILTER_VALIDATE_URL) ) {
			throw new InvalidArgumentException( '$sourceUrl is not a valid url.' );
		}

		$this->sourceUrl = $sourceUrl;
	}

	/**
	 * @param int $size
	 */
	private function setSize( $size ){
		Assert::parameterType( 'integer', $size, '$size' );
		if( $size <= 0 ) {
			throw new InvalidArgumentException( '$size must be positive integer.' );
		}

		$this->size = $size;
	}

}
