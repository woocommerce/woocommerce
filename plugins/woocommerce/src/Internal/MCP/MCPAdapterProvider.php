<?php
/**
 * MCP Adapter Provider class file.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\MCP;

use Automattic\WooCommerce\Utilities\FeaturesUtil;
use Automattic\WooCommerce\Internal\Abilities\AbilitiesRegistry;
use Automattic\WooCommerce\Internal\MCP\Transport\WooCommerceRestTransport;

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
		// Check if MCP adapter class exists (should be autoloaded by WooCommerce's composer).
		if ( ! class_exists( 'WP\MCP\Core\McpAdapter' ) ) {
			if ( function_exists( 'wc_get_logger' ) ) {
				wc_get_logger()->warning(
					'MCP adapter class not found. Skipping MCP initialization.',
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

		/*
		 * Temporarily disable MCP validation during server creation.
		 * Workaround for validator bug with union types (e.g., ["integer", "null"]).
		 * This will be removed once the mcp-adapter validator bug is fixed.
		 *
		 * @see https://github.com/WordPress/mcp-adapter/issues/47
		 */
		add_filter( 'mcp_validation_enabled', array( __CLASS__, 'disable_mcp_validation' ), 999 );

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
		} finally {
			// Re-enable MCP validation immediately after server creation.
			remove_filter( 'mcp_validation_enabled', array( __CLASS__, 'disable_mcp_validation' ), 999 );
		}
	}

	/**
	 * Get WooCommerce abilities for MCP server.
	 *
	 * Filters abilities to include only those with 'woocommerce/' namespace by default,
	 * with a filter to allow inclusion of abilities from other namespaces.
	 *
	 * @return array Array of ability IDs for MCP server.
	 */
	private function get_woocommerce_mcp_abilities(): array {
		// Get all abilities from the registry.
		$abilities_registry = wc_get_container()->get( AbilitiesRegistry::class );
		$all_abilities_ids  = $abilities_registry->get_abilities_ids();

		// Filter abilities based on namespace and custom filter.
		$mcp_abilities = array_filter(
			$all_abilities_ids,
			function ( $ability_id ) {
				// Include WooCommerce abilities by default.
				$include = str_starts_with( $ability_id, 'woocommerce/' );

				// Allow filter to override inclusion decision.
				/**
				 * Filter to override MCP ability inclusion decision.
				 *
				 * @since 10.3.0
				 * @param bool   $include    Whether to include the ability.
				 * @param string $ability_id The ability ID.
				 */
				return apply_filters( 'woocommerce_mcp_include_ability', $include, $ability_id );
			}
		);

		// Re-index array.
		return array_values( $mcp_abilities );
	}

	/**
	 * Temporarily disable MCP validation.
	 *
	 * Used as a callback for the mcp_validation_enabled filter to work around
	 * validator bugs with union types.
	 *
	 * @return bool Always returns false to disable validation.
	 */
	public static function disable_mcp_validation(): bool {
		return false;
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
		if ( ! defined( 'REST_REQUEST' ) || ! REST_REQUEST ) {
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
