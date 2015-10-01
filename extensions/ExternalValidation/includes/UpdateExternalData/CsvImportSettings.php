<?php

namespace WikibaseQuality\ExternalValidation\UpdateExternalData;

use Wikimedia\Assert\Assert;

/**
 * Settings for importing data from csv files to a db table using a ExternalDataImporter
 *
 * @package WikibaseQuality\ExternalValidation\UpdateExternalData
 * @author BP2014N1
 * @licence GNU GPL v2+
 */
class CsvImportSettings {

	/**
	 * Path of CSV file containing external values.
	 *
	 * @var string
	 */
	private $externalValuesFilePath;

	/**
	 * Path of the CSV file containing dump meta information.
	 *
	 * @var string
	 */
	private $dumpInformationFilePath;

	/**
	 * @var int
	 */
	private $batchSize;

	/**
	 * @var boolean
	 */
	private $quiet;

	/**
	 * @param string $externalValuesFilePath
	 * @param string $dumpInformationFilePath
	 * @param int $batchSize
	 * @param bool $quiet
	 */
	public function __construct( $externalValuesFilePath, $dumpInformationFilePath, $batchSize, $quiet = false ) {
		Assert::parameterType( 'string', $externalValuesFilePath, '$externalValuesFilePath' );
		Assert::parameterType( 'string', $dumpInformationFilePath, '$dumpInformationFilePath' );
		Assert::parameterType( 'integer', $batchSize, '$batchSize' );
		Assert::parameterType( 'boolean', $quiet, '$quiet' );

		$this->externalValuesFilePath = $externalValuesFilePath;
		$this->dumpInformationFilePath = $dumpInformationFilePath;
		$this->batchSize = $batchSize;
		$this->quiet = $quiet;
	}

	/**
	 * @return string
	 */
	public function getExternalValuesFilePath() {
		return $this->externalValuesFilePath;
	}

	/**
	 * @return string
	 */
	public function getDumpInformationFilePath() {
		return $this->dumpInformationFilePath;
	}

	/**
	 * @return int
	 */
	public function getBatchSize() {
		return $this->batchSize;
	}

	/**
	 * @return boolean
	 */
	public function isQuiet() {
		return $this->quiet;
	}

}
