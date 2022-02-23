<?php
/**
 * ControllerTestCase Tests.
 */

namespace Automattic\WooCommerce\Blocks\Tests\StoreApi\Routes;

use Automattic\WooCommerce\Blocks\Domain\Services\ExtendRestApi;
use Automattic\WooCommerce\Blocks\Domain\Package as DomainPackage;
use Automattic\WooCommerce\Blocks\StoreApi\Formatters;
use Automattic\WooCommerce\Blocks\StoreApi\Formatters\MoneyFormatter;
use Automattic\WooCommerce\Blocks\StoreApi\Formatters\HtmlFormatter;
use Automattic\WooCommerce\Blocks\StoreApi\Formatters\CurrencyFormatter;
use Automattic\WooCommerce\Blocks\Domain\Services\FeatureGating;

/**
 * ControllerTestCase class.
 */
abstract class ControllerTestCase extends \WP_Test_REST_TestCase {
	/**
	 * ExtendRestApi class instance.
	 *
	 * @var ExtendRestApi
	 */
	protected $mock_extend;

	/**
	 * Setup Rest API server.
	 */
	public function setUp() {
		parent::setUp();

		/** @var \WP_REST_Server $wp_rest_server */
		global $wp_rest_server;
		$wp_rest_server = new \Spy_REST_Server();
		do_action( 'rest_api_init', $wp_rest_server );

		wp_set_current_user( 0 );
		update_option( 'woocommerce_weight_unit', 'g' );

		$formatters = new Formatters();
		$formatters->register( 'money', MoneyFormatter::class );
		$formatters->register( 'html', HtmlFormatter::class );
		$formatters->register( 'currency', CurrencyFormatter::class );
		$this->mock_extend = new ExtendRestApi( new DomainPackage( '', '', new FeatureGating( 2 ) ), $formatters );
	}

	/**
	 * Tear down Rest API server.
	 */
	public function tearDown() {
		parent::tearDown();
		/** @var \WP_REST_Server $wp_rest_server */
		global $wp_rest_server;
		$wp_rest_server = null;
	}

	/**
	 * Get API response from server.
	 *
	 * @param string $endpoint Namespace and path.
	 * @return \WP_Rest_Response
	 */
	public function getApiResponse( $endpoint ) {
		return rest_get_server()->dispatch( new \WP_REST_Request( 'GET', $endpoint ) );
	}

	/**
	 * Asserts that the given fields are present in the given object.
	 *
	 * @param object $object  The object to check.
	 * @param array  $fields  The fields to check.
	 * @param string $message Optional. Message to display when the assertion fails.
	 */
	public function assertEqualFields( $object, $fields, $message = '' ) {
		$this->assertIsObject( $object, $message . ' Passed $object is not an object.' );
		$this->assertIsArray( $fields, $message . ' Passed $fields is not an array.' );
		$this->assertNotEmpty( $fields, $message . ' Fields array is empty.' );

		foreach ( $fields as $field_name => $field_value ) {
			$this->assertObjectHasAttribute( $field_name, $object, $message . " Property $field_name does not exist on the object." );
			$this->assertSame( $field_value, $object->$field_name, $message . " Value of property $field_name is not $field_value." );
		}
	}
	/**
	 * Asserts that the contents of two un-keyed, single arrays are equal, without accounting for the order of elements.
	 *
	 * @param array $expected Expected array.
	 * @param array $actual   Array to check.
	 */
	public function assertEqualSets( $expected, $actual ) {
		sort( $expected );
		sort( $actual );
		$this->assertEquals( $expected, $actual );
	}

	/**
	 * Custom assertion of an API response to confirm data and response code is expected.
	 *
	 * @param string $endpoint_or_request Route endpoint to get.
	 * @param int    $expected_response_code Expected response code.
	 * @param array  $expected_response_data Expected response data.
	 */
	public function assertApiResponse( $endpoint_or_request, $expected_response_code, $expected_response_data = null ) {
		$response      = is_a( $endpoint_or_request, '\WP_Rest_Request' ) ? rest_get_server()->dispatch( $endpoint_or_request ) : $this->getApiResponse( $endpoint_or_request );
		$response_code = $response->get_status();
		$response_data = $response->get_data();

		$this->assertEquals( $expected_response_code, $response_code );

		if ( 204 === $response_code ) {
			$this->assertEmpty( $response_data );
		}

		if ( is_array( $expected_response_data ) ) {
			if ( empty( $expected_response_data ) ) {
				$this->assertEmpty( $response_data );
			} else {
				foreach ( $expected_response_data as $key => $value ) {
					$this->assertAPIFieldValue( $response_data[ $key ], $value );
				}
			}
		}
	}

	/**
	 * Run assertions on a field in an API response.
	 *
	 * @param mixed $actual_value Value from API response.
	 * @param mixed $expected_value Value to test against.
	 */
	protected function assertAPIFieldValue( $actual_value, $expected_value ) {
		if ( is_callable( $expected_value ) ) {
			$this->assertEquals( true, $expected_value( $actual_value ) );
			return;
		}
		if ( is_object( $actual_value ) && is_array( $expected_value ) ) {
			$this->assertEqualFields( $actual_value, $expected_value );
			return;
		}
		if ( is_array( $actual_value ) && is_array( $expected_value ) ) {
			$asserted_keys = array();
			foreach ( $actual_value as $actual_value_key => $actual_value_value ) {
				if ( isset( $expected_value[ $actual_value_key ] ) ) {
					$asserted_keys[] = $actual_value_key;
					$this->assertAPIFieldValue( $actual_value_value, $expected_value[ $actual_value_key ] );
				}
			}
			$this->assertEqualSets( $asserted_keys, array_keys( $expected_value ) );
			return;
		}
		$this->assertEquals( $expected_value, $actual_value );
	}
}
