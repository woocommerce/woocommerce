<?php

namespace Automattic\WooCommerce\Tests\ComingSoon;

use Automattic\WooCommerce\Internal\ComingSoon\ComingSoonCacheInvalidator;

/**
 * Tests for the coming soon cache invalidator class.
 */
class ComingSoonCacheInvalidatorTest extends \WC_Unit_Test_Case {

	/**
	 * System under test.
	 *
	 * @var ComingSoonCacheInvalidator;
	 */
	private $sut;

	/**
	 * Setup.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->sut = wc_get_container()->get( ComingSoonCacheInvalidator::class );
	}

	/**
	 * @testdox Test cache invalidation when coming soon option is changed to yes.
	 */
	public function test_cache_invalidated_when_coming_soon_option_is_changed_yes() {
		update_option( 'woocommerce_coming_soon', 'no' );
		wp_cache_set( 'test_foo', 'bar' );
		update_option( 'woocommerce_coming_soon', 'yes' );

		$this->assertFalse( wp_cache_get( 'test_foo' ) );
	}

	/**
	 * @testdox Test cache invalidation when coming soon option is changed to no.
	 */
	public function test_cache_invalidated_when_coming_soon_option_is_changed_no() {
		update_option( 'woocommerce_coming_soon', 'yes' );
		wp_cache_set( 'test_foo', 'bar' );
		update_option( 'woocommerce_coming_soon', 'no' );

		$this->assertFalse( wp_cache_get( 'test_foo' ) );
	}

	/**
	 * @testdox Test cache invalidation when store pages only option is changed to yes.
	 */
	public function test_cache_invalidated_when_store_pages_only_option_is_changed_yes() {
		update_option( 'woocommerce_store_pages_only', 'no' );
		wp_cache_set( 'test_foo', 'bar' );
		update_option( 'woocommerce_store_pages_only', 'yes' );

		$this->assertFalse( wp_cache_get( 'test_foo' ) );
	}

	/**
	 * @testdox Test cache invalidation when store pages only option is changed to no.
	 */
	public function test_cache_invalidated_when_store_pages_only_option_is_changed_no() {
		update_option( 'woocommerce_store_pages_only', 'yes' );
		wp_cache_set( 'test_foo', 'bar' );
		update_option( 'woocommerce_store_pages_only', 'no' );

		$this->assertFalse( wp_cache_get( 'test_foo' ) );
	}
}