<?php
namespace Automattic\WooCommerce\Blocks\StoreApi\Schemas;

use Automattic\WooCommerce\Blocks\Package;
use Automattic\WooCommerce\Blocks\Domain\Services\ExtendRestApi;
/**
 * AbstractSchema class.
 *
 * For REST Route Schemas
 *
 * @internal This API is used internally by Blocks--it is still in flux and may be subject to revisions.
 * @since 2.5.0
 */
abstract class AbstractSchema {
	/**
	 * The schema item name.
	 *
	 * @var string
	 */
	protected $title = 'Schema';

	/**
	 * Rest extend instance
	 *
	 * @var ExtendRestApi
	 */
	protected $extend;

	/**
	 * Extending key that gets added to endpoint.
	 *
	 * @var string
	 */
	const EXTENDING_KEY = 'extensions';

	/**
	 * Constructor.
	 *
	 * @param ExtendRestApi $extend Rest Extending instance.
	 */
	public function __construct( ExtendRestApi $extend ) {
		$this->extend = $extend;
	}

	/**
	 * Returns the full item schema.
	 *
	 * @return array
	 */
	public function get_item_schema() {
		return array(
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => $this->title,
			'type'       => 'object',
			'properties' => $this->get_properties(),
		);
	}

	/**
	 * Returns the public schema.
	 *
	 * @return array
	 */
	public function get_public_item_schema() {
		$schema = $this->get_item_schema();

		foreach ( $schema['properties'] as &$property ) {
			unset( $property['arg_options'] );
		}

		return $schema;
	}

	/**
	 * Returns extended data for a specific endpoint.
	 *
	 * @param string $endpoint The endpoint identifer.
	 * @param array  ...$passed_args An array of arguments to be passed to callbacks.
	 * @return object the data that will get added.
	 */
	protected function get_extended_data( $endpoint, ...$passed_args ) {
		return $this->extend->get_endpoint_data( $endpoint, $passed_args );
	}

	/**
	 * Returns extended schema for a specific endpoint.
	 *
	 * @param string $endpoint The endpoint identifer.
	 * @param array  ...$passed_args An array of arguments to be passed to callbacks.
	 * @return array the data that will get added.
	 */
	protected function get_extended_schema( $endpoint, ...$passed_args ) {
		return [
			'description' => __( 'Extensions data.', 'woocommerce' ),
			'type'        => [ 'object' ],
			'context'     => [ 'view', 'edit' ],
			'readonly'    => true,
			'properties'  => $this->extend->get_endpoint_schema( $endpoint, $passed_args ),
		];
	}

	/**
	 * Apply a schema get_item_response callback to an array of items and return the result.
	 *
	 * @param AbstractSchema $schema Schema class instance.
	 * @param array          $items Array of items.
	 * @return array Array of values from the callback function.
	 */
	protected function get_item_responses_from_schema( AbstractSchema $schema, $items ) {
		$items = array_filter( $items );

		if ( empty( $items ) ) {
			return [];
		}

		return array_values( array_map( [ $schema, 'get_item_response' ], $items ) );
	}

	/**
	 * Retrieves an array of endpoint arguments from the item schema for the controller.
	 *
	 * @param string $method Optional. HTTP method of the request.
	 * @return array Endpoint arguments.
	 */
	public function get_endpoint_args_for_item_schema( $method = \WP_REST_Server::CREATABLE ) {
		$schema            = $this->get_item_schema();
		$schema_properties = ! empty( $schema['properties'] ) ? $schema['properties'] : array();
		$endpoint_args     = array();

		foreach ( $schema_properties as $field_id => $params ) {

			// Arguments specified as `readonly` are not allowed to be set.
			if ( ! empty( $params['readonly'] ) ) {
				continue;
			}

			$endpoint_args[ $field_id ] = array(
				'validate_callback' => 'rest_validate_request_arg',
				'sanitize_callback' => 'rest_sanitize_request_arg',
			);

			if ( isset( $params['description'] ) ) {
				$endpoint_args[ $field_id ]['description'] = $params['description'];
			}

			if ( \WP_REST_Server::CREATABLE === $method && isset( $params['default'] ) ) {
				$endpoint_args[ $field_id ]['default'] = $params['default'];
			}

			if ( \WP_REST_Server::CREATABLE === $method && ! empty( $params['required'] ) ) {
				$endpoint_args[ $field_id ]['required'] = true;
			}

			foreach ( array( 'type', 'format', 'enum', 'items', 'properties', 'additionalProperties' ) as $schema_prop ) {
				if ( isset( $params[ $schema_prop ] ) ) {
					$endpoint_args[ $field_id ][ $schema_prop ] = $params[ $schema_prop ];
				}
			}

			// Merge in any options provided by the schema property.
			if ( isset( $params['arg_options'] ) ) {

				// Only use required / default from arg_options on CREATABLE endpoints.
				if ( \WP_REST_Server::CREATABLE !== $method ) {
					$params['arg_options'] = array_diff_key(
						$params['arg_options'],
						array(
							'required' => '',
							'default'  => '',
						)
					);
				}

				$endpoint_args[ $field_id ] = array_merge( $endpoint_args[ $field_id ], $params['arg_options'] );
			}
		}

		return $endpoint_args;
	}

	/**
	 * Force all schema properties to be readonly.
	 *
	 * @param array $properties Schema.
	 * @return array Updated schema.
	 */
	protected function force_schema_readonly( $properties ) {
		return array_map(
			function( $property ) {
				$property['readonly'] = true;
				if ( isset( $property['items']['properties'] ) ) {
					$property['items']['properties'] = $this->force_schema_readonly( $property['items']['properties'] );
				}
				return $property;
			},
			$properties
		);
	}

	/**
	 * Returns consistent currency schema used across endpoints for prices.
	 *
	 * @return array
	 */
	protected function get_store_currency_properties() {
		return [
			'currency_code'               => [
				'description' => __( 'Currency code (in ISO format) for returned prices.', 'woocommerce' ),
				'type'        => 'string',
				'context'     => [ 'view', 'edit' ],
				'readonly'    => true,
			],
			'currency_symbol'             => [
				'description' => __( 'Currency symbol for the currency which can be used to format returned prices.', 'woocommerce' ),
				'type'        => 'string',
				'context'     => [ 'view', 'edit' ],
				'readonly'    => true,
			],
			'currency_minor_unit'         => [
				'description' => __( 'Currency minor unit (number of digits after the decimal separator) for returned prices.', 'woocommerce' ),
				'type'        => 'integer',
				'context'     => [ 'view', 'edit' ],
				'readonly'    => true,
			],
			'currency_decimal_separator'  => array(
				'description' => __( 'Decimal separator for the currency which can be used to format returned prices.', 'woocommerce' ),
				'type'        => 'string',
				'context'     => array( 'view', 'edit' ),
				'readonly'    => true,
			),
			'currency_thousand_separator' => array(
				'description' => __( 'Thousand separator for the currency which can be used to format returned prices.', 'woocommerce' ),
				'type'        => 'string',
				'context'     => array( 'view', 'edit' ),
				'readonly'    => true,
			),
			'currency_prefix'             => array(
				'description' => __( 'Price prefix for the currency which can be used to format returned prices.', 'woocommerce' ),
				'type'        => 'string',
				'context'     => array( 'view', 'edit' ),
				'readonly'    => true,
			),
			'currency_suffix'             => array(
				'description' => __( 'Price prefix for the currency which can be used to format returned prices.', 'woocommerce' ),
				'type'        => 'string',
				'context'     => array( 'view', 'edit' ),
				'readonly'    => true,
			),
		];
	}

	/**
	 * Adds currency data to an array of monetary values.
	 *
	 * @param array $values Monetary amounts.
	 * @return array Monetary amounts with currency data appended.
	 */
	protected function prepare_currency_response( $values ) {
		return $this->extend->get_formatter( 'currency' )->format( $values );
	}

	/**
	 * Convert monetary values from WooCommerce to string based integers, using
	 * the smallest unit of a currency.
	 *
	 * @param string|float $amount Monetary amount with decimals.
	 * @param int          $decimals Number of decimals the amount is formatted with.
	 * @param int          $rounding_mode Defaults to the PHP_ROUND_HALF_UP constant.
	 * @return string      The new amount.
	 */
	protected function prepare_money_response( $amount, $decimals = 2, $rounding_mode = PHP_ROUND_HALF_UP ) {
		return $this->extend->get_formatter( 'money' )->format(
			$amount,
			[
				'decimals'      => $decimals,
				'rounding_mode' => $rounding_mode,
			]
		);
	}

	/**
	 * Prepares HTML based content, such as post titles and content, for the API response.
	 *
	 * @param string|array $response Data to format.
	 * @return string|array Formatted data.
	 */
	protected function prepare_html_response( $response ) {
		return $this->extend->get_formatter( 'html' )->format( $response );
	}
}
