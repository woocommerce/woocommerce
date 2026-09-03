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
		update_option( 'woocommerce_store_pages_only', 'no' );
	}

	/**
	 * Tear down test fixtures.
	 */
	public function tearDown(): void {
		delete_option( 'woocommerce_feature_site_visibility_badge_enabled' );
		delete_option( 'woocommerce_coming_soon' );
		delete_option( 'woocommerce_store_pages_only' );
		parent::tearDown();
	}

	/**
	 * @testdox Should show the badge when the store is live and the Live badge option is enabled.
	 */
	public function test_shows_badge_when_store_is_live_and_option_is_enabled(): void {
		update_option( 'woocommerce_coming_soon', 'no' );
		$admin_bar = new WP_Admin_Bar();

		$this->sut->site_visibility_badge( $admin_bar );

		$this->assertSame( 'Live', $admin_bar->get_node( 'woocommerce-site-visibility-badge' )->title );
	}

	/**
	 * @testdox Should hide the badge when the store is live and the Live badge option is missing.
	 */
	public function test_hides_badge_when_store_is_live_and_option_is_missing(): void {
		update_option( 'woocommerce_coming_soon', 'no' );
		delete_option( 'woocommerce_feature_site_visibility_badge_enabled' );
		$admin_bar = new WP_Admin_Bar();

		$this->sut->site_visibility_badge( $admin_bar );

		$this->assertNull( $admin_bar->get_node( 'woocommerce-site-visibility-badge' ) );
	}

	/**
	 * @testdox Should hide the badge when the store is live and the Live badge option is disabled.
	 */
	public function test_hides_badge_when_store_is_live_and_option_is_disabled(): void {
		update_option( 'woocommerce_coming_soon', 'no' );
		update_option( 'woocommerce_feature_site_visibility_badge_enabled', 'no' );
		$admin_bar = new WP_Admin_Bar();

		$this->sut->site_visibility_badge( $admin_bar );

		$this->assertNull( $admin_bar->get_node( 'woocommerce-site-visibility-badge' ) );
	}

	/**
	 * @testdox Should show the badge when the store is coming soon.
	 */
	public function test_shows_badge_when_store_is_coming_soon(): void {
		update_option( 'woocommerce_coming_soon', 'yes' );
		update_option( 'woocommerce_feature_site_visibility_badge_enabled', 'no' );
		$admin_bar = new WP_Admin_Bar();

		$this->sut->site_visibility_badge( $admin_bar );

		$this->assertSame( 'Coming soon', $admin_bar->get_node( 'woocommerce-site-visibility-badge' )->title );
	}
}
