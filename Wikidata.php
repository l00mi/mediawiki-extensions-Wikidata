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

$wgEnableWikibaseRepo = $wmgUseWikibaseRepo;
$wgEnableWikibaseClient = $wmgUseWikibaseClient;

if ( $wmgUseWikibaseRepo ) {
	include_once( __DIR__ . '/Wikibase/repo/Wikibase.php' );
}

if ( $wmgUseWikibaseClient ) {
	include_once( __DIR__ . '/Wikibase/client/WikibaseClient.php' );
}

$wgExtensionCredits['wikibase'][] = array(
	'path' => __DIR__,
	'name' => 'Wikidata Build',
	'author' => array(
		'The Wikidata team', // TODO: link?
	),
	'url' => 'https://www.mediawiki.org/wiki/Wikidata_build',
	'description' => 'Wikidata extensions build'
);

//Jenkins stuff part2
if( PHP_SAPI === 'cli' && getenv( 'JOB_NAME' ) === 'mwext-Wikidata-testextensions-master') {
	//Jenkins always loads both so no need to check if they are loaded before getting settings
	require_once __DIR__ . '/Wikibase/repo/ExampleSettings.php';
	require_once __DIR__ . '/Wikibase/client/ExampleSettings.php';
}
