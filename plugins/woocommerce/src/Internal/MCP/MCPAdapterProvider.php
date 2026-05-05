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
	 * MCP adapter class name.
	 *
	 * @var string
	 */
	private const MCP_ADAPTER_CLASS = 'WP\MCP\Core\McpAdapter';

	/**
	 * Number of arguments WooCommerce passes to McpAdapter::create_server().
	 *
	 * @var int
	 */
	private const CREATE_SERVER_ARGUMENT_COUNT = 10;

	/**
	 * MCP adapter classes WooCommerce needs at runtime.
	 *
	 * @var array<string, string>
	 */
	private const REQUIRED_MCP_ADAPTER_CLASSES = array(
		'WP\MCP\Infrastructure\ErrorHandling\ErrorLogMcpErrorHandler'       => 'MCP error handler',
		'WP\MCP\Infrastructure\Observability\NullMcpObservabilityHandler'  => 'MCP observability handler',
		'WP\MCP\Transport\HttpTransport'                                   => 'MCP HTTP transport',
		'WP\MCP\Transport\Infrastructure\McpTransportContext'              => 'MCP transport context',
	);

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

		if ( ! $this->initialize_mcp_adapter() ) {
			return;
		}

		$this->register_hooks();
		$this->initialized = true;
	}

	/**
	 * Initialize the MCP adapter.
	 *
	 * @return bool Whether the MCP adapter was initialized.
	 */
	private function initialize_mcp_adapter(): bool {
		$compatibility_errors = self::get_mcp_adapter_compatibility_errors();

		if ( ! empty( $compatibility_errors ) ) {
			$this->log_mcp_adapter_compatibility_errors( $compatibility_errors );
			return false;
		}

		// Initialize the MCP adapter instance - this triggers the rest_api_init hook registration.
		\WP\MCP\Core\McpAdapter::instance();

		return true;
	}

	/**
	 * Get MCP adapter compatibility errors for the loaded runtime.
	 *
	 * @return array Compatibility error messages.
	 */
	public static function get_mcp_adapter_compatibility_errors(): array {
		return self::get_mcp_adapter_compatibility_errors_for(
			self::MCP_ADAPTER_CLASS,
			self::REQUIRED_MCP_ADAPTER_CLASSES,
			self::CREATE_SERVER_ARGUMENT_COUNT
		);
	}

	/**
	 * Get MCP adapter compatibility errors for a specific adapter API shape.
	 *
	 * @param string $adapter_class                MCP adapter class name.
	 * @param array  $required_classes             Required class names keyed by class and labelled by dependency role.
	 * @param int    $create_server_argument_count Number of arguments WooCommerce passes to create_server().
	 * @return array Compatibility error messages.
	 */
	private static function get_mcp_adapter_compatibility_errors_for( string $adapter_class, array $required_classes, int $create_server_argument_count ): array {
		$errors = array();

		if ( ! class_exists( $adapter_class ) ) {
			$errors[] = sprintf(
				'MCP adapter class %s was not found.',
				$adapter_class
			);
		} else {
			$errors = array_merge(
				$errors,
				self::get_mcp_adapter_method_compatibility_errors( $adapter_class, $create_server_argument_count )
			);
		}

		foreach ( $required_classes as $class_name => $class_description ) {
			if ( ! class_exists( $class_name ) ) {
				$errors[] = sprintf(
					'Required %1$s class %2$s was not found.',
					$class_description,
					$class_name
				);
			}
		}

		return $errors;
	}

	/**
	 * Get compatibility errors for the MCP adapter methods WooCommerce calls.
	 *
	 * @param string $adapter_class                MCP adapter class name.
	 * @param int    $create_server_argument_count Number of arguments WooCommerce passes to create_server().
	 * @return array Compatibility error messages.
	 */
	private static function get_mcp_adapter_method_compatibility_errors( string $adapter_class, int $create_server_argument_count ): array {
		$errors = array();

		if ( ! method_exists( $adapter_class, 'instance' ) ) {
			$errors[] = sprintf(
				'MCP adapter class %s is missing the instance() method.',
				$adapter_class
			);
		} else {
			$instance_method = new \ReflectionMethod( $adapter_class, 'instance' );
			if ( ! $instance_method->isPublic() || ! $instance_method->isStatic() ) {
				$errors[] = sprintf(
					'MCP adapter class %s must provide a public static instance() method.',
					$adapter_class
				);
			}
		}

		if ( ! method_exists( $adapter_class, 'create_server' ) ) {
			$errors[] = sprintf(
				'MCP adapter class %s is missing the create_server() method.',
				$adapter_class
			);
			return $errors;
		}

		$create_server_method = new \ReflectionMethod( $adapter_class, 'create_server' );
		if ( ! $create_server_method->isPublic() ) {
			$errors[] = sprintf(
				'MCP adapter class %s must provide a public create_server() method.',
				$adapter_class
			);
		}

		if (
			$create_server_method->getNumberOfRequiredParameters() > $create_server_argument_count ||
			$create_server_method->getNumberOfParameters() < $create_server_argument_count
		) {
			$errors[] = sprintf(
				'MCP adapter class %1$s has an incompatible create_server() signature for WooCommerce. Expected support for %2$d arguments.',
				$adapter_class,
				$create_server_argument_count
			);
		}

		return $errors;
	}

	/**
	 * Log MCP adapter compatibility errors.
	 *
	 * @param array $compatibility_errors Compatibility error messages.
	 */
	private function log_mcp_adapter_compatibility_errors( array $compatibility_errors ): void {
		if ( ! function_exists( 'wc_get_logger' ) ) {
			return;
		}

		wc_get_logger()->warning(
			'MCP adapter compatibility check failed. Skipping MCP initialization. ' . implode( ' ', $compatibility_errors ),
			array( 'source' => 'woocommerce-mcp' )
		);
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
			static function ( $ability_id ) {
				// Include WooCommerce abilities by default.
				$include = str_starts_with( $ability_id, 'woocommerce/' );

				// Allow filter to override inclusion decision.
				/**
				 * Filter to override MCP ability inclusion decision.
				 *
				 * @since 10.3.0
				 *
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
