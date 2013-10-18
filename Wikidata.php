<?php

if ( !defined( 'MEDIAWIKI' ) ) {
	die( 'Not an entry point.' );
}

//Jenkins stuff part1
if( PHP_SAPI === 'cli' && getenv( 'JOB_NAME' ) === 'mwext-Wikidata-testextensions-master') {
	//The below is needed so that tests that depend on experimental features pass i.e. wbsetstatementrank
	if ( !defined( 'WB_EXPERIMENTAL_FEATURES' ) || !WB_EXPERIMENTAL_FEATURES ) {
		define( 'WB_EXPERIMENTAL_FEATURES', true );
	}
	$wgEnableWikibaseRepo = true;
	$wgEnableWikibaseClient = true;
}

$wikidataToLoad = array(
	'Diff_VERSION' => '/Diff/Diff.php',
	'DataValues_VERSION' => '/DataValues/DataValues.php',
	'DataTypes_VERSION' => '/DataTypes/DataTypes.php',
	'WIKIBASE_DATAMODEL_VERSION' => '/WikibaseDataModel/WikibaseDataModel.php',
	'WBL_VERSION' => '/Wikibase/lib/WikibaseLib.php',
	'WB_VERSION' => '/Wikibase/repo/Wikibase.php',
	'WBC_VERSION' => '/Wikibase/client/WikibaseClient.php',
);

//Load our entry files ( if we want them )
foreach( $wikidataToLoad as $constant => $location ) {
	if ( !defined( $constant ) ) {
		if( ( $constant === 'WB_VERSION' && ( !isset( $wgEnableWikibaseRepo ) || !$wgEnableWikibaseRepo ) ) ||
			( $constant === 'WBC_VERSION' && ( !isset( $wgEnableWikibaseClient ) || !$wgEnableWikibaseClient ) )
		) { continue; }
		include_once( __DIR__ . $location );
	}
}
unset( $wikidataToLoad );

//Jenkins stuff part2
if( PHP_SAPI === 'cli' && getenv( 'JOB_NAME' ) === 'mwext-Wikidata-testextensions-master') {
	//Jenkins always loads both so no need to check if they are loaded before getting settings
	require_once __DIR__ . '/Wikibase/repo/ExampleSettings.php';
	require_once __DIR__ . '/Wikibase/client/ExampleSettings.php';
}
