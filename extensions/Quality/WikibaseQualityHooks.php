<?php

final class WikibaseQualityHooks {

	public static function onUnitTestsList( &$paths ) {
		$paths[] = __DIR__ . '/tests/phpunit';
		return true;
	}
}