<?php
namespace Automattic\WooCommerce\Blocks\Domain\Services;

use Automattic\WooCommerce\Blocks\Domain\Package;
use Automattic\WooCommerce\Blocks\StoreApi\Schemas\CartItemSchema;
use Throwable;
use Exception;

/**
 * Service class to provide utility functions to extend REST API.
 */
class ExtendRestApi {
	/**
	 * Holds the Package instance
	 *
	 * @var Package
	 */
	private $package;

	/**
	 * Constructor
	 *
	 * @param Package $package An instance of the package class.
	 */
	public function __construct( Package $package ) {
		$this->package = $package;
	}

	/**
	 * Valid endpoints to extend
	 *
	 * @var array
	 */
	private $endpoints = [ CartItemSchema::IDENTIFIER ];

	/**
	 * Data to be extended
	 *
	 * @var array
	 */
	private $extend_data = [];

	/**
	 * An endpoint that validates registration method call
	 *
	 * @param string   $endpoint The endpoint to extend.
	 * @param string   $namespace Plugin namespace.
	 * @param callable $schema_callback Callback executed to add schema data.
	 * @param callable $data_callback Callback executed to add endpoint data.
	 *
	 * @throws Exception On failure to register.
	 * @return boolean True on success.
	 */
	public function register_endpoint_data( $endpoint, $namespace, $schema_callback, $data_callback ) {
		if ( ! is_string( $namespace ) ) {
			$this->throw_exception( 'You must provide a plugin namespace when extending a Store REST endpoint.' );
		}

		if ( ! is_string( $endpoint ) || ! in_array( $endpoint, $this->endpoints, true ) ) {
			$this->throw_exception(
				sprintf( 'You must provide a valid Store REST endpoint to extend, valid endpoints are: %1$s. You provided %2$s.', implode( ', ', $this->endpoints ), $endpoint )
			);
		}

		if ( ! is_callable( $schema_callback ) ) {
			$this->throw_exception( '$schema_callback must be a callable function.' );
		}

		if ( ! is_callable( $data_callback ) ) {
			$this->throw_exception( '$data_callback must be a callable function.' );
		}

		$this->extend_data[ $endpoint ][ $namespace ] = [
			'schema_callback' => $schema_callback,
			'data_callback'   => $data_callback,
		];

		return true;
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

			try {
				$schema = $callbacks['schema_callback']( ...$passed_args );

				if ( ! is_array( $schema ) ) {
					throw new Exception( '$schema_callback must return an array.' );
				}
			} catch ( Throwable $e ) {
				$this->throw_exception( $e );
				continue;
			}

			$schema = $this->format_extensions_properties( $namespace, $schema );

			$registered_schema[ $namespace ] = $schema;
		}
		return (object) $registered_schema;
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
	 *
	 * @return array Formatted schema.
	 */
	private function format_extensions_properties( $namespace, $schema ) {
		return [
			/* translators: %s: extension namespace */
			'description' => sprintf( __( 'Extension data registered by %s', 'woo-gutenberg-products-block' ), $namespace ),
			'type'        => 'object',
			'context'     => [ 'view', 'edit' ],
			'readonly'    => true,
			'properties'  => $schema,
		];
	}
}
