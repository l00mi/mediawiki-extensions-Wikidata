<?php
if ( function_exists( 'wfLoadExtension' ) ) {
	wfLoadExtension( 'WikibaseQuality', __DIR__ . '/extension.json' );
	// Keep i18n globals so mergeMessageFileList.php doesn't break
	$wgMessagesDirs['WikibaseQuality'] = __DIR__ . '/i18n';
	$wgExtensionMessagesFiles['WikibaseQualityAlias'] = __DIR__ . '/WikibaseQuality.alias.php';
	/*wfWarn(
		'Deprecated PHP entry point used for WikibaseQuality extension. Please use wfLoadExtension instead, ' .
		'see https://www.mediawiki.org/wiki/Extension_registration for more details.'
	);*/
	return;
} else {
	die( 'This version of the WikibaseQuality extension requires MediaWiki 1.25+' );
}
