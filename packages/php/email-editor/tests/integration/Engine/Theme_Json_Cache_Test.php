<?php
/**
 * This file is part of the WooCommerce Email Editor package.
 *
 * @package Automattic\WooCommerce\EmailEditor
 */

declare(strict_types = 1);
namespace Automattic\WooCommerce\EmailEditor\Engine;

/**
 * Integration test for Theme_Json_Cache class
 */
class Theme_Json_Cache_Test extends \Email_Editor_Integration_Test_Case {
	/**
	 * Load the stand-in for the Gutenberg plugin's resolver and reset its counter.
	 */
	public function setUp(): void {
		parent::setUp();
		if ( ! class_exists( 'WP_Theme_JSON_Resolver_Gutenberg' ) ) {
			require_once __DIR__ . '/Fixtures/class-wp-theme-json-resolver-gutenberg.php';
		}
		if ( ! property_exists( 'WP_Theme_JSON_Resolver_Gutenberg', 'clean_count' ) ) {
			$this->markTestSkipped( 'The real Gutenberg plugin is loaded.' );
		}
		\WP_Theme_JSON_Resolver_Gutenberg::$clean_count = 0;
	}

	/**
	 * Test it also clears the Gutenberg plugin's resolver.
	 */
	public function testItCleansGutenbergResolverToo(): void {
		Theme_Json_Cache::clean();

		$this->assertSame( 1, \WP_Theme_JSON_Resolver_Gutenberg::$clean_count );
	}

	/**
	 * Test it clears once, on the next switch to another site.
	 */
	public function testItCleansOnceOnNextSwitchToAnotherSite(): void {
		$listener = array( Theme_Json_Cache::class, 'clean_on_blog_switch' );
		Theme_Json_Cache::clean_on_next_blog_switch();
		Theme_Json_Cache::clean_on_next_blog_switch();

		Theme_Json_Cache::clean_on_blog_switch( 1, 1 );
		$this->assertSame( 0, \WP_Theme_JSON_Resolver_Gutenberg::$clean_count, 'A switch to the same site should not clear.' );
		$this->assertNotFalse( has_action( 'switch_blog', $listener ), 'The listener should wait for a switch to another site.' );

		Theme_Json_Cache::clean_on_blog_switch( 2, 1 );
		$this->assertSame( 1, \WP_Theme_JSON_Resolver_Gutenberg::$clean_count );
		$this->assertFalse( has_action( 'switch_blog', $listener ), 'The listener should stop after it clears.' );
	}
}
