<?php

if ( !defined( 'MEDIAWIKI' ) ) {
	die( 'Not an entry point.' );
}

$wgExtensionCredits['wikibase'][] = array(
	'path' => __FILE__,
	'name' => 'Wikidata Build',
	'author' => array(
		'The Wikidata team', // TODO: link?
	),
	'url' => 'https://www.mediawiki.org/wiki/Wikidata_build',
	'description' => 'Wikidata extensions build',
	'version' => 0.1,
	'license-name' => 'GPL-2.0+'
);
