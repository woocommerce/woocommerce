<?php
namespace Automattic\WooCommerce\Blocks\Domain\Services;

use Automattic\WooCommerce\Blocks\Domain\Package;
use Automattic\WooCommerce\Blocks\StoreApi\Routes\RouteException;
use Automattic\WooCommerce\Blocks\StoreApi\Formatters;
use Throwable;
use Exception;

/**
 * Service class to provide utility functions to extend REST API.
 */
final class ExtendRestApi {
	/**
	 * List of Store API schema that is allowed to be extended by extensions.
	 *
	 * @var array
	 */
	private $endpoints = [
		\Automattic\WooCommerce\Blocks\StoreApi\Schemas\CartItemSchema::IDENTIFIER,
		\Automattic\WooCommerce\Blocks\StoreApi\Schemas\CartSchema::IDENTIFIER,
		\Automattic\WooCommerce\Blocks\StoreApi\Schemas\CheckoutSchema::IDENTIFIER,
	];

	/**
	 * Holds the Package instance
	 *
	 * @var Package
	 */
	private $package;

	/**
	 * Holds the formatters class instance.
	 *
	 * @var Formatters
	 */
	private $formatters;

	/**
	 * Constructor
	 *
	 * @param Package    $package An instance of the package class.
	 * @param Formatters $formatters An instance of the formatters class.
	 */
	public function __construct( Package $package, Formatters $formatters ) {
		$this->package    = $package;
		$this->formatters = $formatters;
	}

	/**
	 * Returns a formatter instance.
	 *
	 * @param string $name Formatter name.
	 * @return FormatterInterface
	 */
	public function get_formatter( $name ) {
		return $this->formatters->$name;
	}

	/**
	 * Data to be extended
	 *
	 * @var array
	 */
	private $extend_data = [];

	/**
	 * Data to be extended
	 *
	 * @var array
	 */
	private $callback_methods = [];

	/**
	 * Array of payment requirements
	 *
	 * @var array
	 */
	private $payment_requirements = [];

	/**
	 * An endpoint that validates registration method call
	 *
	 * @param array $args {
	 *     An array of elements that make up a post to update or insert.
	 *
	 *     @type string   $endpoint The endpoint to extend.
	 *     @type string   $namespace Plugin namespace.
	 *     @type callable $schema_callback Callback executed to add schema data.
	 *     @type callable $data_callback Callback executed to add endpoint data.
	 *     @type string   $schema_type The type of data, object or array.
	 * }
	 *
	 * @throws Exception On failure to register.
	 * @return boolean True on success.
	 */
	public function register_endpoint_data( $args ) {
		if ( ! is_string( $args['namespace'] ) ) {
			$this->throw_exception( 'You must provide a plugin namespace when extending a Store REST endpoint.' );
		}

		if ( ! is_string( $args['endpoint'] ) || ! in_array( $args['endpoint'], $this->endpoints, true ) ) {
			$this->throw_exception(
				sprintf( 'You must provide a valid Store REST endpoint to extend, valid endpoints are: %1$s. You provided %2$s.', implode( ', ', $this->endpoints ), $args['endpoint'] )
			);
		}

		if ( isset( $args['schema_callback'] ) && ! is_callable( $args['schema_callback'] ) ) {
			$this->throw_exception( '$schema_callback must be a callable function.' );
		}

		if ( isset( $args['data_callback'] ) && ! is_callable( $args['data_callback'] ) ) {
			$this->throw_exception( '$data_callback must be a callable function.' );
		}

		if ( isset( $args['schema_type'] ) && ! in_array( $args['schema_type'], [ ARRAY_N, ARRAY_A ], true ) ) {
			$this->throw_exception(
				sprintf( 'Data type must be either ARRAY_N for a numeric array or ARRAY_A for an object like array. You provided %1$s.', $args['schema_type'] )
			);
		}

		$this->extend_data[ $args['endpoint'] ][ $args['namespace'] ] = [
			'schema_callback' => isset( $args['schema_callback'] ) ? $args['schema_callback'] : null,
			'data_callback'   => isset( $args['data_callback'] ) ? $args['data_callback'] : null,
			'schema_type'     => isset( $args['schema_type'] ) ? $args['schema_type'] : ARRAY_A,
		];

		return true;
	}

	/**
	 * Add callback functions that can be executed by the cart/extensions endpoint.
	 *
	 * @param array $args {
	 *     An array of elements that make up the callback configuration.
	 *
	 *     @type string   $endpoint The endpoint to extend.
	 *     @type string   $namespace Plugin namespace.
	 *     @type callable $callback The function/callable to execute.
	 * }
	 *
	 * @throws RouteException On failure to register.
	 * @returns boolean True on success.
	 */
	public function register_update_callback( $args ) {
		if ( ! array_key_exists( 'namespace', $args ) || ! is_string( $args['namespace'] ) ) {
			throw new RouteException(
				'woocommerce_rest_cart_extensions_error',
				'You must provide a plugin namespace when extending a Store REST endpoint.',
				400
			);
		}

		if ( ! array_key_exists( 'callback', $args ) || ! is_callable( $args['callback'] ) ) {
			throw new RouteException(
				'woocommerce_rest_cart_extensions_error',
				'There is no valid callback supplied to register_update_callback.',
				400
			);
		}

		$this->callback_methods[ $args['namespace'] ] = [
			'callback' => $args['callback'],
		];
		return true;
	}

	/**
	 * Get callback for a specific endpoint and namespace.
	 *
	 * @param string $namespace The namespace to get callbacks for.
	 *
	 * @return callable The callback registered by the extension.
	 * @throws RouteException When callback is not callable or parameters are incorrect.
	 */
	public function get_update_callback( $namespace ) {
		$method = null;
		if ( ! is_string( $namespace ) ) {
			throw new RouteException(
				'woocommerce_rest_cart_extensions_error',
				'You must provide a plugin namespace when extending a Store REST endpoint.',
				400
			);
		}

		if ( ! array_key_exists( $namespace, $this->callback_methods ) ) {
			throw new RouteException(
				'woocommerce_rest_cart_extensions_error',
				sprintf( 'There is no such namespace registered: %1$s.', $namespace ),
				400
			);
		}

		if ( ! array_key_exists( 'callback', $this->callback_methods[ $namespace ] ) || ! is_callable( $this->callback_methods[ $namespace ]['callback'] ) ) {
			throw new RouteException(
				'woocommerce_rest_cart_extensions_error',
				sprintf( 'There is no valid callback registered for: %1$s.', $namespace ),
				400
			);
		}
		return $this->callback_methods[ $namespace ]['callback'];
	}

	/**
	 * Returns the registered endpoint data
	 *
	 * @param string $endpoint    A valid identifier.
	 * @param array  $passed_args Passed arguments from the Schema class.
	 * @return object Returns an casted object with registered endpoint data.
	 * @throws Exception If a registered callback throws an error, or silently logs it.
	 */
	public function get_endpoint_data( $endpoint, array $passed_args = [] ) {
		$registered_data = [];
		if ( ! isset( $this->extend_data[ $endpoint ] ) ) {
			return (object) $registered_data;
		}
		foreach ( $this->extend_data[ $endpoint ] as $namespace => $callbacks ) {
			$data = [];

			if ( is_null( $callbacks['data_callback'] ) ) {
				continue;
			}

			try {
				$data = $callbacks['data_callback']( ...$passed_args );

				if ( ! is_array( $data ) ) {
					throw new Exception( '$data_callback must return an array.' );
				}
			} catch ( Throwable $e ) {
				$this->throw_exception( $e );
				continue;
			}

			$registered_data[ $namespace ] = $data;
		}
		return (object) $registered_data;
	}

	/**
	 * Returns the registered endpoint schema
	 *
	 * @param string $endpoint    A valid identifier.
	 * @param array  $passed_args Passed arguments from the Schema class.
	 * @return array Returns an array with registered schema data.
	 * @throws Exception If a registered callback throws an error, or silently logs it.
	 */
	public function get_endpoint_schema( $endpoint, array $passed_args = [] ) {
		$registered_schema = [];
		if ( ! isset( $this->extend_data[ $endpoint ] ) ) {
			return (object) $registered_schema;
		}

		foreach ( $this->extend_data[ $endpoint ] as $namespace => $callbacks ) {
			$schema = [];

			if ( is_null( $callbacks['schema_callback'] ) ) {
				continue;
			}

			try {
				$schema = $callbacks['schema_callback']( ...$passed_args );

				if ( ! is_array( $schema ) ) {
					throw new Exception( '$schema_callback must return an array.' );
				}
			} catch ( Throwable $e ) {
				$this->throw_exception( $e );
				continue;
			}

			$schema = $this->format_extensions_properties( $namespace, $schema, $callbacks['schema_type'] );

			$registered_schema[ $namespace ] = $schema;
		}
		return (object) $registered_schema;
	}

	/**
	 * Registers and validates payment requirements callbacks.
	 *
	 * @param array $args {
	 *     Array of registration data.
	 *
	 *     @type callable $data_callback Callback executed to add payment requirements data.
	 * }
	 *
	 * @throws Exception On failure to register.
	 * @return boolean True on success.
	 */
	public function register_payment_requirements( $args ) {
		if ( ! is_callable( $args['data_callback'] ) ) {
			$this->throw_exception( '$data_callback must be a callable function.' );
		}

		$this->payment_requirements[] = $args['data_callback'];

		return true;
	}

	/**
	 * Returns the additional payment requirements.
	 *
	 * @param array $initial_requirements list of requirements that should be added to the collected requirements.
	 * @return array Returns a list of payment requirements.
	 * @throws Exception If a registered callback throws an error, or silently logs it.
	 */
	public function get_payment_requirements( array $initial_requirements = [ 'products' ] ) {
		$requirements = $initial_requirements;
		if ( empty( $this->payment_requirements ) ) {
			return $initial_requirements;
		}

		foreach ( $this->payment_requirements as $callback ) {
			$data = [];

			try {
				$data = $callback();

				if ( ! is_array( $data ) ) {
					throw new Exception( '$data_callback must return an array.' );
				}
			} catch ( Throwable $e ) {
				$this->throw_exception( $e );
				continue;
			}
			$requirements = array_merge( $requirements, $data );
		}

		return array_unique( $requirements );
	}

	/**
	 * Throws error and/or silently logs it.
	 *
	 * @param string|Throwable $exception_or_error Error message or Exception.
	 * @throws Exception An error to throw if we have debug enabled and user is admin.
	 */
	private function throw_exception( $exception_or_error ) {
		if ( is_string( $exception_or_error ) ) {
			$exception = new Exception( $exception_or_error );
		} else {
			$exception = $exception_or_error;
		}
		// Always log an error.
		wc_caught_exception( $exception );
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG && current_user_can( 'manage_woocommerce' ) ) {
			throw $exception;
		}
	}

	/**
	 * Format schema for an extension.
	 *
	 * @param string $namespace Error message or Exception.
	 * @param array  $schema An error to throw if we have debug enabled and user is admin.
	 * @param string $schema_type How should data be shaped.
	 *
	 * @return array Formatted schema.
	 */
	private function format_extensions_properties( $namespace, $schema, $schema_type ) {
		if ( ARRAY_N === $schema_type ) {
			return [
				/* translators: %s: extension namespace */
				'description' => sprintf( __( 'Extension data registered by %s', 'woocommerce' ), $namespace ),
				'type'        => 'array',
				'context'     => [ 'view', 'edit' ],
				'items'       => $schema,
			];
		}
		return [
			/* translators: %s: extension namespace */
			'description' => sprintf( __( 'Extension data registered by %s', 'woocommerce' ), $namespace ),
			'type'        => 'object',
			'context'     => [ 'view', 'edit' ],
			'properties'  => $schema,
		];
	}
}
