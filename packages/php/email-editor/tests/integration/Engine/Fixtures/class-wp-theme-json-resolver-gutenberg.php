<?php
/**
 * This file is part of the WooCommerce Email Editor package.
 *
 * @package Automattic\WooCommerce\EmailEditor
 */

declare(strict_types = 1);

/**
 * Stand-in for the Gutenberg plugin's resolver, which the test environment doesn't load.
 */
class WP_Theme_JSON_Resolver_Gutenberg {
	/**
	 * How many times clean_cached_data() ran.
	 *
	 * @var int
	 */
	public static int $clean_count = 0;

	/**
	 * Count the call.
	 */
	public static function clean_cached_data(): void {
		++self::$clean_count;
	}
}
