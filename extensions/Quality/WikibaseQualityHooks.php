<?php

final class WikibaseQualityHooks {

	/**
	 * @param DatabaseUpdater $updater
	 *
	 * @return bool
	 */
	public static function onCreateSchema( DatabaseUpdater $updater ) {
		$updater->addExtensionTable( 'wbq_violations', __DIR__ . '/sql/create_wbq_violations.sql' );
		return true;
	}

	public static function onUnitTestsList( &$paths ) {
		$paths[] = __DIR__ . '/tests/phpunit';
		return true;
	}
}