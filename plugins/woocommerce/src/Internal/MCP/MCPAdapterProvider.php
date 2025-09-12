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
		// Hook into WordPress plugins loaded to ensure proper timing
		add_action( 'init', array( $this, 'maybe_initialize' ) );
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

		// Create MCP server
		$adapter->create_server(
			'woocommerce-mcp',                                           // Server ID
			'woocommerce',                                              // REST namespace
			'mcp',                                                      // REST route
			'WooCommerce MCP Server',                                   // Name
			'AI-accessible WooCommerce operations via MCP',            // Description
			'1.0.0',                                                    // Version
			array( WooCommerceRestTransport::class ),                       // Transport methods
			\WP\MCP\Infrastructure\ErrorHandling\ErrorLogMcpErrorHandler::class, // Error handler
			\WP\MCP\Infrastructure\Observability\NullMcpObservabilityHandler::class, // Observability handler
			$abilities_ids,                                             // Abilities from registry
		);
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