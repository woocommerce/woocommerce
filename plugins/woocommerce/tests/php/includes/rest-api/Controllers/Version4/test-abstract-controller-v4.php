<?php
declare(strict_types=1);

require_once __DIR__ . '/test-abstract-schema-v4.php';

/**
 * Test implementation of AbstractController for testing purposes.
 */
class Test_Abstract_Controller_V4 extends Automattic\WooCommerce\RestApi\Routes\V4\AbstractController {

	/**
	 * Set rest_base for testing.
	 *
	 * @param string $rest_base The rest base.
	 */
	public function set_rest_base( string $rest_base ) {
		$this->rest_base = $rest_base;
	}

	/**
	 * Get hook prefix for testing.
	 *
	 * @return string
	 */
	public function get_hook_prefix(): string { // phpcs:ignore Generic.CodeAnalysis.UselessOverridingMethod.Found
		return parent::get_hook_prefix();
	}

	/**
	 * Get error prefix for testing.
	 *
	 * @return string
	 */
	public function get_error_prefix(): string { // phpcs:ignore Generic.CodeAnalysis.UselessOverridingMethod.Found
		return parent::get_error_prefix();
	}

	/**
	 * Check permissions for testing.
	 *
	 * @param string $object_type Object type.
	 * @param string $context Context.
	 * @param int    $object_id Object ID.
	 * @return bool
	 */
	public function check_permissions( string $object_type, string $context = 'read', int $object_id = 0 ): bool { // phpcs:ignore Generic.CodeAnalysis.UselessOverridingMethod.Found
		return parent::check_permissions( $object_type, $context, $object_id );
	}

	/**
	 * Get route error response for testing.
	 *
	 * @param string $error_code Error code.
	 * @param string $error_message Error message.
	 * @param int    $http_status_code HTTP status code.
	 * @param array  $additional_data Additional data.
	 * @return WP_Error
	 */
	public function get_route_error_response( string $error_code, string $error_message, int $http_status_code = 400, array $additional_data = array() ): WP_Error { // phpcs:ignore Generic.CodeAnalysis.UselessOverridingMethod.Found
		return parent::get_route_error_response( $error_code, $error_message, $http_status_code, $additional_data );
	}

	/**
	 * Get route error response from object for testing.
	 *
	 * @param WP_Error $error_object Error object.
	 * @param int      $http_status_code HTTP status code.
	 * @param array    $additional_data Additional data.
	 * @return WP_Error
	 */
	public function get_route_error_response_from_object( WP_Error $error_object, int $http_status_code = 400, array $additional_data = array() ): WP_Error { // phpcs:ignore Generic.CodeAnalysis.UselessOverridingMethod.Found
		return parent::get_route_error_response_from_object( $error_object, $http_status_code, $additional_data );
	}

	/**
	 * Get item schema for testing.
	 *
	 * @return array
	 */
	public function get_item_schema(): array { // phpcs:ignore Generic.CodeAnalysis.UselessOverridingMethod.Found
		if ( null === $this->schema ) {
			$this->schema = array(
				'$schema'    => 'http://json-schema.org/draft-04/schema#',
				'type'       => 'object',
				'title'      => Test_Abstract_Schema_V4::IDENTIFIER,
				'properties' => Test_Abstract_Schema_V4::get_item_schema_properties(),
			);
			/**
			 * Filter the item schema for this route.
			 *
			 * @param array $schema The item schema.
			 * @since 10.2.0
			 */
			$this->schema = apply_filters( $this->get_hook_prefix() . 'item_schema', $this->add_additional_fields_schema( $this->schema ) );
		}
		return $this->schema;
	}
}
