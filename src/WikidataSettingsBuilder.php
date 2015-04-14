<?php

namespace Wikidata;

/**
 * Dynamically generates settings for Wikidata
 *
 * @license GNU GPL v2+
 *
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class WikidataSettingsBuilder {

	private $commonSettings;

	private $composerConfig;

	/**
	 * @param Composer\Config $composerConfig - null by default, as not available during tests.
	 */
	public function __construct( $composerConfig = null ) {
		$this->composerConfig = $composerConfig;
	}

	/**
	 * @return array
	 */
	public function getRepoSettings() {
		return $this->getCommonSettings();
	}

	/**
	 * @return array
	 */
	public function getClientSettings() {
		return $this->getCommonSettings();
	}

	private function getCommonSettings() {
		if ( !isset( $this->commonSettings ) ) {
			$this->buildCommonSettings();
		}

		return $this->commonSettings;
	}

	private function buildCommonSettings() {
		$this->addSharedCacheKeyPrefix();
	}

	private function addSharedCacheKeyPrefix() {
		$suffix = time();

		if ( $this->composerConfig !== null ) {
			$autoloaderSuffix = $this->composerConfig->get( 'autoloader-suffix' );

			if ( $autoloaderSuffix !== null ) {
				$suffix = $autoloaderSuffix;
			}
		}

		$this->commonSettings['sharedCacheKeyPrefix'] = 'wikibase_shared/' . $suffix;
	}

}
