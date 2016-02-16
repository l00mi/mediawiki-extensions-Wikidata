<?php

if ( !defined( 'MEDIAWIKI' ) ) {
	die( 'Not an entry point.' );
}

// no magic, use wmf configs instead to control which entry points to load
$wgEnableWikibaseRepo = false;
$wgEnableWikibaseClient = false;

$wgWikidataBaseDir = $IP;

if ( file_exists(  __DIR__ . '/vendor/autoload.php' ) ) {
	$wgWikidataBaseDir = __DIR__;
	require_once __DIR__ . '/vendor/autoload.php';
}

require_once "$wgWikidataBaseDir/extensions/Wikibase/repo/Wikibase.php";
require_once "$wgWikidataBaseDir/extensions/Wikidata.org/WikidataOrg.php";
require_once "$wgWikidataBaseDir/extensions/WikimediaBadges/WikimediaBadges.php";
require_once "$wgWikidataBaseDir/extensions/PropertySuggester/PropertySuggester.php";
require_once "$wgWikidataBaseDir/extensions/Wikibase/client/WikibaseClient.php";
require_once "$wgWikidataBaseDir/extensions/Quality/WikibaseQuality.php";
require_once "$wgWikidataBaseDir/extensions/Constraints/WikibaseQualityConstraints.php";
require_once "$wgWikidataBaseDir/extensions/ArticlePlaceholder/ArticlePlaceholder.php";

require_once "$wgWikidataBaseDir/Wikidata.credits.php";
