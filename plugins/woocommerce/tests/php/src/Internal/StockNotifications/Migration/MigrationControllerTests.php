<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\StockNotifications\Migration;

use Automattic\WooCommerce\Internal\StockNotifications\Migration\Compat\LegacyLinkShim;
use Automattic\WooCommerce\Internal\StockNotifications\Migration\MigrationController;
use Automattic\WooCommerce\Internal\StockNotifications\Migration\MigrationState;
use Automattic\WooCommerce\Tests\Internal\StockNotifications\Migration\Helpers\LegacyStore;
use WC_Unit_Test_Case;

/**
 * Tests for the registration gates: what a store that never had the legacy extension pays
 * for the migration existing, and what the shim's own flag controls.
 */
class MigrationControllerTests extends WC_Unit_Test_Case {

	/**
	 * Controller under test.
	 *
	 * @var MigrationController
	 */
	private MigrationController $controller;

	/**
	 * Set up a clean option state.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->clear_options();

		// Registration needs the feature on. Set it explicitly rather than relying on the
		// install default, so the gate below is actually exercised.
		update_option( 'woocommerce_feature_customer_stock_notifications_enabled', 'yes' );

		// The shim hooks itself in its constructor, so a cached resolution from an earlier
		// test would make registration look like a no-op here.
		$this->reset_container_resolutions();

		$this->controller = new MigrationController();
	}

	/**
	 * Clear the options and the hooks registration may have added.
	 */
	public function tearDown(): void {
		remove_all_filters( 'woocommerce_debug_tools' );
		remove_all_actions( 'admin_notices' );
		remove_all_actions( 'template_redirect' );

		$this->clear_options();
		set_current_screen( 'front' );

		parent::tearDown();
	}

	/**
	 * @testdox a store that never installed the legacy extension should register nothing.
	 */
	public function test_clean_store_registers_nothing(): void {
		$this->controller->register();

		$this->assertFalse( has_filter( 'woocommerce_debug_tools', array( $this->controller, 'handle_woocommerce_debug_tools' ) ) );
		$this->assertFalse( has_action( 'admin_notices', array( $this->controller, 'maybe_render_double_send_notice' ) ) );
		$this->assertFalse( $this->shim_is_hooked(), 'The shim must not register on a clean store.' );
	}

	/**
	 * @testdox registration should issue no queries on a clean store.
	 */
	public function test_clean_store_registration_issues_no_queries(): void {
		$queries = array();

		$recorder = function ( $query ) use ( &$queries ) {
			$queries[] = $query;

			return $query;
		};

		add_filter( 'query', $recorder );
		$this->controller->register();
		remove_filter( 'query', $recorder );

		$table_probes = array_filter(
			$queries,
			static function ( $query ) {
				return false !== stripos( $query, 'SHOW TABLES LIKE' ) || false !== stripos( $query, 'woocommerce_bis_' );
			}
		);

		$this->assertSame( array(), array_values( $table_probes ), 'Registration must not probe for the legacy tables.' );
	}

	/**
	 * @testdox a store with the feature off should register nothing, legacy history or not.
	 */
	public function test_feature_off_registers_nothing(): void {
		update_option( 'wc_bis_db_version', '1.2.0' );
		update_option( 'wc_bis_migration_has_legacy_links', 'yes' );
		update_option( 'woocommerce_feature_customer_stock_notifications_enabled', 'no' );

		$this->controller->register();

		$this->assertFalse( has_filter( 'woocommerce_debug_tools', array( $this->controller, 'handle_woocommerce_debug_tools' ) ) );
		$this->assertFalse( has_action( 'admin_notices', array( $this->controller, 'maybe_render_double_send_notice' ) ) );
		$this->assertFalse( $this->shim_is_hooked(), 'With the feature off there is no data store for the shim to read.' );
	}

	/**
	 * @testdox a store that once had the legacy extension should get the Tools entry.
	 */
	public function test_store_with_legacy_history_registers_the_tools_entry(): void {
		update_option( 'wc_bis_db_version', '1.2.0' );

		$this->controller->register();

		$this->assertNotFalse( has_filter( 'woocommerce_debug_tools', array( $this->controller, 'handle_woocommerce_debug_tools' ) ) );
		$this->assertFalse( $this->shim_is_hooked(), 'The shim needs its own flag, not just legacy history.' );
	}

	/**
	 * @testdox the shim should register only while the legacy-links flag is set.
	 */
	public function test_shim_registers_only_with_the_legacy_links_flag(): void {
		update_option( 'wc_bis_db_version', '1.2.0' );
		update_option( 'wc_bis_migration_has_legacy_links', 'yes' );

		$this->controller->register();

		$this->assertTrue( $this->shim_is_hooked() );
	}

	/**
	 * @testdox the Tools entry should be absent for a user without manage_woocommerce.
	 */
	public function test_tools_entry_is_absent_without_the_capability(): void {
		update_option( 'wc_bis_db_version', '1.2.0' );

		wp_set_current_user( $this->factory()->user->create( array( 'role' => 'customer' ) ) );

		$this->assertSame( array(), $this->controller->handle_woocommerce_debug_tools( array() ) );

		wp_set_current_user( $this->factory()->user->create( array( 'role' => 'administrator' ) ) );

		$this->assertNotEmpty( $this->controller->handle_woocommerce_debug_tools( array() ) );
	}

	/**
	 * @testdox the double-send notice should only render for a capable user with migrated rows.
	 */
	public function test_double_send_notice_needs_the_capability_and_migrated_rows(): void {
		update_option( 'wc_bis_db_version', '1.2.0' );
		set_current_screen( 'plugins' );

		wp_set_current_user( $this->factory()->user->create( array( 'role' => 'customer' ) ) );
		$this->assertSame( '', $this->render_double_send_notice() );

		wp_set_current_user( $this->factory()->user->create( array( 'role' => 'administrator' ) ) );
		$this->assertSame( '', $this->render_double_send_notice(), 'Nothing migrated yet, so nothing to warn about.' );
	}

	/**
	 * @testdox the double-send notice should render only where the merchant can act on it.
	 */
	public function test_double_send_notice_is_scoped_to_actionable_screens(): void {
		$this->arrange_double_send_conditions();

		set_current_screen( 'dashboard' );
		$this->assertSame( '', $this->render_double_send_notice(), 'The dashboard is not a screen this can be acted on from.' );

		set_current_screen( 'plugins' );
		$this->assertStringContainsString( 'Back In Stock Notifications', $this->render_double_send_notice() );

		set_current_screen( 'woocommerce_page_wc-status' );
		$this->assertStringContainsString( 'Back In Stock Notifications', $this->render_double_send_notice() );
	}

	/**
	 * @testdox the double-send notice should not be dismissible and should link to both screens.
	 */
	public function test_double_send_notice_carries_its_actions(): void {
		$this->arrange_double_send_conditions();
		set_current_screen( 'plugins' );

		$notice = $this->render_double_send_notice();

		$this->assertStringNotContainsString( 'is-dismissible', $notice, 'A silenced warning is a duplicate email nobody saw coming.' );
		$this->assertStringContainsString( 'page=wc-status&amp;tab=tools', $notice );
		$this->assertStringContainsString( 'plugins.php', $notice );
	}

	/**
	 * @testdox the double-send notice should ask for the migration to finish before deactivation.
	 */
	public function test_double_send_notice_asks_to_finish_an_unfinished_migration(): void {
		$this->arrange_double_send_conditions();
		set_current_screen( 'plugins' );

		$state = new MigrationState();
		$state->set_count( 'notifications', 12 );

		$this->assertStringContainsString( 'Finish the migration, then deactivate the extension.', $this->render_double_send_notice() );
	}

	/**
	 * @testdox the double-send notice should ask for deactivation once every section has drained.
	 */
	public function test_double_send_notice_asks_for_deactivation_once_drained(): void {
		$this->arrange_double_send_conditions();
		set_current_screen( 'plugins' );

		$state = new MigrationState();

		foreach ( array( 'notifications', 'product-meta' ) as $section ) {
			$state->set_count( $section, 0 );
		}

		$this->assertStringContainsString( 'All subscribers have moved', $this->render_double_send_notice() );
	}

	/**
	 * Whether the legacy unsubscribe shim has hooked itself into the request lifecycle.
	 *
	 * Checked by walking the hook's callbacks rather than by resolving the shim from the
	 * container, since resolving it is what registers it.
	 *
	 * @return bool
	 */
	private function shim_is_hooked(): bool {
		global $wp_filter;

		if ( ! isset( $wp_filter['template_redirect'] ) ) {
			return false;
		}

		foreach ( $wp_filter['template_redirect']->callbacks as $callbacks ) {
			foreach ( $callbacks as $callback ) {
				if ( is_array( $callback['function'] ) && $callback['function'][0] instanceof LegacyLinkShim ) {
					return true;
				}
			}
		}

		return false;
	}

	/**
	 * Put the store in the state the double-send notice warns about: an admin user, migrated
	 * rows, and the legacy extension still active.
	 *
	 * @return void
	 */
	private function arrange_double_send_conditions(): void {
		require_once ABSPATH . 'wp-admin/includes/plugin.php';

		update_option( 'wc_bis_db_version', '1.2.0' );
		update_option( 'wc_bis_migration_has_migrated_rows', 'yes' );
		update_option( 'active_plugins', array( 'woocommerce-back-in-stock-notifications/woocommerce-back-in-stock-notifications.php' ) );

		wp_set_current_user( $this->factory()->user->create( array( 'role' => 'administrator' ) ) );
	}

	/**
	 * Capture whatever the double-send notice renders.
	 *
	 * @return string
	 */
	private function render_double_send_notice(): string {
		ob_start();
		$this->controller->maybe_render_double_send_notice();

		return (string) ob_get_clean();
	}

	/**
	 * Delete every option registration reads, and drop any legacy tables left behind.
	 *
	 * @return void
	 */
	private function clear_options(): void {
		LegacyStore::drop_tables();

		delete_option( 'woocommerce_feature_customer_stock_notifications_enabled' );
		delete_option( 'wc_bis_db_version' );
		delete_option( 'wc_bis_migration_has_legacy_links' );
		delete_option( 'wc_bis_migration_has_migrated_rows' );
		delete_option( 'wc_bis_migration_state' );
		delete_option( 'wc_bis_migration_lock' );
		delete_option( 'wc_bis_migration_batch_lock' );
		delete_option( 'active_plugins' );
	}
}
