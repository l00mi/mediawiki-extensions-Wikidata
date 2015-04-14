<?php

namespace Wikidata\Tests;

use Wikidata\SettingsFileGenerator;

/**
 * @covers Wikidata\SettingsFileGenerator
 *
 * @group Wikidata
 * @group WikidataBuild
 * @group Wikibase
 *
 * @license GNU GPL v2+
 *
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class SettingsFileGeneratorTest extends \PHPUnit_Framework_TestCase {

	public function testGenerate() {
		$settings = array(
			'beverage' => 'beer',
			'food' => 'chocolate'
		);

		$filename = tempnam( sys_get_temp_dir(), 'WikidataSettings.php' );

		$settingsFileGenerator = new SettingsFileGenerator();
		$settingsFileGenerator->generate( $settings, 'wgWikidataSettings', $filename );

		include $filename;

		$this->assertEquals( $settings, $wgWikidataSettings );

		unlink( $filename );
	}

	public function testGenerateDefaultSettings() {
		global $wgWBClientSettings, $wgWBRepoSettings;

		include __DIR__ . '/../WikibaseClient.settings.php';
		include __DIR__ . '/../WikibaseRepo.settings.php';

		$expectedSuffix = $this->getExpectedCacheSuffix();

		$this->assertRegExp(
			'/wikibase_shared\/' . $expectedSuffix . '/',
			$wgWBClientSettings['sharedCacheKeyPrefix']
		);

		$this->assertRegExp(
			'/wikibase_shared\/' . $expectedSuffix . '/',
			$wgWBRepoSettings['sharedCacheKeyPrefix']
		);

		$this->assertEquals(
			$wgWBClientSettings['sharedCacheKeyPrefix'],
			$wgWBRepoSettings['sharedCacheKeyPrefix']
		);
	}

	public function testClientSettingsOverride() {
		if ( !defined( 'WBC_VERSION' ) ) {
			$this->markTestSkipped( 'WikibaseClient is not enabled.' );
		}

		$defaultPrefix = $this->getSharedCacheKeyPrefixDefault(
			$GLOBALS['wgWikidataBaseDir']
				. '/extensions/Wikibase/client/config/WikibaseClient.default.php'
		);

		$actualSettings = \Wikibase\Client\WikibaseClient::getDefaultInstance()->getSettings();
		$cachePrefix = $actualSettings->getSetting( 'sharedCacheKeyPrefix' );

		$this->assertNotSame(
			$defaultPrefix,
			$cachePrefix,
			"Build cache key prefix ($cachePrefix) should be different than"
				. " the default ($defaultPrefix)."
		);
	}

	public function testClientSharedCacheKeyPrefix_isString() {
		if ( !defined( 'WBC_VERSION' ) ) {
			$this->markTestSkipped( 'WikibaseClient is not enabled.' );
		}

		$defaults = include( $GLOBALS['wgWikidataBaseDir']
			. '/extensions/Wikibase/client/config/WikibaseClient.default.php' );

		$this->assertInternalType( 'string', $defaults['sharedCacheKeyPrefix'] );
	}

	public function testRepoSettingsOverride() {
		if ( !defined( 'WB_VERSION' ) ) {
			$this->markTestSkipped( 'WikibaseRepo is not enabled.' );
		}

		$defaultPrefix = $this->getSharedCacheKeyPrefixDefault(
			$GLOBALS['wgWikidataBaseDir']
				. '/extensions/Wikibase/repo/config/Wikibase.default.php'
		);

		$actualSettings = \Wikibase\Repo\WikibaseRepo::getDefaultInstance()->getSettings();
		$cachePrefix = $actualSettings->getSetting( 'sharedCacheKeyPrefix' );

		$this->assertNotSame(
			$defaultPrefix,
			$cachePrefix,
			"Build cache key prefix ($cachePrefix) should be different than"
				. " the default ($defaultPrefix)."
		);
	}

	public function testRepoSharedCacheKeyPrefix_isString() {
		if ( !defined( 'WB_VERSION' ) ) {
			$this->markTestSkipped( 'WikibaseRepo is not enabled.' );
		}

		$defaults = include( $GLOBALS['wgWikidataBaseDir']
			. '/extensions/Wikibase/repo/config/Wikibase.default.php' );

		$this->assertInternalType( 'string', $defaults['sharedCacheKeyPrefix'] );
	}

	private function getSharedCacheKeyPrefixDefault( $settingsFile ) {
		$defaults = include( $settingsFile );
		$defaultSettings = new \Wikibase\SettingsArray( $defaults );

		return $defaultSettings->getSetting( 'sharedCacheKeyPrefix' );
	}

	private function getExpectedCacheSuffix() {
		$composerJson = json_decode( file_get_contents( __DIR__ . '/../composer.json' ), true );

		if ( isset( $composerJson['config']['autoloader-suffix'] ) ) {
			return $composerJson['config']['autoloader-suffix'];
		}

		return '\d+';
	}

}
