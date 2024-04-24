<?php
/**
 * A class of utilities for dealing with plugins.
 */

namespace Automattic\WooCommerce\Utilities;

use Automattic\WooCommerce\Internal\Traits\AccessiblePrivateMethods;
use Automattic\WooCommerce\Internal\Utilities\PluginInstaller;
use Automattic\WooCommerce\Proxies\LegacyProxy;

/**
 * A class of utilities for dealing with plugins.
 */
class PluginUtil {

	use AccessiblePrivateMethods;

	/**
	 * The LegacyProxy instance to use.
	 *
	 * @var LegacyProxy
	 */
	private $proxy;

	/**
	 * The cached list of WooCommerce aware plugin ids.
	 *
	 * @var null|array
	 */
	private $woocommerce_aware_plugins = null;

	/**
	 * The cached list of enabled WooCommerce aware plugin ids.
	 *
	 * @var null|array
	 */
	private $woocommerce_aware_active_plugins = null;

	/**
	 * List of plugins excluded from feature compatibility warnings in UI.
	 *
	 * @var string[]
	 */
	private $plugins_excluded_from_compatibility_ui;

	/**
	 * Creates a new instance of the class.
	 */
	public function __construct() {
		self::add_action( 'activated_plugin', array( $this, 'handle_plugin_de_activation' ), 10, 0 );
		self::add_action( 'deactivated_plugin', array( $this, 'handle_plugin_de_activation' ), 10, 0 );

		$this->plugins_excluded_from_compatibility_ui = array( 'woocommerce-legacy-rest-api/woocommerce-legacy-rest-api.php' );
	}

	/**
	 * Initialize the class instance.
	 *
	 * @internal
	 *
	 * @param LegacyProxy $proxy The instance of LegacyProxy to use.
	 */
	final public function init( LegacyProxy $proxy ) {
		$this->proxy = $proxy;
		require_once ABSPATH . WPINC . '/plugin.php';
	}

	/**
	 * Get a list with the names of the WordPress plugins that are WooCommerce aware
	 * (they have a "WC tested up to" header).
	 *
	 * @param bool $active_only True to return only active plugins, false to return all the active plugins.
	 * @return string[] A list of plugin ids (path/file.php).
	 */
	public function get_woocommerce_aware_plugins( bool $active_only = false ): array {
		if ( is_null( $this->woocommerce_aware_plugins ) ) {
			// In case `get_plugins` was called much earlier in the request (before our headers could be injected), we
			// invalidate the plugin cache list.
			wp_cache_delete( 'plugins', 'plugins' );
			$all_plugins = $this->proxy->call_function( 'get_plugins' );

			$this->woocommerce_aware_plugins =
				array_keys(
					array_filter(
						$all_plugins,
						array( $this, 'is_woocommerce_aware_plugin' )
					)
				);

			$this->woocommerce_aware_active_plugins =
				array_values(
					array_filter(
						$this->woocommerce_aware_plugins,
						function ( $plugin_name ) {
							return $this->proxy->call_function( 'is_plugin_active', $plugin_name );
						}
					)
				);
		}

		return $active_only ? $this->woocommerce_aware_active_plugins : $this->woocommerce_aware_plugins;
	}

	/**
	 * Get the printable name of a plugin.
	 *
	 * @param string $plugin_id Plugin id (path/file.php).
	 * @return string Printable plugin name, or the plugin id itself if printable name is not available.
	 */
	public function get_plugin_name( string $plugin_id ): string {
		$plugin_data = $this->proxy->call_function( 'get_plugin_data', WP_PLUGIN_DIR . DIRECTORY_SEPARATOR . $plugin_id );
		return $plugin_data['Name'] ?? $plugin_id;
	}

	/**
	 * Check if a plugin is WooCommerce aware.
	 *
	 * @param string|array $plugin_file_or_data Plugin id (path/file.php) or plugin data (as returned by get_plugins).
	 * @return bool True if the plugin exists and is WooCommerce aware.
	 * @throws \Exception The input is neither a string nor an array.
	 */
	public function is_woocommerce_aware_plugin( $plugin_file_or_data ): bool {
		if ( is_string( $plugin_file_or_data ) ) {
			return in_array( $plugin_file_or_data, $this->get_woocommerce_aware_plugins(), true );
		} elseif ( is_array( $plugin_file_or_data ) ) {
			return '' !== ( $plugin_file_or_data['WC tested up to'] ?? '' );
		} else {
			throw new \Exception( 'is_woocommerce_aware_plugin requires a plugin name or an array of plugin data as input' );
		}
	}

	/**
	 * Match plugin identifier passed as a parameter with the output from `get_plugins()`.
	 *
	 * @param string $plugin_file Plugin identifier, either 'my-plugin/my-plugin.php', or output from __FILE__.
	 *
	 * @return string|false Key from the array returned by `get_plugins` if matched. False if no match.
	 */
	public function get_wp_plugin_id( $plugin_file ) {
		$wp_plugins = array_keys( $this->proxy->call_function( 'get_plugins' ) );

		// Try to match plugin_basename().
		$plugin_basename = $this->proxy->call_function( 'plugin_basename', $plugin_file );
		if ( in_array( $plugin_basename, $wp_plugins, true ) ) {
			return $plugin_basename;
		}

		// Try to match by the my-file/my-file.php (dir + file name), then by my-file.php (file name only).
		$plugin_file     = str_replace( array( '\\', '/' ), DIRECTORY_SEPARATOR, $plugin_file );
		$file_name_parts = explode( DIRECTORY_SEPARATOR, $plugin_file );
		$file_name       = array_pop( $file_name_parts );
		$directory_name  = array_pop( $file_name_parts );
		$full_matches    = array();
		$partial_matches = array();
		foreach ( $wp_plugins as $wp_plugin ) {
			if ( false !== strpos( $wp_plugin, $directory_name . DIRECTORY_SEPARATOR . $file_name ) ) {
				$full_matches[] = $wp_plugin;
			}

			if ( false !== strpos( $wp_plugin, $file_name ) ) {
				$partial_matches[] = $wp_plugin;
			}
		}

		if ( 1 === count( $full_matches ) ) {
			return $full_matches[0];
		}

		if ( 1 === count( $partial_matches ) ) {
			return $partial_matches[0];
		}

		return false;
	}

	/**
	 * Handle plugin activation and deactivation by clearing the WooCommerce aware plugin ids cache.
	 */
	private function handle_plugin_de_activation(): void {
		$this->woocommerce_aware_plugins        = null;
		$this->woocommerce_aware_active_plugins = null;
	}

	/**
	 * Utility method to generate warning string for incompatible features based on active plugins.
	 *
	 * Additionally, this method will manually print a warning message on the HPOS feature if both
	 * the Legacy REST API and HPOS are active.
	 *
	 * @param string $feature_id Feature id.
	 * @param array  $plugin_feature_info Array of plugin feature info. See FeaturesControllers->get_compatible_plugins_for_feature() for details.
	 *
	 * @return string Warning string.
	 */
	public function generate_incompatible_plugin_feature_warning( string $feature_id, array $plugin_feature_info ): string {
		$feature_warning    = '';
		$incompatibles      = array_merge( $plugin_feature_info['incompatible'], $plugin_feature_info['uncertain'] );
		$incompatibles      = array_filter( $incompatibles, 'is_plugin_active' );
		$incompatibles      = array_values( array_diff( $incompatibles, $this->get_plugins_excluded_from_compatibility_ui() ) );
		$incompatible_count = count( $incompatibles );

		$feature_warnings = array();
		if ( 'custom_order_tables' === $feature_id && 'yes' === get_option( 'woocommerce_api_enabled' ) ) {
			if ( is_plugin_active( 'woocommerce-legacy-rest-api/woocommerce-legacy-rest-api.php' ) ) {
				$legacy_api_and_hpos_incompatibility_warning_text =
					sprintf(
						// translators: %s is a URL.
						__( '⚠ <b><a target="_blank" href="%s">The Legacy REST API plugin</a> is installed and active on this site.</b> Please be aware that the WooCommerce Legacy REST API is <b>not</b> compatible with HPOS.', 'woocommerce' ),
						'https://wordpress.org/plugins/woocommerce-legacy-rest-api/'
					);
			} else {
				$legacy_api_and_hpos_incompatibility_warning_text =
				sprintf(
					// translators: %s is a URL.
					__( '⚠ <b><a target="_blank" href="%s">The Legacy REST API</a> is active on this site.</b> Please be aware that the WooCommerce Legacy REST API is <b>not</b> compatible with HPOS.', 'woocommerce' ),
					admin_url( 'admin.php?page=wc-settings&tab=advanced&section=legacy_api' )
				);
			}

			/**
			 * Filter to modify the warning text that appears in the HPOS section of the features settings page
			 * when both the Legacy REST API is active (via WooCommerce core or via the Legacy REST API plugin)
			 * and the orders table is in use as the primary data store for orders.
			 *
			 * @param string $legacy_api_and_hpos_incompatibility_warning_text Original warning text.
			 * @returns string|null Actual warning text to use, or null to suppress the warning.
			 *
			 * @since 8.9.0
			 */
			$legacy_api_and_hpos_incompatibility_warning_text = apply_filters( 'woocommerce_legacy_api_and_hpos_incompatibility_warning_text', $legacy_api_and_hpos_incompatibility_warning_text );

			if ( ! is_null( $legacy_api_and_hpos_incompatibility_warning_text ) ) {
				$feature_warnings[] = "\n" . $legacy_api_and_hpos_incompatibility_warning_text;
			}
		}

		if ( $incompatible_count > 0 ) {
			if ( 1 === $incompatible_count ) {
				/* translators: %s = printable plugin name */
				$feature_warnings[] = sprintf( __( '⚠ 1 Incompatible plugin detected (%s).', 'woocommerce' ), $this->get_plugin_name( $incompatibles[0] ) );
			} elseif ( 2 === $incompatible_count ) {
				$feature_warnings[] = sprintf(
					/* translators: %1\$s, %2\$s = printable plugin names */
					__( '⚠ 2 Incompatible plugins detected (%1$s and %2$s).', 'woocommerce' ),
					$this->get_plugin_name( $incompatibles[0] ),
					$this->get_plugin_name( $incompatibles[1] )
				);
			} else {
				$feature_warnings[] = sprintf(
					/* translators: %1\$s, %2\$s = printable plugin names, %3\$d = plugins count */
					_n(
						'⚠ Incompatible plugins detected (%1$s, %2$s and %3$d other).',
						'⚠ Incompatible plugins detected (%1$s and %2$s plugins and %3$d others).',
						$incompatible_count - 2,
						'woocommerce'
					),
					$this->get_plugin_name( $incompatibles[0] ),
					$this->get_plugin_name( $incompatibles[1] ),
					$incompatible_count - 2
				);
			}

			$incompatible_plugins_url = add_query_arg(
				array(
					'plugin_status' => 'incompatible_with_feature',
					'feature_id'    => $feature_id,
				),
				admin_url( 'plugins.php' )
			);
			$feature_warnings[]       = sprintf(
				/* translators: %1$s opening link tag %2$s closing link tag. */
				__( '%1$sView and manage%2$s', 'woocommerce' ),
				'<a href="' . esc_url( $incompatible_plugins_url ) . '">',
				'</a>'
			);
		}

		return str_replace( "\n", '<br>', implode( "\n", $feature_warnings ) );
	}

	/**
	 * Get the names of the plugins that are excluded from the feature compatibility UI.
	 * These plugins won't be considered as incompatible with any existing feature for the purposes
	 * of displaying compatibility warning in UI, even if they declare incompatibilities explicitly.
	 *
	 * @return string[] Plugin names relative to the root plugins directory.
	 */
	public function get_plugins_excluded_from_compatibility_ui() {
		return $this->plugins_excluded_from_compatibility_ui;
	}
}
