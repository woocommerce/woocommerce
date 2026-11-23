<?php
/**
 * Tests for the proxy speed module.
 *
 * @package automattic/woocommerce-analytics
 */

namespace Automattic\Woocommerce_Analytics;

use Automattic\Woocommerce_Analytics;
use PHPUnit\Framework\Attributes\DataProvider;
use WorDBless\BaseTestCase;

/**
 * Tests for Proxy_Speed_Module and the MU-plugin template that loads it.
 *
 * maybe_serve() exits after answering, so these tests drive its private respond() instead.
 */
class Proxy_Speed_Module_Test extends BaseTestCase {

	/**
	 * Path of the MU-plugin template.
	 *
	 * @var string
	 */
	const TEMPLATE = __DIR__ . '/../../src/mu-plugin/woocommerce-analytics-proxy-speed-module-template.php';

	/**
	 * File error_log() writes to during a test.
	 *
	 * @var string
	 */
	private $error_log_file;

	/**
	 * Previous error_log ini value.
	 *
	 * @var string|false
	 */
	private $previous_error_log;

	/**
	 * Request globals as they were before the test.
	 *
	 * @var array
	 */
	private $previous_server;

	/**
	 * Temporary files to delete after the test.
	 *
	 * @var string[]
	 */
	private $temp_files = array();

	/**
	 * Capture error_log() and start from an unauthorized module with no queued pixels.
	 */
	public function set_up(): void {
		parent::set_up();
		$this->previous_server    = $_SERVER;
		$this->error_log_file     = tempnam( sys_get_temp_dir(), 'wca-speed-module' );
		$this->previous_error_log = ini_set( 'error_log', $this->error_log_file ); // phpcs:ignore WordPress.PHP.IniSet.Risky
		delete_option( Woocommerce_Analytics::PROXY_SPEED_MODULE_AUTHORIZED_OPTION );
		$this->set_pixel_batch_queue( array() );
	}

	/**
	 * Restore the request globals and error log.
	 */
	public function tear_down(): void {
		unset( $_COOKIE['wp_consent_statistics'], $_COOKIE['custom_statistics'] );
		$_SERVER = $this->previous_server;
		remove_all_filters( 'wp_consent_cookie_prefix' );
		remove_all_filters( 'woocommerce_analytics_experimental_proxy_tracking_enabled' );
		delete_option( Woocommerce_Analytics::PROXY_SPEED_MODULE_AUTHORIZED_OPTION );
		delete_option( Woocommerce_Analytics::PROXY_TRACKING_EVER_ENABLED_OPTION );
		$GLOBALS['wp_rest_server'] = null;
		$this->set_pixel_batch_queue( array() );
		ini_set( 'error_log', (string) $this->previous_error_log ); // phpcs:ignore WordPress.PHP.IniSet.Risky
		foreach ( array_merge( $this->temp_files, array( $this->error_log_file ) ) as $file ) {
			wp_delete_file( $file );
		}
		parent::tear_down();
	}

	/**
	 * @dataProvider provide_requests_left_to_the_rest_route
	 *
	 * @param string      $method     Request method.
	 * @param string      $uri        Request URI.
	 * @param string|null $authorized Stored authorization, or null when absent.
	 * @param string|null $choice     Stored statistics choice, or null when none is stored.
	 */
	#[DataProvider( 'provide_requests_left_to_the_rest_route' )]
	public function test_leaves_the_request_to_the_rest_route( $method, $uri, $authorized, $choice ): void {
		$this->make_request( $method, $uri );
		if ( null !== $authorized ) {
			update_option( Woocommerce_Analytics::PROXY_SPEED_MODULE_AUTHORIZED_OPTION, $authorized );
		}
		if ( null !== $choice ) {
			$_COOKIE['wp_consent_statistics'] = $choice;
		}

		list( $served, $output ) = $this->respond();

		$this->assertFalse( $served );
		$this->assertSame( '', $output, 'A request left to the REST route must not be answered here.' );
	}

	/**
	 * Requests the module must not answer.
	 *
	 * @return array
	 */
	public static function provide_requests_left_to_the_rest_route(): array {
		$route = '/wp-json/woocommerce-analytics/v1/track';
		return array(
			'not a POST'                => array( 'GET', $route, 'yes', null ),
			'never authorized'          => array( 'POST', $route, null, null ),
			'authorization revoked'     => array( 'POST', $route, 'no', null ),
			'shopper denied statistics' => array( 'POST', $route, 'yes', 'deny' ),
			'another route'             => array( 'POST', '/wp-json/wp/v2/posts', 'yes', null ),
			'a longer route'            => array( 'POST', $route . 'er', 'yes', null ),
			'route in the query string' => array( 'POST', '/?rest_route=' . $route, 'yes', null ),
			'encoded path'              => array( 'POST', '/wp-json/%2e%2e' . $route, 'yes', null ),
		);
	}

	/**
	 * Test that the stored deny is read under a consent cookie prefix filtered by an earlier MU-plugin.
	 */
	public function test_leaves_a_deny_under_a_filtered_cookie_prefix_to_the_rest_route(): void {
		$this->make_request( 'POST', '/wp-json/woocommerce-analytics/v1/track' );
		update_option( Woocommerce_Analytics::PROXY_SPEED_MODULE_AUTHORIZED_OPTION, 'yes' );
		add_filter( 'wp_consent_cookie_prefix', fn() => 'custom' );
		$_COOKIE['custom_statistics'] = 'deny';

		list( $served ) = $this->respond();

		$this->assertFalse( $served );
	}

	/**
	 * The test request has no body, so an answered request is the module's empty-body error.
	 *
	 * @dataProvider provide_requests_the_module_answers
	 *
	 * @param string      $uri    Request URI.
	 * @param string|null $choice The stored statistics choice, or null when none is stored.
	 */
	#[DataProvider( 'provide_requests_the_module_answers' )]
	public function test_answers_an_authorized_proxy_request( $uri, $choice ): void {
		$this->make_request( 'POST', $uri );
		update_option( Woocommerce_Analytics::PROXY_SPEED_MODULE_AUTHORIZED_OPTION, 'yes' );
		if ( null !== $choice ) {
			$_COOKIE['wp_consent_statistics'] = $choice;
		}

		list( $served, $output ) = $this->respond();

		$this->assertTrue( $served );
		$this->assertSame(
			array(
				'success' => false,
				'error'   => 'Empty request body',
			),
			json_decode( $output, true )
		);
	}

	/**
	 * Requests the module answers.
	 *
	 * @return array
	 */
	public static function provide_requests_the_module_answers(): array {
		return array(
			'no stored choice'                     => array( '/wp-json/woocommerce-analytics/v1/track', null ),
			'stored allow'                         => array( '/wp-json/woocommerce-analytics/v1/track', 'allow' ),
			'subdirectory install, trailing slash' => array( '/shop/wp-json/woocommerce-analytics/v1/track/', null ),
		);
	}

	/**
	 * Test that answering sends the pixels queued while handling the request.
	 */
	public function test_sends_the_queued_pixels_after_answering(): void {
		$this->make_request( 'POST', '/wp-json/woocommerce-analytics/v1/track' );
		update_option( Woocommerce_Analytics::PROXY_SPEED_MODULE_AUTHORIZED_OPTION, 'yes' );
		// A closed loopback port, so the non-blocking send fails fast without leaving the machine.
		$this->set_pixel_batch_queue( array( 'http://127.0.0.1:9/t.gif' ) );

		$this->respond();

		$property = ( new \ReflectionClass( WC_Analytics_Tracking::class ) )->getProperty( 'pixel_batch_queue' );
		$property->setAccessible( true );
		$this->assertSame( array(), $property->getValue() );
	}

	/**
	 * @dataProvider provide_request_bodies
	 *
	 * @param string $body            Raw request body.
	 * @param int    $expected_status Expected status code.
	 * @param array  $expected_data   Expected subset of the response data.
	 */
	#[DataProvider( 'provide_request_bodies' )]
	public function test_responds_to_the_request_body( $body, $expected_status, $expected_data ): void {
		$method = new \ReflectionMethod( Proxy_Speed_Module::class, 'handle_events' );
		$method->setAccessible( true );

		$response = $method->invoke( null, $body );

		$this->assertSame( $expected_status, $response['status'] );
		$this->assertSame( $expected_data, array_intersect_key( $response['data'], $expected_data ) );
	}

	/**
	 * Request bodies and the responses they get.
	 *
	 * @return array
	 */
	public static function provide_request_bodies(): array {
		return array(
			'invalid JSON'      => array( '{', 400, array( 'error' => 'Invalid JSON' ) ),
			'one invalid event' => array(
				'[{"properties":{}},{"event_name":"add_to_cart"}]',
				207,
				array(
					'success'               => false,
					'is_proxy_speed_module' => true,
				),
			),
			'valid events'      => array(
				'{"event_name":"add_to_cart"}',
				200,
				array(
					'success'               => true,
					'is_proxy_speed_module' => true,
				),
			),
		);
	}

	/**
	 * @dataProvider provide_template_requests
	 *
	 * @param string $method        Request method.
	 * @param string $uri           Request URI.
	 * @param bool   $loads_package Whether the template should try to load the package.
	 */
	#[DataProvider( 'provide_template_requests' )]
	public function test_template_only_loads_the_package_for_proxy_requests( $method, $uri, $loads_package ): void {
		$this->make_request( $method, $uri );

		include self::TEMPLATE;

		// The placeholder is never replaced here, so reaching the autoloader logs an error.
		$log = (string) file_get_contents( $this->error_log_file );
		if ( $loads_package ) {
			$this->assertStringContainsString( 'Autoloader path placeholder was not replaced', $log );
		} else {
			$this->assertSame( '', $log );
		}
	}

	/**
	 * Requests the template sees.
	 *
	 * @return array
	 */
	public static function provide_template_requests(): array {
		return array(
			'proxy request'    => array( 'POST', '/wp-json/woocommerce-analytics/v1/track', true ),
			'plain permalinks' => array( 'POST', '/?rest_route=/woocommerce-analytics/v1/track', false ),
			'GET to the route' => array( 'GET', '/wp-json/woocommerce-analytics/v1/track', false ),
			'other POST'       => array( 'POST', '/wp-json/wp/v2/posts', false ),
		);
	}

	/**
	 * Test that an installed template whose autoloader moved logs and falls through instead of fataling.
	 */
	public function test_template_falls_through_when_the_autoloader_is_gone(): void {
		$this->make_request( 'POST', '/wp-json/woocommerce-analytics/v1/track' );
		$installed          = tempnam( sys_get_temp_dir(), 'wca-installed-template' );
		$this->temp_files[] = $installed;
		file_put_contents( $installed, str_replace( '{{AUTOLOADER_PATH}}', '/nonexistent/vendor/autoload.php', (string) file_get_contents( self::TEMPLATE ) ) );

		include $installed;

		$this->assertStringContainsString( 'Autoloader file not found', (string) file_get_contents( $this->error_log_file ) );
	}

	/**
	 * The template matches the route before it can load the constant, so a renamed route must update both.
	 */
	public function test_the_template_and_the_rest_route_match_the_module_path(): void {
		add_filter( 'woocommerce_analytics_experimental_proxy_tracking_enabled', '__return_true' );
		Woocommerce_Analytics::sync_proxy_tracking_state();
		$GLOBALS['wp_rest_server'] = null;
		Woocommerce_Analytics::register_rest_routes();

		$this->assertArrayHasKey( '/' . Proxy_Speed_Module::PROXY_REQUEST_PATH, rest_get_server()->get_routes() );
		$this->assertStringContainsString( "'" . Proxy_Speed_Module::PROXY_REQUEST_PATH . "'", (string) file_get_contents( self::TEMPLATE ) );
	}

	/**
	 * Make the current request look like the given request.
	 *
	 * @param string $method Request method.
	 * @param string $uri    Request URI.
	 */
	private function make_request( $method, $uri ): void {
		$_SERVER['REQUEST_METHOD'] = $method;
		$_SERVER['REQUEST_URI']    = $uri;
	}

	/**
	 * Run the module's respond step and capture its output.
	 *
	 * @return array{0: bool, 1: string} Whether it answered, and what it printed.
	 */
	private function respond(): array {
		$method = new \ReflectionMethod( Proxy_Speed_Module::class, 'respond' );
		$method->setAccessible( true );

		ob_start();
		$served = $method->invoke( null );
		$output = (string) ob_get_clean();

		return array( $served, $output );
	}

	/**
	 * Replace the queued pixel URLs.
	 *
	 * @param array $queue Pixel URLs.
	 */
	private function set_pixel_batch_queue( array $queue ): void {
		$property = ( new \ReflectionClass( WC_Analytics_Tracking::class ) )->getProperty( 'pixel_batch_queue' );
		$property->setAccessible( true );
		$property->setValue( null, $queue );
	}
}
