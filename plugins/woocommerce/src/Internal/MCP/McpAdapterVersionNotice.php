<?php
/**
 * McpAdapterVersionNotice class file.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\MCP;

defined( 'ABSPATH' ) || exit;

/**
 * Tells store managers to update the MCP Adapter plugin when its version cannot run WooCommerce abilities correctly.
 *
 * MCP Adapter 0.7.2 prepares the ability input before it checks permissions. Older versions pass raw input
 * to the permission callbacks of WooCommerce abilities.
 *
 * Remove when MCP Adapter requires WordPress 7.2.
 *
 * @since 11.4.0
 */
class McpAdapterVersionNotice {

	/**
	 * The lowest MCP Adapter version that WooCommerce abilities support.
	 *
	 * @var string
	 */
	public const MIN_VERSION = '0.7.2';

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'admin_notices', array( $this, 'handle_admin_notices' ) );
	}

	/**
	 * Handle the admin_notices hook.
	 *
	 * @internal
	 */
	public function handle_admin_notices(): void {
		$version = defined( 'WP_MCP_VERSION' ) ? (string) WP_MCP_VERSION : null;
		$bundled = null === $version;
		// A plugin that loads a bundled adapter only on rest_api_init gets no notice, because admin pages never load the class.
		if ( $bundled && class_exists( 'WP\MCP\Core\McpAdapter', false ) ) {
			$version = (string) constant( 'WP\MCP\Core\McpAdapter::VERSION' );
		}
		if ( ! self::is_outdated( $version ) || ! current_user_can( 'manage_woocommerce' ) ) {
			return;
		}

		$bundled_by = $bundled ? self::get_bundling_plugin_name() : null;
		if ( null !== $bundled_by ) {
			$message = sprintf(
				/* translators: 1: Required MCP Adapter version, 2: Plugin name, 3: Active MCP Adapter version. */
				__( 'WooCommerce abilities need MCP Adapter %1$s or later, but %2$s loads version %3$s. Update %2$s so MCP clients can keep using WooCommerce.', 'woocommerce' ),
				self::MIN_VERSION,
				$bundled_by,
				$version
			);
		} elseif ( $bundled ) {
			$message = sprintf(
				/* translators: 1: Required MCP Adapter version, 2: Active MCP Adapter version. */
				__( 'WooCommerce abilities need MCP Adapter %1$s or later, but another plugin loads version %2$s. Update the plugin that includes MCP Adapter so MCP clients can keep using WooCommerce.', 'woocommerce' ),
				self::MIN_VERSION,
				$version
			);
		} else {
			$message = sprintf(
				/* translators: 1: Required MCP Adapter version, 2: Active MCP Adapter version. */
				__( 'WooCommerce abilities need MCP Adapter %1$s or later, but version %2$s is active. Update the MCP Adapter plugin so MCP clients can keep using WooCommerce.', 'woocommerce' ),
				self::MIN_VERSION,
				$version
			);
		}
		$message = esc_html( $message );

		if ( current_user_can( 'update_plugins' ) ) {
			$message .= sprintf( ' <a href="%1$s">%2$s</a>', esc_url( self_admin_url( 'plugins.php' ) ), esc_html__( 'Go to Plugins', 'woocommerce' ) );
		}

		echo wp_kses_post( '<div class="notice notice-warning"><p>' . $message . '</p></div>' );
	}

	/**
	 * Get the name of the plugin that bundles the loaded MCP Adapter class.
	 *
	 * @return string|null The plugin name, or null when the class does not load from a regular plugin.
	 */
	private static function get_bundling_plugin_name(): ?string {
		// @phpstan-ignore-next-line class.notFound -- MCP Adapter is an optional plugin, and only a loaded class reaches this method.
		$file        = wp_normalize_path( (string) ( new \ReflectionClass( \WP\MCP\Core\McpAdapter::class ) )->getFileName() );
		$plugins_dir = trailingslashit( wp_normalize_path( WP_PLUGIN_DIR ) );
		if ( ! str_starts_with( $file, $plugins_dir ) || ! function_exists( 'get_plugins' ) ) {
			return null;
		}

		$slug = strtok( substr( $file, strlen( $plugins_dir ) ), '/' );

		return self::get_root_plugin_name( get_plugins( '/' . $slug ) );
	}

	/**
	 * Get the name of the only plugin whose main file sits at the root of a plugin folder.
	 *
	 * The get_plugins() result also holds plugin headers from subfolders, so those entries are skipped.
	 *
	 * @internal
	 *
	 * @param array $plugins The result of get_plugins() for one plugin folder, keyed by file path relative to that folder.
	 * @return string|null The plugin name, or null when the folder does not have exactly one root plugin file.
	 */
	public static function get_root_plugin_name( array $plugins ): ?string {
		$root = array_filter(
			$plugins,
			static fn( $path ) => false === strpos( (string) $path, '/' ),
			ARRAY_FILTER_USE_KEY
		);
		if ( 1 !== count( $root ) ) {
			return null;
		}
		$plugin = reset( $root );

		return empty( $plugin['Name'] ) ? null : (string) $plugin['Name'];
	}

	/**
	 * Check whether an MCP Adapter version is older than the version WooCommerce abilities support.
	 *
	 * @param string|null $version The active MCP Adapter version, or null when no adapter is loaded.
	 * @return bool
	 */
	public static function is_outdated( ?string $version ): bool {
		return null !== $version && version_compare( $version, self::MIN_VERSION, '<' );
	}
}
