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
		// Check if MCP integration feature is enabled
		if ( ! FeaturesUtil::feature_is_enabled( 'mcp_integration' ) ) {
			return;
		}

		// Prevent double initialization
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
		// Check if MCP adapter class exists (should be autoloaded by WooCommerce's composer)
		if ( ! class_exists( 'WP\MCP\Core\McpAdapter' ) ) {
			return;
		}

		// Initialize the MCP adapter instance - this triggers the rest_api_init hook registration
		\WP\MCP\Core\McpAdapter::instance();
	}

	/**
	 * Register WordPress hooks for MCP adapter.
	 */
	private function register_hooks(): void {
		// Initialize MCP server when MCP adapter is ready
		add_action( 'mcp_adapter_init', array( $this, 'initialize_mcp_server' ) );
	}

	/**
	 * Initialize MCP server.
	 *
	 * @param object $adapter MCP adapter instance.
	 */
	public function initialize_mcp_server( $adapter ): void {
		// Get abilities from the registry
		$abilities_registry = wc_get_container()->get( AbilitiesRegistry::class );
		$abilities_ids = $abilities_registry->getAbilitiesIDs();

		// Bail if no abilities are available
		if ( empty( $abilities_ids ) ) {
			return;
		}

		// Temporarily disable MCP validation during server creation
		// Workaround for validator bug with union types (e.g., ["integer", "null"])
		// TODO: Remove once mcp-adapter validator bug is fixed
		add_filter( 'mcp_validation_enabled', '__return_false', 999 );

		try {
			// Create MCP server
			$adapter->create_server(
				'woocommerce-mcp',
				'woocommerce',
				'mcp',
				'WooCommerce MCP Server',
				'AI-accessible WooCommerce operations via MCP',
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
			// Re-enable MCP validation immediately after server creation
			remove_filter( 'mcp_validation_enabled', '__return_false', 999 );
		}
	}

	/**
	 * Check if MCP adapter is initialized.
	 *
	 * @return bool Whether MCP adapter is initialized.
	 */
	public function is_initialized(): bool {
		return $this->initialized;
	}
}