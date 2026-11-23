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
 */
class Proxy_Speed_Module_Test extends BaseTestCase {

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
	 * Capture error_log() and start from an unauthorized module with no queued pixels.
	 */
	public function set_up(): void {
		parent::set_up();
		$this->previous_server    = $_SERVER;
		$this->error_log_file     = tempnam( sys_get_temp_dir(), 'wca-speed-module' );
		$this->previous_error_log = ini_set( 'error_log', $this->error_log_file ); // phpcs:ignore WordPress.PHP.IniSet.Risky
		delete_option( Woocommerce_Analytics::PROXY_SPEED_MODULE_AUTHORIZED_OPTION );
		$this->reset_tracking_state();
	}

	/**
	 * Restore the request globals and error log.
	 */
	public function tear_down(): void {
		unset( $_COOKIE['wp_consent_statistics'], $_COOKIE['custom_statistics'], $_COOKIE['tk_ai'] );
		$_SERVER = $this->previous_server;
		remove_all_filters( 'wp_consent_cookie_prefix' );
		delete_option( Woocommerce_Analytics::PROXY_SPEED_MODULE_AUTHORIZED_OPTION );
		delete_option( 'woocommerce_store_id' );
		$this->reset_tracking_state();
		ini_set( 'error_log', (string) $this->previous_error_log ); // phpcs:ignore WordPress.PHP.IniSet.Risky
		wp_delete_file( $this->error_log_file );
		parent::tear_down();
	}

	/**
	 * @dataProvider provide_requests_left_to_the_rest_route
	 *
	 * @param string      $method     Request method.
	 * @param string|null $authorized Stored authorization, or null when absent.
	 * @param string|null $choice     Stored statistics choice, or null when none is stored.
	 */
	#[DataProvider( 'provide_requests_left_to_the_rest_route' )]
	public function test_leaves_the_request_to_the_rest_route( $method, $authorized, $choice ): void {
		$this->make_proxy_request( $method );
		if ( null !== $authorized ) {
			update_option( Woocommerce_Analytics::PROXY_SPEED_MODULE_AUTHORIZED_OPTION, $authorized );
		}
		if ( null !== $choice ) {
			$_COOKIE['wp_consent_statistics'] = $choice;
		}

		ob_start();
		$served = Proxy_Speed_Module::maybe_serve();
		$output = ob_get_clean();

		$this->assertFalse( $served );
		$this->assertSame( '', $output, 'A request left to the REST route must not be answered here.' );
	}

	/**
	 * Requests the module must not answer.
	 *
	 * @return array
	 */
	public static function provide_requests_left_to_the_rest_route(): array {
		return array(
			'not a POST'                => array( 'GET', 'yes', null ),
			'never authorized'          => array( 'POST', null, null ),
			'authorization revoked'     => array( 'POST', 'no', null ),
			'shopper denied statistics' => array( 'POST', 'yes', 'deny' ),
		);
	}

	/**
	 * Test that the stored deny is read under a consent cookie prefix filtered by an earlier MU-plugin.
	 */
	public function test_leaves_a_deny_under_a_filtered_cookie_prefix_to_the_rest_route(): void {
		$this->make_proxy_request();
		update_option( Woocommerce_Analytics::PROXY_SPEED_MODULE_AUTHORIZED_OPTION, 'yes' );
		add_filter( 'wp_consent_cookie_prefix', fn() => 'custom' );
		$_COOKIE['custom_statistics'] = 'deny';

		ob_start();
		$served = Proxy_Speed_Module::maybe_serve();
		ob_end_clean();

		$this->assertFalse( $served );
	}

	/**
	 * The test request has no body, so an answered request is the module's empty-body error.
	 *
	 * @dataProvider provide_choices_the_module_still_serves
	 *
	 * @param string|null $choice The stored statistics choice, or null when none is stored.
	 */
	#[DataProvider( 'provide_choices_the_module_still_serves' )]
	public function test_answers_an_authorized_proxy_request( $choice ): void {
		$this->make_proxy_request();
		update_option( Woocommerce_Analytics::PROXY_SPEED_MODULE_AUTHORIZED_OPTION, 'yes' );
		if ( null !== $choice ) {
			$_COOKIE['wp_consent_statistics'] = $choice;
		}

		ob_start();
		$served = Proxy_Speed_Module::maybe_serve();
		$output = ob_get_clean();

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
	 * Stored choices that do not deny statistics.
	 *
	 * @return array
	 */
	public static function provide_choices_the_module_still_serves(): array {
		return array(
			'no stored choice' => array( null ),
			'stored allow'     => array( 'allow' ),
		);
	}

	/**
	 * Client events go through the untrusted entry point, so a forged server-owned property is replaced.
	 */
	public function test_replaces_forged_server_owned_properties(): void {
		$_COOKIE['tk_ai']       = 'test-visitor-id-1234567890ab';
		$_SERVER['REMOTE_ADDR'] = '203.0.113.7';
		update_option( 'woocommerce_store_id', 'real-store-id' );

		$response = Proxy_Speed_Module::handle_events(
			wp_json_encode(
				array(
					'event_name' => 'add_to_cart',
					'properties' => array(
						'store_id' => 'someone-elses-store',
						'pi'       => 42,
					),
				)
			)
		);

		$this->assertSame( 200, $response['status'] );
		$queue = $this->get_pixel_batch_queue();
		$this->assertCount( 1, $queue );
		parse_str( (string) wp_parse_url( $queue[0], PHP_URL_QUERY ), $props );
		$this->assertSame( 'real-store-id', $props['store_id'] ?? null );
		$this->assertSame( '42', $props['pi'] ?? null );
	}

	/**
	 * Test that the batch is capped like the REST route's.
	 */
	public function test_caps_the_batch_at_the_client_event_limit(): void {
		$events = array_fill(
			0,
			WC_Analytics_Tracking::MAX_CLIENT_EVENTS_PER_REQUEST + 1,
			array( 'event_name' => 'add_to_cart' )
		);

		$response = Proxy_Speed_Module::handle_events( wp_json_encode( $events ) );

		$this->assertCount( WC_Analytics_Tracking::MAX_CLIENT_EVENTS_PER_REQUEST, $response['data']['results'] );
	}

	/**
	 * The placeholder is never replaced here, so reaching the autoloader logs an error.
	 *
	 * @dataProvider provide_template_requests
	 *
	 * @param string $method        Request method.
	 * @param string $uri           Request URI.
	 * @param bool   $loads_package Whether the template should try to load the package.
	 */
	#[DataProvider( 'provide_template_requests' )]
	public function test_template_only_loads_the_package_for_proxy_requests( $method, $uri, $loads_package ): void {
		$_SERVER['REQUEST_METHOD'] = $method;
		$_SERVER['REQUEST_URI']    = $uri;

		include dirname( __DIR__, 2 ) . '/src/mu-plugin/woocommerce-analytics-proxy-speed-module-template.php';

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
			'plain permalinks' => array( 'POST', '/?rest_route=/woocommerce-analytics/v1/track', true ),
			'GET to the route' => array( 'GET', '/wp-json/woocommerce-analytics/v1/track', false ),
			'other POST'       => array( 'POST', '/wp-json/wp/v2/posts', false ),
		);
	}

	/**
	 * Make the current request look like a proxy tracking request.
	 *
	 * @param string $method Request method.
	 */
	private function make_proxy_request( $method = 'POST' ): void {
		$_SERVER['REQUEST_METHOD'] = $method;
		$_SERVER['REQUEST_URI']    = '/wp-json/woocommerce-analytics/v1/track';
	}

	/**
	 * Read the queued pixel URLs.
	 *
	 * @return array
	 */
	private function get_pixel_batch_queue(): array {
		$property = ( new \ReflectionClass( WC_Analytics_Tracking::class ) )->getProperty( 'pixel_batch_queue' );
		$property->setAccessible( true );
		return $property->getValue();
	}

	/**
	 * Clear the queued pixels and the per-request memos that would leak between tests.
	 */
	private function reset_tracking_state(): void {
		$reflection = new \ReflectionClass( WC_Analytics_Tracking::class );
		$defaults   = array(
			'pixel_batch_queue'       => array(),
			'cached_ip'               => null,
			'reserved_property_names' => null,
			'cached_visitor_id'       => null,
		);
		foreach ( $defaults as $name => $value ) {
			$property = $reflection->getProperty( $name );
			$property->setAccessible( true );
			$property->setValue( null, $value );
		}
	}
}
