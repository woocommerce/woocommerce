<?php
/**
 * This file is part of the WooCommerce Email Editor package.
 *
 * @package Automattic\WooCommerce\EmailEditor
 */

declare(strict_types = 1);
namespace Automattic\WooCommerce\EmailEditor\Engine;

use WP_Theme_JSON_Resolver;

/**
 * Clears the theme.json data that WordPress keeps in memory.
 *
 * Core's WP_Theme_JSON_Resolver, and the Gutenberg plugin's copy of it, keep resolved theme.json data
 * for the whole PHP process and don't reset it on switch_to_blog(), so it can belong to another site.
 */
class Theme_Json_Cache {
	/**
	 * Clear the theme.json data kept by core and by the Gutenberg plugin.
	 */
	public static function clean(): void {
		WP_Theme_JSON_Resolver::clean_cached_data();
		if ( class_exists( 'WP_Theme_JSON_Resolver_Gutenberg' ) ) {
			\WP_Theme_JSON_Resolver_Gutenberg::clean_cached_data();
		}
	}

	/**
	 * Clear the theme.json data once, when the next switch to another site happens.
	 *
	 * Call it when loading a switched site's data, so the site that switched doesn't get that data
	 * back after restore_current_blog().
	 */
	public static function clean_on_next_blog_switch(): void {
		if ( false === has_action( 'switch_blog', array( self::class, 'clean_on_blog_switch' ) ) ) {
			add_action( 'switch_blog', array( self::class, 'clean_on_blog_switch' ), 10, 2 );
		}
	}

	/**
	 * Clear the theme.json data when the site changes, then stop listening.
	 *
	 * Callback for the `switch_blog` action added by clean_on_next_blog_switch().
	 *
	 * @param int|string $new_blog_id  ID of the site switched to.
	 * @param int|string $prev_blog_id ID of the site switched from.
	 */
	public static function clean_on_blog_switch( $new_blog_id, $prev_blog_id ): void {
		if ( (int) $new_blog_id === (int) $prev_blog_id ) {
			return;
		}
		remove_action( 'switch_blog', array( self::class, 'clean_on_blog_switch' ), 10 );
		self::clean();
	}
}
