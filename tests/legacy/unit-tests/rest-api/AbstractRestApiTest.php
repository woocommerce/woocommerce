<?php
/**
 * REST API Endpoint Test base class.
 *
 * This class can be extended to add test coverage to REST API endpoints.
 *
 * For each endpoint, please test:
 * - Create
 * - Read
 * - Update
 * - Delete
 * - How the API responds to logged out/unauthorised users
 * - Schema
 * - Routes
 * - Collection params/queries
 *
 * @package WooCommerce\RestApi\Tests
 */

namespace Automattic\WooCommerce\RestApi\UnitTests;

defined( 'ABSPATH' ) || exit;

use \WC_REST_Unit_Test_Case;
use Automattic\WooCommerce\RestApi\UnitTests\Helpers\CustomerHelper;
use Automattic\WooCommerce\RestApi\UnitTests\Helpers\OrderHelper;
use Automattic\WooCommerce\RestApi\UnitTests\Helpers\ProductHelper;

/**
 * Abstract Rest API Test Class
 *
 * @extends  WC_REST_Unit_Test_Case
 */
abstract class AbstractRestApiTest extends WC_REST_Unit_Test_Case {

	/**
	 * The endpoint schema.
	 *
	 * @var array Keys are property names, values are supported context.
	 */
	protected $properties = [];

	/**
	 * Routes that this endpoint creates.
	 *
	 * @var array
	 */
	protected $routes = [];

	/**
	 * User variable.
	 *
	 * @var WP_User
	 */
	protected static $user;

	/**
	 * Setup once before running tests.
	 *
	 * @param object $factory Factory object.
	 */
	public static function wpSetUpBeforeClass( $factory ) {
		self::$user = $factory->user->create(
			array(
				'role' => 'administrator',
			)
		);
	}

	/**
	 * Setup test class.
	 */
	public function setUp() {
		parent::setUp();
		wp_set_current_user( self::$user );
	}

	/**
	 * Test route registration.
	 */
	public function test_register_routes() {
		$actual_routes   = $this->server->get_routes();
		$expected_routes = $this->routes;

		foreach ( $expected_routes as $expected_route ) {
			$this->assertArrayHasKey( $expected_route, $actual_routes );
		}
	}

	/**
	 * Validate that the returned API schema matches what is expected.
	 *
	 * @return void
	 */
	public function test_schema_properties() {
		$request    = new \WP_REST_Request( 'OPTIONS', $this->routes[0] );
		$response   = $this->server->dispatch( $request );
		$data       = $response->get_data();
		$properties = $data['schema']['properties'];

		$this->assertEquals( count( array_keys( $this->properties ) ), count( $properties ), print_r( array_diff( array_keys( $properties ), array_keys( $this->properties ) ), true ) );

		foreach ( array_keys( $this->properties ) as $property ) {
			$this->assertArrayHasKey( $property, $properties );
		}
	}

	/**
	 * Test creation using this method.
	 * If read-only, test to confirm this.
	 */
	abstract public function test_create();

	/**
	 * Test get/read using this method.
	 */
	abstract public function test_read();

	/**
	 * Test updates using this method.
	 * If read-only, test to confirm this.
	 */
	abstract public function test_update();

	/**
	 * Test delete using this method.
	 * If read-only, test to confirm this.
	 */
	abstract public function test_delete();

	/**
	 * Perform a request and return the status and returned data.
	 *
	 * @param string  $endpoint Endpoint to hit.
	 * @param string  $type Type of request e.g GET or POST.
	 * @param array   $params Request body or query.
	 * @return object
	 */
	protected function do_request( $endpoint, $type = 'GET', $params = [] ) {
		$request = new \WP_REST_Request( $type, untrailingslashit( $endpoint ) );
		'GET' === $type ? $request->set_query_params( $params ) : $request->set_body_params( $params );
		$response = $this->server->dispatch( $request );

		return (object) array(
			'status' => $response->get_status(),
			'data'   => json_decode( wp_json_encode( $response->get_data() ), true ),
			'raw'    => $response->get_data(),
		);
	}

	/**
	 * Test the request/response matched the data we sent.
	 *
	 * @param array $response Array of response data from do_request above.
	 * @param int   $status_code Expected status code.
	 * @param array $data Array of expected data.
	 */
	protected function assertExpectedResponse( $response, $status_code = 200, $data = array() ) {
		$this->assertObjectHasAttribute( 'status', $response );
		$this->assertObjectHasAttribute( 'data', $response );
		$this->assertEquals( $status_code, $response->status, print_r( $response->data, true ) );

		if ( $data ) {
			foreach ( $data as $key => $value ) {
				if ( ! isset( $response->data[ $key ] ) ) {
					continue;
				}
				switch ( $key ) {
					case 'meta_data':
						$this->assertMetaData( $value, $response->data[ $key ] );
						break;
					default:
						if ( is_array( $value ) ) {
							$this->assertArraySubset( $value, $response->data[ $key ] );
						} else {
							$this->assertEquals( $value, $response->data[ $key ] );
						}
				}
			}
		}
	}

	/**
	 * Test meta data in a response matches what we expect.
	 *
	 * @param array $expected_meta_data Array of data.
	 * @param array $actual_meta_data Array of data.
	 */
	protected function assertMetaData( $expected_meta_data, $actual_meta_data ) {
		$this->assertTrue( is_array( $actual_meta_data ) );
		$this->assertEquals( count( $expected_meta_data ), count( $actual_meta_data ) );

		foreach ( $actual_meta_data as $key => $meta ) {
			$this->assertArrayHasKey( 'id', $meta );
			$this->assertArrayHasKey( 'key', $meta );
			$this->assertArrayHasKey( 'value', $meta );
			$this->assertEquals( $expected_meta_data[ $key ]['key'], $meta['key'] );
			$this->assertEquals( $expected_meta_data[ $key ]['value'], $meta['value'] );
		}
	}

	/**
	 * Return array of properties for a given context.
	 *
	 * @param string $context Context to use.
	 * @return array
	 */
	protected function get_properties( $context = 'edit' ) {
		return array_keys( array_filter( $this->properties, function( $contexts ) use( $context ) {
			return in_array( $context, $contexts );
		} ) );
	}
}
