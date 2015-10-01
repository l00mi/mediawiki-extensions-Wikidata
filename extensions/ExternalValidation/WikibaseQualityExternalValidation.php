<?php
if ( file_exists( __DIR__ . '/vendor/autoload.php' ) ) {
	require_once __DIR__ . '/vendor/autoload.php';
}

call_user_func( function () {
	// Set credits
	$GLOBALS['wgExtensionCredits']['specialpage'][] = array(
		'path' => __FILE__,
		'name' => 'WikibaseQualityExternalValidation',
		'author' => 'BP2014N1',
		'url' => 'https://www.mediawiki.org/wiki/Extension:WikibaseQualityExternalValidation',
		'descriptionmsg' => 'wbqev-desc',
		'version' => '1.0.0'
	);

	// Initialize localization and aliases
	$GLOBALS['wgMessagesDirs']['WikibaseQualityExternalValidation'] = __DIR__ . '/i18n';
	$GLOBALS['wgExtensionMessagesFiles']['WikibaseQualityExternalValidationAlias'] = __DIR__ . '/WikibaseQualityExternalValidation.alias.php';

	// Initalize hooks for creating database tables
	$GLOBALS['wgHooks']['LoadExtensionSchemaUpdates'][] = 'WikibaseQualityExternalValidationHooks::onCreateSchema';

	// Register hooks for Unit Tests
	$GLOBALS['wgHooks']['UnitTestsList'][] = 'WikibaseQualityExternalValidationHooks::onUnitTestsList';

	// Initialize special pages
	$GLOBALS['wgSpecialPages']['CrossCheck'] = 'WikibaseQuality\ExternalValidation\Specials\SpecialCrossCheck::newFromGlobalState';
	$GLOBALS['wgSpecialPages']['ExternalDbs'] = 'WikibaseQuality\ExternalValidation\Specials\SpecialExternalDbs::newFromGlobalState';

	// Define API modules
	$GLOBALS['wgAPIModules']['wbqevcrosscheck'] = array(
		'class' => 'WikibaseQuality\ExternalValidation\Api\RunCrossCheck',
		'factory' => function( ApiMain $main, $action ) {
			return \WikibaseQuality\ExternalValidation\Api\RunCrossCheck::newFromGlobalState( $main, $action );
		}
	);

	// Define modules
	$GLOBALS['wgResourceModules']['SpecialCrossCheckPage'] = array(
		'styles' => '/modules/ext.WikibaseExternalValidation.SpecialCrossCheckPage.css',
		'localBasePath' => __DIR__,
		'remoteExtPath' => 'WikibaseQualityExternalValidation'
	);

	// Ids of certain Wikidata entities
	if( !defined( 'INSTANCE_OF_PID' ) ) {
		define( 'INSTANCE_OF_PID', 'P31' );
	}
	if( !defined( 'IDENTIFIER_PROPERTY_QID' ) ) {
		define( 'IDENTIFIER_PROPERTY_QID', 'Q19847637' );
	}
	if( !defined( 'STATED_IN_PID' ) ) {
		define( 'STATED_IN_PID', 'P248' );
	}
} );
