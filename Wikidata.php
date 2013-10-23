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
	$wmgUseWikibaseRepo = true;
	$wmgUseWikibaseClient = true;
}

$wikidataDependencies = array(
	'Diff_VERSION' => '/Diff/Diff.php',
	'DataValues_VERSION' => '/DataValues/DataValues.php',
	'DataTypes_VERSION' => '/DataTypes/DataTypes.php',
	'WIKIBASE_DATAMODEL_VERSION' => '/WikibaseDataModel/WikibaseDataModel.php',
	'WBL_VERSION' => '/Wikibase/lib/WikibaseLib.php'
);

//Load our entry files ( if we want them )
if ( $wmgUseWikibaseRepo || $wmgUseWikibaseClient ) {
	foreach( $wikidataDependencies as $constant => $location ) {
		include_once( __DIR__ . $location );
	}
}

if ( $wmgUseWikibaseRepo ) {
	include_once( __DIR__ . '/Wikibase/repo/Wikibase.php' );
}

if ( $wmgUseWikibaseClient ) {
	include_once( __DIR__ . '/Wikibase/client/WikibaseClient.php' );
}

//Jenkins stuff part2
if( PHP_SAPI === 'cli' && getenv( 'JOB_NAME' ) === 'mwext-Wikidata-testextensions-master') {
	//Jenkins always loads both so no need to check if they are loaded before getting settings
	require_once __DIR__ . '/Wikibase/repo/ExampleSettings.php';
	require_once __DIR__ . '/Wikibase/client/ExampleSettings.php';
}
