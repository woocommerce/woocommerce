<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\ComingSoon;

use Automattic\WooCommerce\Internal\ComingSoon\ComingSoonAdminBarBadge;
use WC_Unit_Test_Case;
use WP_Admin_Bar;

/**
 * Tests for the ComingSoonAdminBarBadge class.
 */
class ComingSoonAdminBarBadgeTest extends WC_Unit_Test_Case {

	/**
	 * The System Under Test.
	 *
	 * @var ComingSoonAdminBarBadge
	 */
	private $sut;

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();
		require_once ABSPATH . WPINC . '/class-wp-admin-bar.php';
		$this->sut = new ComingSoonAdminBarBadge();
		update_option( 'woocommerce_feature_site_visibility_badge_enabled', 'yes' );
	}

	/**
	 * Tear down test fixtures.
	 */
	public function tearDown(): void {
		remove_filter( 'woocommerce_should_display_site_visibility_badge', '__return_true' );
		remove_filter( 'woocommerce_should_display_site_visibility_badge', '__return_false' );
		delete_option( 'woocommerce_feature_site_visibility_badge_enabled' );
		delete_option( 'woocommerce_coming_soon' );
		parent::tearDown();
	}

	/**
	 * @testdox Should hide the badge when the store is live.
	 */
	public function test_hides_badge_when_store_is_live(): void {
		update_option( 'woocommerce_coming_soon', 'no' );
		$admin_bar = new WP_Admin_Bar();

		$this->sut->site_visibility_badge( $admin_bar );

		$this->assertNull( $admin_bar->get_node( 'woocommerce-site-visibility-badge' ) );
	}

	/**
	 * @testdox Should show the badge when the store is coming soon.
	 */
	public function test_shows_badge_when_store_is_coming_soon(): void {
		update_option( 'woocommerce_coming_soon', 'yes' );
		$admin_bar = new WP_Admin_Bar();

		$this->sut->site_visibility_badge( $admin_bar );

		$this->assertSame( 'Coming soon', $admin_bar->get_node( 'woocommerce-site-visibility-badge' )->title );
	}

	/**
	 * @testdox Should allow the display filter to show the badge when the store is live.
	 */
	public function test_filter_can_show_badge_when_store_is_live(): void {
		update_option( 'woocommerce_coming_soon', 'no' );
		add_filter( 'woocommerce_should_display_site_visibility_badge', '__return_true' );
		$admin_bar = new WP_Admin_Bar();

		$this->sut->site_visibility_badge( $admin_bar );

		$this->assertSame( 'Live', $admin_bar->get_node( 'woocommerce-site-visibility-badge' )->title );
	}

	/**
	 * @testdox Should allow the display filter to hide the badge when the store is coming soon.
	 */
	public function test_filter_can_hide_badge_when_store_is_coming_soon(): void {
		update_option( 'woocommerce_coming_soon', 'yes' );
		add_filter( 'woocommerce_should_display_site_visibility_badge', '__return_false' );
		$admin_bar = new WP_Admin_Bar();

		$this->sut->site_visibility_badge( $admin_bar );

		$this->assertNull( $admin_bar->get_node( 'woocommerce-site-visibility-badge' ) );
	}
}
