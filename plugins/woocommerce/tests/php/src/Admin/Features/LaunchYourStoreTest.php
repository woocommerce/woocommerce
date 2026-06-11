<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Admin\Features;

use Automattic\WooCommerce\Admin\Features\LaunchYourStore;
use Automattic\WooCommerce\Internal\Admin\WCAdminUser;

/**
 * Tests for the LaunchYourStore class, focusing on the frontend coming soon banner
 * behavior that runs outside the wc-admin feature loader.
 */
class LaunchYourStoreTest extends \WC_Unit_Test_Case {

	/**
	 * System under test.
	 *
	 * @var LaunchYourStore
	 */
	private $sut;

	/**
	 * Setup.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->sut = wc_get_container()->get( LaunchYourStore::class );
		update_option( 'woocommerce_coming_soon', 'yes' );
		update_option( 'woocommerce_store_pages_only', 'no' );
	}

	/**
	 * Teardown.
	 */
	public function tearDown(): void {
		delete_option( 'woocommerce_coming_soon' );
		delete_option( 'woocommerce_store_pages_only' );
		wp_set_current_user( 0 );
		parent::tearDown();
	}

	/**
	 * Renders the banner and returns the output.
	 *
	 * @return string
	 */
	private function render_banner(): string {
		ob_start();
		$this->sut->maybe_add_coming_soon_banner_on_frontend();
		return ob_get_clean();
	}

	/**
	 * @testdox The frontend hooks are registered on the shared container instance.
	 */
	public function test_frontend_hooks_are_registered(): void {
		$this->assertNotFalse( has_action( 'wp_footer', array( $this->sut, 'maybe_add_coming_soon_banner_on_frontend' ) ), 'wp_footer hook should be registered' );
		$this->assertNotFalse( has_action( 'wp_login', array( $this->sut, 'reset_woocommerce_coming_soon_banner_dismissed' ) ), 'wp_login hook should be registered' );
		$this->assertNotFalse( has_filter( 'woocommerce_tracks_event_properties', array( $this->sut, 'append_coming_soon_global_tracks' ) ), 'tracks properties filter should be registered' );
	}

	/**
	 * @testdox The banner is rendered for administrators when coming soon mode is enabled.
	 */
	public function test_banner_is_rendered_for_admin_in_coming_soon_mode(): void {
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );

		$this->assertStringContainsString( 'coming-soon-footer-banner', $this->render_banner(), 'Admins should see the banner in coming soon mode' );
	}

	/**
	 * @testdox The banner is not rendered for logged-out visitors.
	 */
	public function test_banner_is_not_rendered_for_logged_out_visitors(): void {
		wp_set_current_user( 0 );

		$this->assertSame( '', $this->render_banner(), 'Logged-out visitors should not see the banner' );
	}

	/**
	 * @testdox The banner is not rendered for users without store management roles.
	 */
	public function test_banner_is_not_rendered_for_customers(): void {
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'customer' ) ) );

		$this->assertSame( '', $this->render_banner(), 'Customers should not see the banner' );
	}

	/**
	 * @testdox The banner is not rendered when coming soon mode is disabled.
	 */
	public function test_banner_is_not_rendered_when_coming_soon_is_off(): void {
		update_option( 'woocommerce_coming_soon', 'no' );
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );

		$this->assertSame( '', $this->render_banner(), 'The banner should not render when coming soon mode is off' );
	}

	/**
	 * @testdox The banner is not rendered when the user has dismissed it.
	 */
	public function test_banner_is_not_rendered_when_dismissed(): void {
		$user_id = self::factory()->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $user_id );
		WCAdminUser::update_user_data_field( $user_id, LaunchYourStore::BANNER_DISMISS_USER_META_KEY, 'yes' );

		$this->assertSame( '', $this->render_banner(), 'The banner should not render after being dismissed' );
	}

	/**
	 * @testdox Logging in resets the dismissed state so the banner reappears each session.
	 */
	public function test_login_resets_dismissed_state(): void {
		$user_id = self::factory()->user->create( array( 'role' => 'administrator' ) );
		WCAdminUser::update_user_data_field( $user_id, LaunchYourStore::BANNER_DISMISS_USER_META_KEY, 'yes' );

		$user = get_user_by( 'id', $user_id );
		$this->sut->reset_woocommerce_coming_soon_banner_dismissed( $user->user_login, $user );

		$this->assertSame( 'no', WCAdminUser::get_user_data_field( $user_id, LaunchYourStore::BANNER_DISMISS_USER_META_KEY ), 'Dismissed state should reset to no on login' );
	}

	/**
	 * @testdox Tracks event properties get the coming_soon property appended.
	 */
	public function test_tracks_properties_include_coming_soon_state(): void {
		$this->assertSame( 'site', $this->sut->append_coming_soon_global_tracks( array() )['coming_soon'], 'Site-wide coming soon should report "site"' );

		update_option( 'woocommerce_store_pages_only', 'yes' );
		$this->assertSame( 'store', $this->sut->append_coming_soon_global_tracks( array() )['coming_soon'], 'Store-pages-only coming soon should report "store"' );

		update_option( 'woocommerce_coming_soon', 'no' );
		$this->assertSame( 'no', $this->sut->append_coming_soon_global_tracks( array() )['coming_soon'], 'Live sites should report "no"' );
	}

	/**
	 * @testdox The feature loader skips launch-your-store so its hooks are not registered twice.
	 */
	public function test_feature_loader_does_not_duplicate_hook_registration(): void {
		global $wp_filter;

		$count = 0;
		if ( isset( $wp_filter['wp_footer'] ) ) {
			foreach ( $wp_filter['wp_footer']->callbacks as $callbacks ) {
				foreach ( $callbacks as $callback ) {
					if ( is_array( $callback['function'] ) && $callback['function'][0] instanceof LaunchYourStore && 'maybe_add_coming_soon_banner_on_frontend' === $callback['function'][1] ) {
						++$count;
					}
				}
			}
		}

		$this->assertSame( 1, $count, 'The banner callback should be registered exactly once' );
	}
}
