<?php
if ( !defined( 'MEDIAWIKI' ) ) {
	die( 'Not an entry point.' );
}

// Jenkins stuff part1
if ( isset( $wgWikimediaJenkinsCI ) && $wgWikimediaJenkinsCI == true ) {
	// in future, run as non-experimental
	if ( !defined( 'WB_EXPERIMENTAL_FEATURES' ) || !WB_EXPERIMENTAL_FEATURES ) {
		define( 'WB_EXPERIMENTAL_FEATURES', true );
	}

	$wmgUseWikibaseRepo = true;
	$wmgUseWikibaseClient = true;
	$wmgUseWikibaseQuality = true;
	$wmgUseWikibaseQualityExternalValidation = true;
}

// no magic, use wmf configs instead to control which entry points to load
$wgEnableWikibaseRepo = false;
$wgEnableWikibaseClient = false;

$wgWikidataBaseDir = $IP;

if ( file_exists(  __DIR__ . '/vendor/autoload.php' ) ) {
	include_once __DIR__ . '/vendor/autoload.php';

	$wgWikidataBaseDir = __DIR__;
}

if ( !empty( $wmgUseWikibaseRepo ) ) {
	include_once "$wgWikidataBaseDir/extensions/Wikibase/repo/Wikibase.php";
	include_once "$wgWikidataBaseDir/extensions/Wikidata.org/WikidataOrg.php";
	include_once "$wgWikidataBaseDir/extensions/PropertySuggester/PropertySuggester.php";
	if ( !empty( $wmgUseWikibaseQuality ) ) {
		include_once "$wgWikidataBaseDir/extensions/Quality/WikibaseQuality.php";
		include_once "$wgWikidataBaseDir/extensions/Constraints/WikibaseQualityConstraints.php";

		// @note wikibase/external-validation is removed from composer.json for
		// deployment builds, during the 'branch' grunt command. (pending security review)
		if ( !empty( $wmgUseWikibaseQualityExternalValidation ) ) {
			include_once "$wgWikidataBaseDir/extensions/ExternalValidation/WikibaseQualityExternalValidation.php";
		}
	}
}

if ( !empty( $wmgUseWikibaseClient ) ) {
	include_once "$wgWikidataBaseDir/extensions/Wikibase/client/WikibaseClient.php";
}

if ( file_exists(  __DIR__ . '/vendor/autoload.php' ) ) {
	// @fixme generating these settings with composer doesn't work with
	// the composer-merge-plugin. We would need to fix that and probably
	// handle this somewhat differently.  For now, this way at least
	// works with the current Wikidata build process, with composer run
	// here and the results committed to gerrit.
	//
	// see T95663 for details on migrating and supporting install with
	// composer-merge-plugin
	if ( !empty( $wmgUseWikibaseRepo ) ) {
		include_once __DIR__ . '/WikibaseRepo.settings.php';
	}

	if ( !empty( $wmgUseWikibaseClient ) ) {
		include_once __DIR__ . '/WikibaseClient.settings.php';
	}
}

$wgHooks['UnitTestsList'][] = '\Wikidata\WikidataHooks::onUnitTestsList';

$wgExtensionCredits['wikibase'][] = array(
	'path' => __FILE__,
	'name' => 'Wikidata',
	'author' => array(
		'The Wikidata team', // TODO: link?
	),
	'url' => 'https://www.mediawiki.org/wiki/Wikidata_build',
	'description' => 'Wikidata extensions build'
);

// Jenkins stuff part2
if ( isset( $wgWikimediaJenkinsCI ) && $wgWikimediaJenkinsCI == true ) {
	//Jenkins always loads both so no need to check if they are loaded before getting settings
	require_once __DIR__ . '/extensions/Wikibase/repo/ExampleSettings.php';
	require_once __DIR__ . '/extensions/Wikibase/client/ExampleSettings.php';
}
