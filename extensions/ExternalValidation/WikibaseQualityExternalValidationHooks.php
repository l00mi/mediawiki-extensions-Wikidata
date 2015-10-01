<?php

final class WikibaseQualityExternalValidationHooks {

	/**
	 * @param DatabaseUpdater $updater
	 *
	 * @return bool
	 */
	public static function onCreateSchema( DatabaseUpdater $updater ) {
		$updater->addExtensionTable( 'wbqev_dump_information', __DIR__ . '/sql/create_wbqev_dump_information.sql' );
		$updater->addExtensionTable( 'wbqev_external_data', __DIR__ . '/sql/create_wbqev_external_data.sql' );
		$updater->addExtensionTable( 'wbqev_identifier_properties', __DIR__ . '/sql/create_wbqev_identifier_properties.sql' );

		return true;
	}

	public static function onUnitTestsList( &$paths ) {
		$paths[] = __DIR__ . '/tests/phpunit';
		return true;
	}

}
