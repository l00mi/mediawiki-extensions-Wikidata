<?php
if ( file_exists( __DIR__ . '/vendor/autoload.php' ) ) {
	require_once __DIR__ . '/vendor/autoload.php';
}

call_user_func( function () {
	// Set credits
	$GLOBALS['wgExtensionCredits']['wikibase'][] = array(
		'path' => __FILE__,
		'name' => 'WikibaseQuality',
		'author' => 'BP2014N1',
		'url' => 'https://www.mediawiki.org/wiki/Extension:WikibaseQuality',
		'descriptionmsg' => 'wbq-desc',
		'version' => '1.0.0'
	);

	// Initialize localization and aliases
	$GLOBALS['wgMessagesDirs']['WikibaseQuality'] = __DIR__ . '/i18n';
	$GLOBALS['wgExtensionMessagesFiles']['WikibaseQualityAlias'] = __DIR__ . '/WikibaseQuality.alias.php';

	// Register hooks for Unit Tests
	$GLOBALS['wgHooks']['UnitTestsList'][] = 'WikibaseQualityHooks::onUnitTestsList';

	// Define modules
	$GLOBALS['wgResourceModules']['SpecialCheckResultPage'] = array(
		'styles' => '/modules/SpecialCheckResultPage.css',
		'localBasePath' => __DIR__,
		'remoteExtPath' => 'WikibaseQuality'
	);
} );
