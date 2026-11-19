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
		if ( null === $version && defined( 'WP\MCP\Core\McpAdapter::VERSION' ) ) {
			$version = (string) constant( 'WP\MCP\Core\McpAdapter::VERSION' );
		}
		if ( ! self::is_outdated( $version ) || ! current_user_can( 'manage_woocommerce' ) ) {
			return;
		}

		$message = esc_html(
			sprintf(
				/* translators: 1: Required MCP Adapter version, 2: Active MCP Adapter version. */
				__( 'WooCommerce abilities need MCP Adapter %1$s or later, but version %2$s is active. Update the MCP Adapter plugin so MCP clients can keep using WooCommerce.', 'woocommerce' ),
				self::MIN_VERSION,
				$version
			)
		);

		if ( current_user_can( 'update_plugins' ) ) {
			$message .= sprintf( ' <a href="%1$s">%2$s</a>', esc_url( self_admin_url( 'plugins.php' ) ), esc_html__( 'Go to Plugins', 'woocommerce' ) );
		}

		echo wp_kses_post( '<div class="notice notice-warning"><p>' . $message . '</p></div>' );
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
