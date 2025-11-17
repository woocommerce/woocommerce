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
		 * Initialize MCP adapter in both WP-CLI and REST API contexts.
		 *
		 * For WP-CLI: Hook into 'init' at priority 5 to ensure initialization
		 * happens before the MCP adapter registers its CLI commands (priority 20).
		 * This enables commands like 'wp mcp-adapter serve' to work properly.
		 *
		 * For REST: Hook into 'rest_api_init' with priority 10 to initialize only
		 * on REST API requests. MCP adapter registers on rest_api_init with priority 20000,
		 * so we initialize earlier. This prevents unnecessary MCP initialization on
		 * favicon, cron, or admin requests.
		 */
		if ( defined( 'WP_CLI' ) && WP_CLI ) {
			add_action( 'init', array( $this, 'maybe_initialize' ), 5 );
		} else {
			add_action( 'rest_api_init', array( $this, 'maybe_initialize' ), 10 );
		}
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
		return self::is_mcp_cli_request() || self::is_mcp_rest_request();
	}

	/**
	 * Check if the current request is a WP-CLI request for MCP adapter.
	 *
	 * Handles WP-CLI invocations like:
	 * - `wp mcp-adapter serve`
	 * - `wp --debug --user=1 mcp-adapter serve`
	 * - `wp --path=/var/www --quiet -vvv mcp-adapter serve`
	 *
	 * @return bool True if this is a WP-CLI MCP adapter request.
	 */
	private static function is_mcp_cli_request(): bool {
		// Check if this is a CLI request.
		if ( ! defined( 'WP_CLI' ) || ! constant( 'WP_CLI' ) ) {
			return false;
		}

		// Try to get the command from WP_CLI runner if available (strips global options).
		if ( class_exists( 'WP_CLI' ) && method_exists( 'WP_CLI', 'get_runner' ) ) {
			try {
				$runner = \WP_CLI::get_runner();
				if ( $runner && isset( $runner->arguments ) && is_array( $runner->arguments ) ) {
					// Check if the first non-option argument is 'mcp-adapter'.
					$first_arg = reset( $runner->arguments );
					if ( 'mcp-adapter' === $first_arg ) {
						return true;
					}
				}
			} catch ( \Exception $e ) {
				// If runner is not available or throws exception, fall through to argv check.
				unset( $e );
			}
		}

		// Fallback: scan $_SERVER['argv'] for the first non-flag token.
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.ValidatedSanitizedInput.MissingUnslash -- CLI arguments are safe in WP-CLI context.
		$cli_args = $_SERVER['argv'] ?? array();
		foreach ( $cli_args as $index => $arg ) {
			// Skip the script name (first element).
			if ( 0 === $index ) {
				continue;
			}
			// Skip global flags (start with '--' or single dash options like '-vvv').
			if ( str_starts_with( $arg, '--' ) || str_starts_with( $arg, '-' ) ) {
				continue;
			}
			// First non-flag argument found - check if it's 'mcp-adapter'.
			return 'mcp-adapter' === $arg;
		}

		return false;
	}

	/**
	 * Check if the current request is a REST API request for the MCP endpoint.
	 *
	 * @return bool True if this is a REST request to the MCP endpoint.
	 */
	private static function is_mcp_rest_request(): bool {
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
