<?php
/**
 * MigrationController class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\StockNotifications\Migration;

use Automattic\WooCommerce\Internal\RegisterHooksInterface;
use Automattic\WooCommerce\Internal\StockNotifications\Migration\Compat\LegacyLinkShim;
use Automattic\WooCommerce\Internal\StockNotifications\Migration\Runners\ToolsRegistrar;
use Automattic\WooCommerce\Internal\StockNotifications\StockNotifications;

defined( 'ABSPATH' ) || exit;

/**
 * Gates registration of the BIS-to-Core stock notifications migration and wires it up.
 *
 * This is the only class in `Migration\` autoloaded on a normal request. Every decision below
 * reads an option that is already autoloaded and in memory, so registration itself never runs a
 * query. Everything else - the Tools entry, the CLI commands, the legacy link shim - is resolved
 * from the container lazily, inside the callback that actually needs it, so nothing beyond this
 * class is autoloaded until one of those callbacks fires.
 *
 * Registration needs both the Customer stock notifications feature to be on and the legacy
 * extension to have been installed here. With the feature off there is nothing to migrate into:
 * the `stock_notification` data store is not registered, so the legacy link shim could not load
 * a notification, and Core sends nothing, so the double-send notice would have nothing to warn
 * about. The CLI command registers from `WC_CLI` rather than from here, but applies the same
 * two conditions in `Cli::register()`, so with the feature off `wp wc bis-migrate` does not
 * exist at all.
 */
class MigrationController implements RegisterHooksInterface {

	/**
	 * Option the legacy extension writes on install, with no autoload argument (so it is
	 * autoloaded) and never deletes. Its presence is the answer to "was the extension ever
	 * installed" - the extension ships no uninstall.php, so the option and its tables survive
	 * plugin deletion together.
	 */
	private const OPTION_LEGACY_DB_VERSION = Constants::DB_VERSION_OPTION;

	/**
	 * Autoloaded flag the notifications migrator sets the first time it writes a row carrying a
	 * legacy token, of either kind - unsubscribe or verification. Answers "are there live legacy
	 * links", and guards only the legacy link shim's registration - it is not a general "migrated
	 * rows exist" signal, since a store whose rows carry neither kind of token never sets it. See
	 * OPTION_HAS_MIGRATED_ROWS for that broader question.
	 */
	private const OPTION_HAS_LEGACY_LINKS = Constants::HAS_LEGACY_LINKS_OPTION;

	/**
	 * Autoloaded flag the notifications migrator sets the first time it migrates any row,
	 * inserted or adopted, regardless of whether it carries a legacy token. Answers
	 * "have any rows been migrated" for the double-send admin notice below.
	 */
	private const OPTION_HAS_MIGRATED_ROWS = Constants::HAS_MIGRATED_ROWS_OPTION;

	/**
	 * Basename of the legacy Back In Stock Notifications extension, as WordPress lists it in
	 * the active plugins option.
	 */
	private const LEGACY_PLUGIN_BASENAME = 'woocommerce-back-in-stock-notifications/woocommerce-back-in-stock-notifications.php';

	/**
	 * Screens the double-send notice renders on, beyond WooCommerce's own: the plugins list,
	 * where the merchant deactivates the extension. Everywhere else the notice is a warning
	 * they cannot act on from where they are standing.
	 */
	private const EXTRA_NOTICE_SCREENS = array( 'plugins', 'plugins-network' );

	/**
	 * Register the migration's hooks, gated per the rules above.
	 *
	 * @internal
	 */
	public function register(): void {
		// The `wp wc bis-migrate` command registers itself from WC_CLI, on `after_wp_load`,
		// like every other WooCommerce command. Registering here would resolve it out of the
		// container on every `wp` invocation, `wp help` included.
		if ( ! $this->feature_is_enabled() || ! $this->extension_was_ever_installed() ) {
			return;
		}

		add_filter( 'woocommerce_debug_tools', array( $this, 'handle_woocommerce_debug_tools' ) );
		add_action( 'admin_notices', array( $this, 'maybe_render_double_send_notice' ) );

		if ( get_option( self::OPTION_HAS_LEGACY_LINKS ) ) {
			// The shim hooks itself in its constructor; resolving it from the container is
			// the registration.
			wc_get_container()->get( LegacyLinkShim::class );
		}
	}

	/**
	 * Add or remove the `woocommerce_debug_tools` entry for the migration.
	 *
	 * Delegates to `Runners\ToolsRegistrar`, resolved lazily so nothing beyond this class is
	 * autoloaded until the Tools page actually filters its list.
	 *
	 * @internal
	 *
	 * @param array $tools Existing tools list.
	 * @return array
	 */
	public function handle_woocommerce_debug_tools( array $tools ): array {
		return wc_get_container()->get( ToolsRegistrar::class )->handle_woocommerce_debug_tools( $tools );
	}

	/**
	 * Warn while the legacy extension is still active and rows have already been migrated: a
	 * restock in that state emails from both the legacy queue and Core.
	 *
	 * Reads `wc_bis_migration_has_migrated_rows`, set the first time any row is migrated
	 * regardless of whether it carries a legacy token. `wc_bis_migration_has_legacy_links` would
	 * under-report this: a store whose rows carry no legacy token never sets it, even with
	 * migrated rows present.
	 *
	 * Rendered on WooCommerce's own screens and on the plugins list, the places the merchant
	 * can act from, and never dismissible: the cost of silencing it is a customer receiving the
	 * same restock email twice. Never auto-deactivates the extension; this is a notice only.
	 *
	 * @internal
	 */
	public function maybe_render_double_send_notice(): void {
		if ( ! $this->is_notice_screen() || ! current_user_can( 'manage_woocommerce' ) ) {
			return;
		}

		if ( ! get_option( self::OPTION_HAS_MIGRATED_ROWS ) ) {
			return;
		}

		if ( ! function_exists( 'is_plugin_active' ) || ! is_plugin_active( self::LEGACY_PLUGIN_BASENAME ) ) {
			return;
		}

		wp_admin_notice(
			$this->double_send_notice_message(),
			array(
				'id'          => 'wc-bis-migration-double-send',
				'type'        => 'warning',
				'dismissible' => false,
			)
		);
	}

	/**
	 * The double-send notice's text, in the state the migration is actually in.
	 *
	 * Two sentences: what is happening, and what to do about it. A finished migration is asked
	 * to deactivate the extension; an unfinished one is asked to finish first, since
	 * deactivating mid-migration strands whatever has not moved yet.
	 *
	 * @return string
	 */
	private function double_send_notice_message(): string {
		$status_link = sprintf(
			'<a href="%s">%s</a>',
			esc_url( admin_url( 'admin.php?page=wc-status&tab=tools' ) ),
			esc_html__( 'View migration status', 'woocommerce' )
		);

		if ( $this->migration_is_drained() ) {
			return sprintf(
				/* translators: 1: link to the plugins screen, 2: link to the migration's entry on the Status → Tools screen */
				esc_html__( 'All subscribers have moved to the built-in stock notifications. Deactivate Back In Stock Notifications to stop duplicate restock emails. %1$s or %2$s.', 'woocommerce' ),
				$this->plugins_link( esc_html__( 'Manage plugins', 'woocommerce' ) ),
				$status_link
			);
		}

		return sprintf(
			/* translators: 1: link to the migration's entry on the Status → Tools screen, 2: link to the plugins screen */
			esc_html__( 'Back In Stock Notifications is still active, so migrated customers can get two emails per restock. Finish the migration, then deactivate the extension. %1$s or %2$s.', 'woocommerce' ),
			$status_link,
			$this->plugins_link( esc_html__( 'manage plugins', 'woocommerce' ) )
		);
	}

	/**
	 * A link to the active plugins list, under the label the sentence needs.
	 *
	 * @param string $label Link text, already escaped.
	 * @return string
	 */
	private function plugins_link( string $label ): string {
		return sprintf( '<a href="%s">%s</a>', esc_url( admin_url( 'plugins.php?plugin_status=active' ) ), $label );
	}

	/**
	 * Whether every section of the migration has run out of rows to visit.
	 *
	 * Reads the counts cached in `wc_bis_migration_state`, which a run refreshes when a
	 * section drains, so this is one option read and never a count query. A section that has
	 * never been counted is treated as unfinished: nothing has proved otherwise.
	 *
	 * @return bool
	 */
	private function migration_is_drained(): bool {
		$state = wc_get_container()->get( MigrationState::class );

		foreach ( Constants::SECTION_ORDER as $section ) {
			$cached = $state->get_count( $section );

			if ( null === $cached || (int) $cached['count'] > 0 ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Whether the current admin screen is one the double-send notice belongs on.
	 *
	 * @return bool
	 */
	private function is_notice_screen(): bool {
		if ( ! function_exists( 'get_current_screen' ) || ! function_exists( 'wc_get_screen_ids' ) ) {
			return false;
		}

		$screen = get_current_screen();

		if ( null === $screen ) {
			return false;
		}

		return in_array( $screen->id, array_merge( wc_get_screen_ids(), self::EXTRA_NOTICE_SCREENS ), true );
	}

	/**
	 * Whether the legacy extension was ever installed on this site.
	 *
	 * @return bool
	 */
	private function extension_was_ever_installed(): bool {
		return false !== get_option( self::OPTION_LEGACY_DB_VERSION, false );
	}

	/**
	 * Whether the Customer stock notifications feature is on.
	 *
	 * Reads the option directly rather than through `FeaturesUtil::feature_is_enabled()`,
	 * which builds translated feature definitions: this runs while the plugin loads, before
	 * `init`, so translations are not available yet. `StockNotifications::register_data_stores()`
	 * reads it the same way, for the same reason.
	 *
	 * @return bool
	 */
	private function feature_is_enabled(): bool {
		return 'yes' === get_option( StockNotifications::ENABLE_OPTION_NAME, 'no' );
	}
}
