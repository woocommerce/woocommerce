<?php
/**
 * Rate Limits Tests
 */

namespace Automattic\WooCommerce\Tests\Blocks\StoreApi;

use Automattic\WooCommerce\StoreApi\Authentication;
use Automattic\WooCommerce\StoreApi\Utilities\RateLimits;
use ReflectionClass;
use ReflectionException;
use Spy_REST_Server;
use WP_REST_Server;
use WP_Test_REST_TestCase;

/**
 * ControllerTests
 */
class RateLimitsTests extends WP_Test_REST_TestCase {
	/**
	 * Setup Rest API server.
	 */
	protected function setUp(): void {
		parent::setUp();

		/** @var WP_REST_Server $wp_rest_server */
		global $wp_rest_server;
		$wp_rest_server = new Spy_REST_Server();

		$GLOBALS['wp']->query_vars['rest_route'] = '/wc/store/cart';
		$_SERVER['REMOTE_ADDR']                  = '76.45.67.179';

		do_action( 'rest_api_init', $wp_rest_server );
	}

	/**
	 * Tests that Rate limiting headers are sent and set correctly when Rate Limiting
	 * main functionality is enabled.
	 *
	 * @return void
	 */
	public function test_rate_limits_response_headers() {
		add_filter(
			'woocommerce_store_api_rate_limit_options',
			function () {
				return array( 'enabled' => true );
			}
		);

		$rate_limiting_options = RateLimits::get_options();

		/** @var Spy_REST_Server $spy_rest_server */
		$spy_rest_server = rest_get_server();
		$spy_rest_server->serve_request( '/wc/store/cart' );

		$this->assertArrayHasKey( 'RateLimit-Limit', $spy_rest_server->sent_headers );
		$this->assertArrayHasKey( 'RateLimit-Remaining', $spy_rest_server->sent_headers );
		$this->assertArrayHasKey( 'RateLimit-Reset', $spy_rest_server->sent_headers );

		$this->assertEquals( $rate_limiting_options->limit, $spy_rest_server->sent_headers['RateLimit-Limit'] );
		$this->assertTrue( $spy_rest_server->sent_headers['RateLimit-Remaining'] > 0 );
		$this->assertIsInt( $spy_rest_server->sent_headers['RateLimit-Reset'] );
		$this->assertGreaterThan( time(), $spy_rest_server->sent_headers['RateLimit-Reset'] );

		// Exhaust the limit.
		do {
			$remaining = $spy_rest_server->sent_headers['RateLimit-Remaining'];

			$spy_rest_server->serve_request( '/wc/store/cart' );

			$this->assertEquals( $rate_limiting_options->limit, $spy_rest_server->sent_headers['RateLimit-Limit'] );
			$this->assertIsInt( $spy_rest_server->sent_headers['RateLimit-Reset'] );
			$this->assertGreaterThan( time(), $spy_rest_server->sent_headers['RateLimit-Reset'] );
			$this->assertEquals( $remaining - 1, $spy_rest_server->sent_headers['RateLimit-Remaining'] );
		} while ( $spy_rest_server->sent_headers['RateLimit-Remaining'] > 0 );

		// Attempt a request after rate limit is reached.
		$spy_rest_server->serve_request( '/wc/store/cart' );

		$body = json_decode( $spy_rest_server->sent_body );
		$this->assertEquals( JSON_ERROR_NONE, json_last_error() );
		$this->assertEquals( 400, $body->data->status );

		$this->assertEquals( $rate_limiting_options->limit, $spy_rest_server->sent_headers['RateLimit-Limit'] );
		$this->assertIsInt( $spy_rest_server->sent_headers['RateLimit-Reset'] );
		$this->assertGreaterThan( time(), $spy_rest_server->sent_headers['RateLimit-Reset'] );
		$this->assertEquals( 0, $spy_rest_server->sent_headers['RateLimit-Remaining'] );
		$this->assertArrayHasKey( 'RateLimit-Retry-After', $spy_rest_server->sent_headers );
		$this->assertIsInt( $spy_rest_server->sent_headers['RateLimit-Retry-After'] );
		$this->assertLessThanOrEqual( $rate_limiting_options->seconds, $spy_rest_server->sent_headers['RateLimit-Retry-After'] );
	}

	/**
	 * Tests that get_ip_address() correctly selects the $_SERVER var, parses and return the IP whether
	 * behind a proxy or not.
	 *
	 * @return void
	 * @throws ReflectionException On failing invoked protected method through reflection class.
	 */
	public function test_get_ip_address_method() {
		$_SERVER = array_merge(
			$_SERVER,
			array(
				'REMOTE_ADDR'          => '76.45.67.100',
				'HTTP_X_REAL_IP'       => '76.45.67.101',
				'HTTP_CLIENT_IP'       => '76.45.67.102',
				'HTTP_X_FORWARDED_FOR' => '76.45.67.103,2001:db8:85a3:8d3:1319:8a2e:370:7348,150.172.238.178',
				'HTTP_FORWARDED'       => 'for="[2001:0db8:85a3:0000:0000:8a2e:0370:7334]:4711";proto=http;by=203.0.113.43,for=192.0.2.60;proto=https;by=203.0.113.43',
			)
		);

		$authentication = new ReflectionClass( Authentication::class );
		// As the method we're testing is protected, we're using ReflectionClass to set it accessible from the outside.
		$get_ip_address = $authentication->getMethod( 'get_ip_address' );
		$get_ip_address->setAccessible( true );

		$this->assertEquals( '76.45.67.100', $get_ip_address->invokeArgs( $authentication, array() ) );

		$this->assertEquals( '76.45.67.101', $get_ip_address->invokeArgs( $authentication, array( true ) ) );
		$_SERVER['HTTP_X_REAL_IP'] = 'invalid_ip_address';
		$this->assertequals( '0.0.0.0', $get_ip_address->invokeArgs( $authentication, array( true ) ) );

		unset( $_SERVER['REMOTE_ADDR'] );
		unset( $_SERVER['HTTP_X_REAL_IP'] );
		$this->assertEquals( '76.45.67.102', $get_ip_address->invokeArgs( $authentication, array( true ) ) );
		$_SERVER['HTTP_CLIENT_IP'] = 'invalid_ip_address';
		$this->assertequals( '0.0.0.0', $get_ip_address->invokeArgs( $authentication, array( true ) ) );

		unset( $_SERVER['HTTP_CLIENT_IP'] );
		$this->assertEquals( '76.45.67.103', $get_ip_address->invokeArgs( $authentication, array( true ) ) );
		$_SERVER['HTTP_X_FORWARDED_FOR'] = 'invalid_ip_address,76.45.67.103';
		$this->assertequals( '0.0.0.0', $get_ip_address->invokeArgs( $authentication, array( true ) ) );

		unset( $_SERVER['HTTP_X_FORWARDED_FOR'] );
		$this->assertEquals( '2001:0db8:85a3:0000:0000:8a2e:0370:7334', $get_ip_address->invokeArgs( $authentication, array( true ) ) );
		$_SERVER['HTTP_FORWARDED'] = 'for=invalid_ip_address;proto=https;by=203.0.113.43';
		$this->assertequals( '0.0.0.0', $get_ip_address->invokeArgs( $authentication, array( true ) ) );

		unset( $_SERVER['HTTP_FORWARDED'] );
		$this->assertequals( '0.0.0.0', $get_ip_address->invokeArgs( $authentication, array( true ) ) );

	}
}
