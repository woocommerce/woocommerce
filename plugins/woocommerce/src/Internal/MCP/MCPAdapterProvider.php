<?php
/**
 * MCP Adapter Provider class file.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\MCP;

use Automattic\WooCommerce\Utilities\FeaturesUtil;
use Automattic\WooCommerce\Internal\Abilities\AbilitiesRegistry;
use Automattic\WooCommerce\Internal\Abilities\REST\RestAbilityFactory;
use Automattic\WooCommerce\Internal\MCP\Transport\WooCommerceRestTransport;
use Automattic\WooCommerce\Proxies\LegacyProxy;

defined( 'ABSPATH' ) || exit;

/**
 * MCP Adapter Provider class for WooCommerce.
 *
 * Manages MCP (Model Context Protocol) adapter initialization and server configuration.
 * Abilities should be registered separately using the WordPress Abilities API.
 */
class MCPAdapterProvider {

	/**
	 * MCP server namespace.
	 *
	 * @var string
	 */
	const MCP_NAMESPACE = 'woocommerce';

	/**
	 * MCP server route.
	 *
	 * @var string
	 */
	const MCP_ROUTE = 'mcp';

	/**
	 * Name of the admin notice shown when the MCP Adapter plugin is missing.
	 *
	 * @var string
	 */
	const ADAPTER_MISSING_NOTICE = 'mcp_adapter_missing';

	/**
	 * Whether MCP adapter is initialized.
	 *
	 * @var bool
	 */
	private bool $initialized = false;

	/**
	 * Constructor.
	 */
	public function __construct() {
		/*
		 * Hook into rest_api_init with priority 10 to initialize only on REST API requests.
		 * MCP adapter registers on rest_api_init with priority 20000, so we initialize earlier.
		 * This prevents unnecessary MCP initialization on favicon, cron, or admin requests.
		 */
		add_action( 'rest_api_init', array( $this, 'maybe_initialize' ), 10 );
		add_action( 'admin_notices', array( $this, 'maybe_show_adapter_missing_notice' ) );
	}

	/**
	 * Show an admin notice when WooCommerce MCP is enabled but the MCP Adapter plugin is not available.
	 *
	 * @internal
	 */
	public function maybe_show_adapter_missing_notice(): void {
		if (
			! current_user_can( 'manage_woocommerce' )
			|| ! FeaturesUtil::feature_is_enabled( 'mcp_integration' )
			|| self::is_mcp_adapter_available()
			|| \WC_Admin_Notices::user_has_dismissed_notice( self::ADAPTER_MISSING_NOTICE )
		) {
			return;
		}

		$message = esc_html__( 'WooCommerce MCP is enabled, but the MCP Adapter plugin is not active. MCP clients can\'t connect to your store until you install and activate it.', 'woocommerce' );

		if ( current_user_can( 'install_plugins' ) ) {
			$message .= sprintf(
				' <a href="%s">%s</a>',
				esc_url( self::get_mcp_adapter_install_url() ),
				esc_html__( 'Install MCP Adapter', 'woocommerce' )
			);
		}

		$dismiss_url = wp_nonce_url(
			add_query_arg( 'wc-hide-notice', self::ADAPTER_MISSING_NOTICE ),
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
	 * Check feature flag and initialize MCP adapter if enabled.
	 */
	public function maybe_initialize(): void {
		// Check if MCP integration feature is enabled.
		if ( ! FeaturesUtil::feature_is_enabled( 'mcp_integration' ) ) {
			return;
		}

		// Prevent double initialization.
		if ( $this->initialized ) {
			return;
		}

		$this->initialize_mcp_adapter();
		$this->register_hooks();
		$this->initialized = true;
	}

	/**
	 * Initialize the MCP adapter.
	 */
	private function initialize_mcp_adapter(): void {
		// The MCP adapter is provided by the MCP Adapter plugin (https://wordpress.org/plugins/mcp-adapter/).
		if ( ! self::is_mcp_adapter_available() ) {
			// Only log for MCP endpoint requests to avoid a log entry on every REST request.
			if ( self::is_mcp_request() && function_exists( 'wc_get_logger' ) ) {
				wc_get_logger()->warning(
					'MCP adapter not found. Install and activate the MCP Adapter plugin to use WooCommerce MCP.',
					array( 'source' => 'woocommerce-mcp' )
				);
			}
			return;
		}

		// Initialize the MCP adapter instance - this triggers the rest_api_init hook registration.
		\WP\MCP\Core\McpAdapter::instance();
	}

	/**
	 * Register WordPress hooks for MCP adapter.
	 */
	private function register_hooks(): void {
		// Initialize MCP server when MCP adapter is ready.
		add_action( 'mcp_adapter_init', array( $this, 'initialize_mcp_server' ) );
	}

	/**
	 * Initialize MCP server.
	 *
	 * @param object $adapter MCP adapter instance.
	 */
	public function initialize_mcp_server( $adapter ): void {
		// Get filtered abilities for MCP server.
		$abilities_ids = $this->get_woocommerce_mcp_abilities();

		// Bail if no abilities are available.
		if ( empty( $abilities_ids ) ) {
			return;
		}

		try {
			// Create MCP server.
			$adapter->create_server(
				'woocommerce-mcp',
				self::MCP_NAMESPACE,
				self::MCP_ROUTE,
				__( 'WooCommerce MCP Server', 'woocommerce' ),
				__( 'AI-accessible WooCommerce operations via MCP', 'woocommerce' ),
				'1.0.0',
				array( WooCommerceRestTransport::class ),
				\WP\MCP\Infrastructure\ErrorHandling\ErrorLogMcpErrorHandler::class,
				\WP\MCP\Infrastructure\Observability\NullMcpObservabilityHandler::class,
				$abilities_ids,
			);
		} catch ( \Throwable $e ) {
			if ( function_exists( 'wc_get_logger' ) ) {
				wc_get_logger()->error(
					'MCP server initialization failed: ' . $e->getMessage(),
					array( 'source' => 'woocommerce-mcp' )
				);
			}
		}
	}

	/**
	 * Get WooCommerce abilities for MCP server.
	 *
	 * Filters abilities to include only those explicitly exposed to the deprecated
	 * WooCommerce MCP endpoint, with a filter to override inclusion decisions.
	 *
	 * @return array Array of ability IDs for MCP server.
	 */
	private function get_woocommerce_mcp_abilities(): array {
		// Get all abilities from the registry.
		$abilities_registry = wc_get_container()->get( AbilitiesRegistry::class );
		$all_abilities_ids  = $abilities_registry->get_abilities_ids();

		// Filter abilities based on deprecated endpoint exposure metadata and custom filter.
		$mcp_abilities = array_filter(
			$all_abilities_ids,
			static function ( $ability_id ) {
				$include = self::should_include_ability_by_default( $ability_id );

				// Allow filter to override inclusion decision.
				/**
				 * Filter to override MCP ability inclusion decision.
				 *
				 * @since 10.3.0
				 *
				 * @param bool   $include    Whether to include the ability by default. True when the ability has
				 *                            `expose_in_deprecated_woocommerce_mcp => true` in its metadata
				 *                            (set automatically on REST-derived abilities by RestAbilityFactory).
				 *                            Migration note: this value no longer represents whether the ability uses
				 *                            the `woocommerce/` namespace.
				 *                            Return unchanged to keep the default, or return true/false to override.
				 * @param string $ability_id The ability ID.
				 */
				return apply_filters( 'woocommerce_mcp_include_ability', $include, $ability_id );
			}
		);

		// Re-index array.
		return array_values( $mcp_abilities );
	}

	/**
	 * Check if an ability should be included in the deprecated WooCommerce MCP endpoint.
	 *
	 * REST-derived abilities can opt in to the deprecated WooCommerce MCP endpoint.
	 * Require explicit metadata so semantic/domain abilities can use WooCommerce
	 * namespaces without expanding the deprecated MCP tool list.
	 * This intentionally allows abilities from any namespace to opt in to the
	 * deprecated endpoint while keeping namespace and transport exposure separate.
	 *
	 * @param string $ability_id Ability ID.
	 * @return bool Whether to include the ability by default.
	 */
	private static function should_include_ability_by_default( string $ability_id ): bool {
		// Keep the pre-check to avoid triggering _doing_it_wrong() for stale or mocked registry IDs.
		if ( function_exists( 'wp_get_ability' ) && function_exists( 'wp_has_ability' ) && wp_has_ability( $ability_id ) ) {
			$ability = wp_get_ability( $ability_id );
			if ( $ability ) {
				// Strict boolean required: truthy values like 1 or "true" are intentionally excluded.
				return true === $ability->get_meta_item(
					RestAbilityFactory::EXPOSE_IN_DEPRECATED_MCP_META_KEY,
					false
				);
			}
		}

		return false;
	}

	/**
	 * Check if the MCP adapter (provided by the MCP Adapter plugin) is available.
	 *
	 * @return bool Whether the MCP adapter is available.
	 *
	 * @since 11.3.0
	 */
	public static function is_mcp_adapter_available(): bool {
		return (bool) wc_get_container()->get( LegacyProxy::class )->call_function( 'class_exists', 'WP\MCP\Core\McpAdapter' );
	}

	/**
	 * Get the admin URL for installing the MCP Adapter plugin.
	 *
	 * @return string The plugin installer URL.
	 *
	 * @since 11.3.0
	 */
	public static function get_mcp_adapter_install_url(): string {
		return admin_url( 'plugin-install.php?tab=search&type=term&s=mcp-adapter' );
	}

	/**
	 * Check if MCP adapter is initialized.
	 *
	 * @return bool Whether MCP adapter is initialized.
	 */
	public function is_initialized(): bool {
		return $this->initialized;
	}

	/**
	 * Check if the current request is for the MCP endpoint.
	 *
	 * @return bool True if this is an MCP endpoint request.
	 */
	public static function is_mcp_request(): bool {
		// Check if this is a REST request.
		if ( ! wp_is_serving_rest_request() ) {
			return false;
		}

		// Get the request URI.
		$request_uri = isset( $_SERVER['REQUEST_URI'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '';

		// Build the MCP endpoint path dynamically from constants.
		$mcp_endpoint = '/' . self::MCP_NAMESPACE . '/' . self::MCP_ROUTE;

		// Check if the request is for the MCP endpoint.
		return false !== strpos( $request_uri, $mcp_endpoint );
	}
}
