<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\Utilities;

use Automattic\WooCommerce\Internal\RegisterHooksInterface;
use Automattic\WooCommerce\Proxies\LegacyProxy;

/**
 * Detects requests running with a mixed old-code/new-files state: an in-place update
 * has swapped the plugin files on disk while the loaded code and autoloader classmap
 * still belong to the previous version. Autoloading is unreliable in that state, so
 * deferrable late work (footer hooks, 'shutdown', REST registration) should consult
 * 'is_update_in_progress' and skip for the remainder of the request.
 *
 * Detection uses the upgrader hooks (covers the request performing the update) and an
 * admin-only disk-vs-loaded version comparison (covers still-warm workers after an
 * update in another process).
 *
 * @since 11.0.0
 */
class UpdateDetection implements RegisterHooksInterface {

	/**
	 * Transient name prefix used to throttle log entries.
	 *
	 * @var string
	 */
	private const LOG_THROTTLE_TRANSIENT_PREFIX = 'wc_update_detection_log_';

	/**
	 * Flag indicating that a WooCommerce update has started or completed within this request.
	 *
	 * @var bool
	 */
	private bool $update_started = false;

	/**
	 * Memoized result of the on-disk version comparison, null if not computed yet.
	 *
	 * @var bool|null
	 */
	private ?bool $version_on_disk_differs = null;

	/**
	 * The LegacyProxy instance to use.
	 *
	 * @var LegacyProxy
	 */
	private LegacyProxy $proxy;

	/**
	 * Initialize the class instance.
	 *
	 * @internal
	 *
	 * @param LegacyProxy $proxy The LegacyProxy instance to use.
	 */
	final public function init( LegacyProxy $proxy ): void {
		$this->proxy = $proxy;
	}

	/**
	 * Attach hooks used by the class.
	 */
	public function register(): void {
		add_filter( 'upgrader_pre_install', array( $this, 'handle_upgrader_pre_install' ), 10, 2 );
		add_action( 'upgrader_process_complete', array( $this, 'handle_upgrader_process_complete' ), 10, 2 );
	}

	/**
	 * Handler for the 'upgrader_pre_install' filter: flags the start of a WooCommerce update.
	 *
	 * @param bool|\WP_Error $response The installation response before the installation has started.
	 * @param array          $hook_extra Extra arguments passed to hooked filters.
	 * @return bool|\WP_Error The unmodified $response.
	 *
	 * @internal For exclusive usage of WooCommerce core, backwards compatibility not guaranteed.
	 */
	public function handle_upgrader_pre_install( $response, $hook_extra ) {
		if ( $this->hook_extra_references_woocommerce( (array) $hook_extra ) ) {
			$this->update_started = true;
		}
		return $response;
	}

	/**
	 * Handler for the 'upgrader_process_complete' action: flags that a WooCommerce update
	 * just swapped the files in this request.
	 *
	 * @param \WP_Upgrader $upgrader The upgrader instance that performed the update.
	 * @param array        $hook_extra Extra information about the update process.
	 *
	 * @internal For exclusive usage of WooCommerce core, backwards compatibility not guaranteed.
	 */
	public function handle_upgrader_process_complete( $upgrader, $hook_extra ): void {
		if ( $this->hook_extra_references_woocommerce( (array) $hook_extra ) ) {
			$this->update_started = true;
		}
	}

	/**
	 * Whether the current request may be running with plugin files that don't match the
	 * loaded code. Deferrable late work should skip execution when this returns true.
	 *
	 * @return bool True if an update window is active for this request.
	 */
	public function is_update_in_progress(): bool {
		return $this->update_started || $this->version_on_disk_differs();
	}

	/**
	 * Log that deferrable work was skipped or failed during an update window,
	 * throttled to one entry per context per hour.
	 *
	 * @param string          $context Identifier of the work that was skipped, e.g. 'asset_data_registry:someDataKey'.
	 * @param \Throwable|null $throwable The error that was caught, if the work failed rather than being skipped.
	 */
	public function log_suppressed_work( string $context, ?\Throwable $throwable = null ): void {
		$transient_name = self::LOG_THROTTLE_TRANSIENT_PREFIX . md5( $context );
		if ( get_transient( $transient_name ) ) {
			return;
		}
		set_transient( $transient_name, 1, HOUR_IN_SECONDS );

		if ( is_null( $throwable ) ) {
			$message = sprintf( 'Deferred work "%s" was skipped because a WooCommerce update appears to be in progress. It will resume on the next request.', $context );
		} else {
			$message = sprintf( 'Deferred work "%s" failed: %s. This is expected while a WooCommerce update is in progress; if it persists, clearing the opcode cache or restarting PHP should resolve it.', $context, $throwable->getMessage() );
		}

		$this->proxy->call_function( 'wc_get_logger' )->warning(
			$message,
			array(
				'source' => 'update-detection',
				'error'  => is_null( $throwable ) ? null : array(
					'class' => get_class( $throwable ),
					'file'  => $throwable->getFile(),
					'line'  => $throwable->getLine(),
				),
			)
		);
	}

	/**
	 * Whether the WooCommerce version on disk differs from the loaded code version.
	 * Admin-only and memoized, so it costs at most one file read per request.
	 *
	 * @return bool True if the on-disk version differs (or could not be read).
	 */
	private function version_on_disk_differs(): bool {
		if ( ! is_null( $this->version_on_disk_differs ) ) {
			return $this->version_on_disk_differs;
		}

		if ( ! is_admin() ) {
			return false;
		}

		$loaded_version = WC()->version;
		$file_data      = $this->proxy->call_function( 'get_file_data', WC_PLUGIN_FILE, array( 'Version' => 'Version' ), 'plugin' );
		$disk_version   = $file_data['Version'] ?? '';

		// An unreadable version is treated as a mismatch: it can only mean the file is mid-swap or gone.
		$this->version_on_disk_differs = '' === $disk_version || $disk_version !== $loaded_version;

		return $this->version_on_disk_differs;
	}

	/**
	 * Whether the $hook_extra information passed to an upgrader hook refers to WooCommerce.
	 *
	 * @param array $hook_extra Extra arguments passed to hooked upgrader filters/actions.
	 * @return bool True if WooCommerce is the plugin (or among the plugins) being updated.
	 */
	private function hook_extra_references_woocommerce( array $hook_extra ): bool {
		// 'type' is absent on 'upgrader_pre_install' but present on 'upgrader_process_complete'.
		if ( 'plugin' !== ( $hook_extra['type'] ?? 'plugin' ) ) {
			return false;
		}

		$woocommerce_basename = plugin_basename( WC_PLUGIN_FILE );

		if ( ( $hook_extra['plugin'] ?? '' ) === $woocommerce_basename ) {
			return true;
		}

		return in_array( $woocommerce_basename, (array) ( $hook_extra['plugins'] ?? array() ), true );
	}
}
