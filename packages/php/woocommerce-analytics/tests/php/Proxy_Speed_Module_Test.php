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
 * Tests for Proxy_Speed_Module.
 *
 * maybe_serve() exits after answering, so these tests drive its private respond() instead.
 */
class Proxy_Speed_Module_Test extends BaseTestCase {

	/**
	 * Request globals as they were before the test.
	 *
	 * @var array
	 */
	private $previous_server;

	/**
	 * Fired actions as they were before the test.
	 *
	 * @var array
	 */
	private $previous_wp_actions;

	/**
	 * Start from an unauthorized module with no queued pixels.
	 */
	public function set_up(): void {
		parent::set_up();
		$this->previous_server     = $_SERVER;
		$this->previous_wp_actions = $GLOBALS['wp_actions'];
		delete_option( Woocommerce_Analytics::PROXY_SPEED_MODULE_AUTHORIZED_OPTION );
		$this->set_pixel_batch_queue( array() );
	}

	/**
	 * Restore the request globals.
	 */
	public function tear_down(): void {
		unset( $_COOKIE['wp_consent_statistics'], $_COOKIE['custom_statistics'] );
		$_SERVER               = $this->previous_server;
		$GLOBALS['wp_actions'] = $this->previous_wp_actions;
		remove_action( 'muplugins_loaded', array( Proxy_Speed_Module::class, 'maybe_serve' ) );
		remove_all_filters( 'doing_it_wrong_trigger_error' );
		remove_all_actions( 'doing_it_wrong_run' );
		$enabled = ( new \ReflectionClass( Proxy_Speed_Module::class ) )->getProperty( 'enabled' );
		$enabled->setAccessible( true );
		$enabled->setValue( null, false );
		remove_all_filters( 'wp_consent_cookie_prefix' );
		remove_all_filters( 'woocommerce_analytics_experimental_proxy_tracking_enabled' );
		delete_option( Woocommerce_Analytics::PROXY_SPEED_MODULE_AUTHORIZED_OPTION );
		delete_option( Woocommerce_Analytics::PROXY_TRACKING_EVER_ENABLED_OPTION );
		$GLOBALS['wp_rest_server'] = null;
		$this->set_pixel_batch_queue( array() );
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
	 * A renamed route must rename the module's path too, or the module silently never serves.
	 */
	public function test_the_rest_route_matches_the_module_path(): void {
		add_filter( 'woocommerce_analytics_experimental_proxy_tracking_enabled', '__return_true' );
		Woocommerce_Analytics::sync_proxy_tracking_state();
		$GLOBALS['wp_rest_server'] = null;
		Woocommerce_Analytics::register_rest_routes();

		$this->assertArrayHasKey( '/' . Proxy_Speed_Module::PROXY_REQUEST_PATH, rest_get_server()->get_routes() );
	}

	/**
	 * Test that an MU-plugin's call waits for every MU-plugin, so their consent cookie prefix applies.
	 */
	public function test_enable_from_an_mu_plugin_serves_on_muplugins_loaded(): void {
		$this->set_load_stage( 'mu-plugins' );

		Proxy_Speed_Module::enable();

		$this->assertTrue( Proxy_Speed_Module::is_enabled() );
		$this->assertSame( 10, has_action( 'muplugins_loaded', array( Proxy_Speed_Module::class, 'maybe_serve' ) ) );
	}

	/**
	 * Test that a plugin file's call serves at once, since `muplugins_loaded` has already fired.
	 */
	public function test_enable_from_a_plugin_file_serves_at_once(): void {
		$this->set_load_stage( 'plugins' );
		$this->make_request( 'GET', '/' );

		Proxy_Speed_Module::enable();

		$this->assertTrue( Proxy_Speed_Module::is_enabled() );
		$this->assertFalse( has_action( 'muplugins_loaded', array( Proxy_Speed_Module::class, 'maybe_serve' ) ) );
	}

	/**
	 * A late call cannot save anything, and enabling it would flip the stored authorization between requests.
	 */
	public function test_enable_after_plugins_loaded_is_refused(): void {
		$this->set_load_stage( 'loaded' );
		$wrong = 0;
		add_action(
			'doing_it_wrong_run',
			function () use ( &$wrong ) {
				++$wrong;
			}
		);
		add_filter( 'doing_it_wrong_trigger_error', '__return_false' );

		Proxy_Speed_Module::enable();

		$this->assertFalse( Proxy_Speed_Module::is_enabled() );
		$this->assertSame( 1, $wrong );
	}

	/**
	 * Make the load-stage actions look fired up to the given stage.
	 *
	 * @param string $stage `mu-plugins` (loading MU-plugins), `plugins` (loading plugins) or `loaded`.
	 */
	private function set_load_stage( $stage ): void {
		unset( $GLOBALS['wp_actions']['muplugins_loaded'], $GLOBALS['wp_actions']['plugins_loaded'] );
		if ( 'mu-plugins' !== $stage ) {
			$GLOBALS['wp_actions']['muplugins_loaded'] = 1;
		}
		if ( 'loaded' === $stage ) {
			$GLOBALS['wp_actions']['plugins_loaded'] = 1;
		}
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
