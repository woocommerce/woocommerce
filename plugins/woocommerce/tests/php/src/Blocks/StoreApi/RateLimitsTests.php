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

	/**
	 * Tests that get_rate_limiting_id() correctly returns the USER ID, IP or filter result for set conditions.
	 *
	 * @return void
	 * @throws ReflectionException On failing invoked protected method through reflection class.
	 */
	public function test_get_rate_limiting_id_method() {
		$authentication = new ReflectionClass( Authentication::class );
		// As the method we're testing is protected, we're using ReflectionClass to set it accessible from the outside.
		$get_rate_limiting_id = $authentication->getMethod( 'get_rate_limiting_id' );
		$get_rate_limiting_id->setAccessible( true );

		$_SERVER['REMOTE_ADDR'] = '76.45.67.102';
		$this->assertEquals( md5( '76.45.67.102' ), $get_rate_limiting_id->invokeArgs( $authentication, array( false ) ) );

		$user_id = $this->factory->user->create( [ 'role' => 'customer' ] );
		wp_set_current_user( $user_id );
		$this->assertEquals( $user_id, $get_rate_limiting_id->invokeArgs( $authentication, array( false ) ) );

		$_SERVER['HTTP_ACCEPT_LANGUAGE'] = 'en-US,en;q=0.9';
		$_SERVER['HTTP_USER_AGENT']      = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/58.0.3029.110 Safari/537.3';

		add_filter(
			'woocommerce_store_api_rate_limit_id',
			function () {
				return wc_get_user_agent() . $_SERVER['HTTP_ACCEPT_LANGUAGE']; // @codingStandardsIgnoreLine
			}
		);

		$this->assertEquals(
			sanitize_key( wc_get_user_agent() . $_SERVER['HTTP_ACCEPT_LANGUAGE'] ), // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotValidated
			$get_rate_limiting_id->invokeArgs( $authentication, array( false ) )
		);
	}

	/**
	 * Provides test cases for is_only_post_request() method.
	 *
	 * @return array[] Test cases with REQUEST_METHOD, override header, and expected result.
	 */
	public function provide_is_only_post_request_test_cases(): array {
		return array(
			'pure POST request without override header'    => array(
				'method'   => 'POST',
				'override' => null,
				'expected' => true,
			),
			'POST request overridden to PUT via header'    => array(
				'method'   => 'POST',
				'override' => 'PUT',
				'expected' => false,
			),
			'POST request overridden to DELETE via header' => array(
				'method'   => 'POST',
				'override' => 'DELETE',
				'expected' => false,
			),
			'POST request with POST override header (redundant)' => array(
				'method'   => 'POST',
				'override' => 'POST',
				'expected' => true,
			),
			'POST request with empty override header'      => array(
				'method'   => 'POST',
				'override' => '',
				'expected' => true,
			),
			'GET request without override header'          => array(
				'method'   => 'GET',
				'override' => null,
				'expected' => false,
			),
			'GET request with POST override - method precedence' => array(
				'method'   => 'GET',
				'override' => 'POST',
				'expected' => false,
			),
			'PUT request without override header'          => array(
				'method'   => 'PUT',
				'override' => null,
				'expected' => false,
			),
			'DELETE request without override header'       => array(
				'method'   => 'DELETE',
				'override' => null,
				'expected' => false,
			),
			'PATCH request without override header'        => array(
				'method'   => 'PATCH',
				'override' => null,
				'expected' => false,
			),
			'POST request overridden to PATCH via header'  => array(
				'method'   => 'POST',
				'override' => 'PATCH',
				'expected' => false,
			),
		);
	}

	/**
	 * Tests that is_only_post_request() correctly identifies true POST requests
	 * and rejects requests with X-HTTP-Method-Override header set to another method.
	 *
	 * @dataProvider provide_is_only_post_request_test_cases
	 *
	 * @param string      $method   The REQUEST_METHOD value.
	 * @param string|null $override The X-HTTP-Method-Override header value, or null if unset.
	 * @param bool        $expected The expected return value.
	 *
	 * @return void
	 * @throws ReflectionException On failing invoked private method through reflection class.
	 */
	public function test_is_only_post_request_method( string $method, ?string $override, bool $expected ) {
		$original_server = $_SERVER;

		try {
			$authentication          = new ReflectionClass( Authentication::class );
			$is_only_post_request    = $authentication->getMethod( 'is_only_post_request' );
			$authentication_instance = $authentication->newInstance();
			$is_only_post_request->setAccessible( true );

			$_SERVER['REQUEST_METHOD'] = $method;

			if ( null === $override ) {
				unset( $_SERVER['HTTP_X_HTTP_METHOD_OVERRIDE'] );
			} else {
				$_SERVER['HTTP_X_HTTP_METHOD_OVERRIDE'] = $override;
			}

			$this->assertSame(
				$expected,
				$is_only_post_request->invoke( $authentication_instance ),
				sprintf(
					'is_only_post_request() should return %s for REQUEST_METHOD=%s with override=%s',
					$expected ? 'true' : 'false',
					$method,
					$override ?? 'null'
				)
			);
		} finally {
			$_SERVER = $original_server;
		}
	}
}
