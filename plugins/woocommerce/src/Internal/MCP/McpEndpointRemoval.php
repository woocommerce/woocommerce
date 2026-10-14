<?php
/**
 * McpEndpointRemoval class file.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\MCP;

use Automattic\WooCommerce\Proxies\LegacyProxy;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

defined( 'ABSPATH' ) || exit;

/**
 * Handles the removal of the WooCommerce MCP endpoint (woocommerce/mcp) and its REST-derived abilities.
 *
 * WooCommerce abilities remain registered through the WordPress Abilities API, and the MCP Adapter
 * plugin exposes them to MCP clients.
 *
 * @since 11.3.0
 */
class McpEndpointRemoval {

	/**
	 * WooCommerce version that removed the WooCommerce MCP endpoint.
	 *
	 * @var string
	 */
	public const REMOVED_IN = '11.3.0';

	/**
	 * Documentation for using WooCommerce abilities with MCP.
	 *
	 * @var string
	 */
	public const DOCS_URL = 'https://developer.woocommerce.com/docs/features/mcp/';

	/**
	 * Namespace of the removed WooCommerce MCP endpoint.
	 *
	 * @var string
	 */
	private const ENDPOINT_NAMESPACE = 'woocommerce';

	/**
	 * Route of the removed WooCommerce MCP endpoint.
	 *
	 * @var string
	 */
	private const ENDPOINT_ROUTE = '/mcp';

	/**
	 * Option that stored whether the removed WooCommerce MCP feature was enabled.
	 *
	 * @var string
	 */
	private const FEATURE_OPTION = 'woocommerce_feature_mcp_integration_enabled';

	/**
	 * Name of the admin notice about the removal, used for dismissal.
	 *
	 * @var string
	 */
	public const REMOVED_NOTICE = 'mcp_endpoint_removed';

	/**
	 * Plugin file of the MCP Adapter plugin from WordPress.org.
	 *
	 * @var string
	 */
	private const ADAPTER_PLUGIN_FILE = 'mcp-adapter/mcp-adapter.php';

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'rest_api_init', array( $this, 'handle_rest_api_init' ) );
		add_action( 'admin_notices', array( $this, 'handle_admin_notices' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'handle_admin_enqueue_scripts' ) );
		add_action( 'admin_init', array( $this, 'handle_admin_init' ) );
	}

	/**
	 * Handle the admin_init hook.
	 *
	 * Once the MCP Adapter plugin is active, the store has acted on the removal notice, so delete the old
	 * feature option that triggers it.
	 *
	 * @internal
	 */
	public function handle_admin_init(): void {
		if ( 'yes' === get_option( self::FEATURE_OPTION ) && $this->is_mcp_adapter_plugin_active() ) {
			delete_option( self::FEATURE_OPTION );
		}
	}

	/**
	 * Handle the admin_enqueue_scripts hook.
	 *
	 * Loads the core scripts that open the MCP Adapter plugin details in a modal, only when the notice shows the install link.
	 *
	 * @internal
	 */
	public function handle_admin_enqueue_scripts(): void {
		if ( ! $this->should_show_removed_notice() || 'install' !== $this->get_adapter_link_type() ) {
			return;
		}

		add_thickbox();
		wp_enqueue_script( 'plugin-install' );
	}

	/**
	 * Handle the admin_notices hook.
	 *
	 * Tells stores that had the WooCommerce MCP feature enabled what was removed and how to keep using WooCommerce with MCP.
	 *
	 * @internal
	 */
	public function handle_admin_notices(): void {
		if ( ! $this->should_show_removed_notice() ) {
			return;
		}

		$message = sprintf(
			'<strong>%1$s</strong> %2$s <a href="%3$s" target="_blank" rel="noopener noreferrer">%4$s</a>',
			esc_html__( 'WooCommerce MCP has been removed.', 'woocommerce' ),
			esc_html(
				sprintf(
					/* translators: %s: WooCommerce version. */
					__( 'As of WooCommerce %s, the WooCommerce MCP endpoint (/wp-json/woocommerce/mcp), its REST API key authentication, and its REST-based product and order tools are no longer available. WooCommerce abilities are still available through the WordPress Abilities API. Use the MCP Adapter plugin to connect MCP clients to them.', 'woocommerce' ),
					self::REMOVED_IN
				)
			),
			esc_url( self::DOCS_URL ),
			esc_html__( 'Learn more', 'woocommerce' )
		);

		$link_type = $this->get_adapter_link_type();
		if ( 'install' === $link_type ) {
			$message .= sprintf(
				' | <a href="%1$s" class="thickbox open-plugin-details-modal" data-title="%2$s">%3$s</a>',
				esc_url( network_admin_url( 'plugin-install.php?tab=plugin-information&plugin=mcp-adapter&TB_iframe=true&width=600&height=550' ) ),
				esc_attr__( 'MCP Adapter', 'woocommerce' ),
				esc_html__( 'Install MCP Adapter', 'woocommerce' )
			);
		} elseif ( 'activate' === $link_type ) {
			$message .= sprintf(
				' | <a href="%1$s">%2$s</a>',
				esc_url( wp_nonce_url( admin_url( 'plugins.php?action=activate&plugin=' . rawurlencode( self::ADAPTER_PLUGIN_FILE ) ), 'activate-plugin_' . self::ADAPTER_PLUGIN_FILE ) ),
				esc_html__( 'Activate MCP Adapter', 'woocommerce' )
			);
		}

		$dismiss_url = wp_nonce_url(
			add_query_arg( 'wc-hide-notice', self::REMOVED_NOTICE ),
			'woocommerce_hide_notices_nonce',
			'_wc_notice_nonce'
		);

		echo wp_kses_post(
			'<div class="notice notice-warning is-dismissible">'
			. '<a class="woocommerce-message-close notice-dismiss" style="text-decoration: none;" href="' . esc_url( $dismiss_url ) . '" aria-label="' . esc_attr__( 'Dismiss this notice', 'woocommerce' ) . '"></a>'
			. '<p>' . $message . '</p>'
			. '</div>'
		);
	}

	/**
	 * Check whether the removal notice should show to the current user.
	 *
	 * @return bool
	 */
	private function should_show_removed_notice(): bool {
		return 'yes' === get_option( self::FEATURE_OPTION )
			&& current_user_can( 'manage_woocommerce' )
			&& ! \WC_Admin_Notices::user_has_dismissed_notice( self::REMOVED_NOTICE );
	}

	/**
	 * Decide which MCP Adapter link the removal notice shows to the current user.
	 *
	 * No link is shown when an MCP adapter is already loaded, from the MCP Adapter plugin or any other plugin.
	 *
	 * @return string 'install', 'activate', or '' for no link.
	 */
	private function get_adapter_link_type(): string {
		if ( self::is_mcp_adapter_available() ) {
			return '';
		}

		$proxy = wc_get_container()->get( LegacyProxy::class );
		if ( $proxy->call_function( 'file_exists', WP_PLUGIN_DIR . '/' . self::ADAPTER_PLUGIN_FILE ) ) {
			return current_user_can( 'activate_plugins' ) ? 'activate' : '';
		}

		return current_user_can( 'install_plugins' ) ? 'install' : '';
	}

	/**
	 * Check whether the MCP Adapter plugin is active on this site or network.
	 *
	 * @return bool
	 */
	private function is_mcp_adapter_plugin_active(): bool {
		if ( ! function_exists( 'is_plugin_active' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		return (bool) wc_get_container()->get( LegacyProxy::class )->call_function( 'is_plugin_active', self::ADAPTER_PLUGIN_FILE );
	}

	/**
	 * Check whether an MCP adapter is loaded, from the MCP Adapter plugin or any other plugin.
	 *
	 * @return bool
	 */
	private static function is_mcp_adapter_available(): bool {
		return (bool) wc_get_container()->get( LegacyProxy::class )->call_function( 'class_exists', 'WP\MCP\Core\McpAdapter' );
	}

	/**
	 * Handle the rest_api_init hook.
	 *
	 * Registers the removed WooCommerce MCP endpoint so MCP clients that still use it get an explanation instead of a 404.
	 *
	 * @internal
	 */
	public function handle_rest_api_init(): void {
		register_rest_route(
			self::ENDPOINT_NAMESPACE,
			self::ENDPOINT_ROUTE,
			array(
				'methods'             => WP_REST_Server::ALLMETHODS,
				'callback'            => array( $this, 'get_endpoint_removed_response' ),
				'permission_callback' => '__return_true',
			)
		);
	}

	/**
	 * Respond to a request to the removed WooCommerce MCP endpoint.
	 *
	 * The response is a JSON-RPC error, which MCP clients surface to the user or agent, sent with HTTP status 410 Gone.
	 *
	 * @internal
	 *
	 * @param WP_REST_Request $request The REST request.
	 * @phpstan-param WP_REST_Request<array<string, mixed>> $request
	 * @return WP_REST_Response
	 */
	public function get_endpoint_removed_response( WP_REST_Request $request ): WP_REST_Response {
		$params     = $request->get_json_params();
		$request_id = is_array( $params ) && isset( $params['id'] ) && ( is_string( $params['id'] ) || is_int( $params['id'] ) ) ? $params['id'] : null;
		$adapter    = rest_url( 'mcp/mcp-adapter-default-server' );

		return new WP_REST_Response(
			array(
				'jsonrpc' => '2.0',
				'id'      => $request_id,
				'error'   => array(
					'code'    => -32000,
					'message' => sprintf(
						/* translators: 1: WooCommerce version, 2: MCP Adapter endpoint URL, 3: Documentation URL. */
						__( 'The WooCommerce MCP endpoint (woocommerce/mcp) was removed in WooCommerce %1$s. To keep using WooCommerce with MCP, install and activate the MCP Adapter plugin (https://wordpress.org/plugins/mcp-adapter/) on this site, then connect your MCP client to %2$s using a WordPress application password. Documentation: %3$s', 'woocommerce' ),
						self::REMOVED_IN,
						$adapter,
						self::DOCS_URL
					),
					'data'    => array(
						'replacement_endpoint' => $adapter,
						'documentation_url'    => self::DOCS_URL,
					),
				),
			),
			410
		);
	}

	/**
	 * Report a call to code that was removed along with the WooCommerce MCP endpoint.
	 *
	 * @param string $function_name The removed function or method that was called.
	 */
	public static function doing_it_wrong( string $function_name ): void {
		wc_doing_it_wrong(
			$function_name,
			sprintf(
				/* translators: 1: Function or method name, 2: WooCommerce version, 3: Documentation URL. */
				__( '%1$s was removed in WooCommerce %2$s along with the WooCommerce MCP endpoint and its REST-based abilities. WooCommerce abilities remain available through the WordPress Abilities API, and the MCP Adapter plugin exposes them to MCP clients. See %3$s.', 'woocommerce' ),
				$function_name,
				self::REMOVED_IN,
				self::DOCS_URL
			),
			self::REMOVED_IN
		);
	}
}
