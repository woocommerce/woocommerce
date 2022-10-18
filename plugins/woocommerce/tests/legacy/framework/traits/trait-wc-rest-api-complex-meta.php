<?php
/**
 * Trait for easier testing of objects that have `mixed` data type somewhere.
 *
 * @package Automattic/WooCommerce/Tests/WC_REST_API_Complex_Meta.
 */

/**
 * Trait WC_REST_API_Complex_Meta
 */
trait WC_REST_API_Complex_Meta {

	/**
	 * Sample data of different built in data types.
	 *
	 * @var array
	 */
	public static $sample_meta = array(
		array(
			'key' => 'string_meta',
			'value' => 'string_value',
		),
		array(
			'key' => 'int_meta',
			'value' => 1,
		),
		array(
			'key' => 'bool_meta',
			'value' => true,
		),
		array(
			'key' => 'array_meta',
			'value' => array( 1, 2, 'string' ),
		),
		array(
			'key' => 'null_meta',
			'value' => 'null',
		),
		array(
			'key' => 'object_meta',
			'value' => array(
				'nested_key1' => 'nested_value1',
				'nested_key2' => 0,
				'nested_key3' => true,
				'nested_key4' => array( 2, 3, 4 ),
				'nested_key5' => array( 2, 3, array( 'deep' => 'nesting' ) ),
			),
		),
	);

	/**
	 * Test to update `meta_data` field with a complex data type.
	 *
	 * @param string $url     URL to send request against.
	 * @param array  $options Options for customizations.
	 */
	public function assert_update_complex_meta( $url, $options = array() ) {
		$meta = $options['meta'] ?? self::$sample_meta;
		$request = new WP_REST_Request( 'PUT', $url );
		$request->set_body_params( array( 'meta_data' => $meta ) );

		$response = $this->server->dispatch( $request );

		$data = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );

		$response_meta = $data['meta_data'];

		foreach ( $meta as $meta_object ) {
			$found = false;
			foreach ( $response_meta as $response_meta_object ) {
				if ( $response_meta_object->key === $meta_object['key'] ) {
					$response_value = $response_meta_object->value;
					$this->assertEquals( $meta_object['value'], $response_value );
					$found = true;
					break;
				}
			}
			$this->assertEquals( true, $found, sprintf( 'Meta key %s was not found in response.', $meta_object['key'] ) );
		}
	}
}
