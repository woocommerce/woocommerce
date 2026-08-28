<?php
/**
 * MigrationController class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\StockNotifications\Migration;

use Automattic\WooCommerce\Internal\RegisterHooksInterface;
use Automattic\WooCommerce\Internal\StockNotifications\Migration\Compat\LegacyUnsubscribeShim;
use Automattic\WooCommerce\Internal\StockNotifications\Migration\Runners\ToolsRegistrar;
use Automattic\WooCommerce\Internal\StockNotifications\StockNotifications;

defined( 'ABSPATH' ) || exit;

/**
 * Gates registration of the BIS-to-Core stock notifications migration and wires it up.
 *
 * This is the only class in `Migration\` autoloaded on a normal request. Every decision below
 * reads an option that is already autoloaded and in memory, so registration itself never runs a
 * query. Everything else - the Tools entry, the CLI commands, the unsubscribe shim - is resolved
 * from the container lazily, inside the callback that actually needs it, so nothing beyond this
 * class is autoloaded until one of those callbacks fires.
 *
 * Registration needs both the Customer stock notifications feature to be on and the legacy
 * extension to have been installed here. With the feature off there is nothing to migrate into:
 * the `stock_notification` data store is not registered, so the unsubscribe shim could not load
 * a notification, and Core sends nothing, so the double-send notice would have nothing to warn
 * about. The CLI command is deliberately not gated this way - it registers from `WC_CLI` and
 * reports the disabled feature as an error the merchant can act on, which is more use than a
 * command that silently does not exist.
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
	 * legacy unsubscribe token. Answers "are there live legacy links", and gates only the
	 * unsubscribe shim's registration - it is not a general "migrated rows exist" signal, since a
	 * store whose legacy rows all lack `_hash_key`/`_hash_iv` never sets it. See
	 * OPTION_HAS_MIGRATED_ROWS for that broader question.
	 */
	private const OPTION_HAS_LEGACY_LINKS = Constants::HAS_LEGACY_LINKS_OPTION;

	/**
	 * Autoloaded flag the notifications migrator sets the first time it migrates any row,
	 * inserted or adopted, regardless of whether it carries a legacy unsubscribe token. Answers
	 * "have any rows been migrated" for the double-send admin notice below.
	 */
	private const OPTION_HAS_MIGRATED_ROWS = Constants::HAS_MIGRATED_ROWS_OPTION;

	/**
	 * Basename of the legacy Back In Stock Notifications extension, as WordPress lists it in
	 * the active plugins option.
	 */
	private const LEGACY_PLUGIN_BASENAME = 'woocommerce-back-in-stock-notifications/woocommerce-back-in-stock-notifications.php';

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
			wc_get_container()->get( LegacyUnsubscribeShim::class );
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
	 * regardless of whether it carries a legacy unsubscribe token. `wc_bis_migration_has_legacy_links`
	 * would under-report this: a store whose legacy rows all lack `_hash_key`/`_hash_iv` never
	 * sets it, even with migrated rows present. Never auto-deactivates the extension; this is a
	 * notice only.
	 *
	 * @internal
	 */
	public function maybe_render_double_send_notice(): void {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			return;
		}

		if ( ! get_option( self::OPTION_HAS_MIGRATED_ROWS ) ) {
			return;
		}

		if ( ! function_exists( 'is_plugin_active' ) || ! is_plugin_active( self::LEGACY_PLUGIN_BASENAME ) ) {
			return;
		}

		wp_admin_notice(
			__( 'WooCommerce Back In Stock Notifications is still active and some of its subscribers have already been migrated to the built-in Customer stock notifications feature. While both are active, a restock can send duplicate emails to the same customer. Finish the migration, then deactivate the legacy plugin.', 'woocommerce' ),
			array(
				'id'          => 'wc-bis-migration-double-send',
				'type'        => 'warning',
				'dismissible' => false,
			)
		);
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
