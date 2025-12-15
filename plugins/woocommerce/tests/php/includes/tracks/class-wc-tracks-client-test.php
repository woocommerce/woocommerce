<?php
declare(strict_types=1);

/**
 * Class WC_Tracks_Client_Test.
 */
class WC_Tracks_Client_Test extends \WC_Unit_Test_Case {

	/**
	 * Array to track intercepted HTTP requests.
	 *
	 * @var array
	 */
	private $intercepted_requests = array();

	/**
	 * Set up test.
	 *
	 * @return void
	 */
	public function setUp(): void {
		parent::setUp();
		include_once WC_ABSPATH . 'includes/tracks/class-wc-tracks.php';
		include_once WC_ABSPATH . 'includes/tracks/class-wc-tracks-client.php';
		include_once WC_ABSPATH . 'includes/tracks/class-wc-tracks-event.php';

		// Clear any existing batch queue and hooks.
		$this->reset_batch_state();

		// Clear intercepted requests.
		$this->intercepted_requests = array();

		// Intercept HTTP requests to prevent actual network calls.
		add_filter( 'pre_http_request', array( $this, 'intercept_http_requests' ), 10, 3 );
	}

	/**
	 * Tear down test.
	 *
	 * @return void
	 */
	public function tearDown(): void {
		$this->reset_batch_state();
		remove_filter( 'pre_http_request', array( $this, 'intercept_http_requests' ), 10 );
		parent::tearDown();
	}

	/**
	 * Intercept HTTP requests to prevent actual network calls during testing.
	 *
	 * @param false|array|WP_Error $response    A preemptive return value of an HTTP request.
	 * @param array                $parsed_args HTTP request arguments.
	 * @param string               $url         The request URL.
	 * @return array Mocked HTTP response.
	 */
	public function intercept_http_requests( $response, $parsed_args, $url ) {
		// Track the intercepted request.
		if ( strpos( $url, 'pixel.wp.com/t.gif' ) !== false ) {
			$this->intercepted_requests[] = array(
				'url'  => $url,
				'args' => $parsed_args,
			);

			// Return a successful mock response.
			return array(
				'response' => array(
					'code'    => 200,
					'message' => 'OK',
				),
				'body'     => '',
			);
		}

		return $response;
	}

	/**
	 * Reset the batch state using reflection.
	 *
	 * @return void
	 */
	private function reset_batch_state() {
		$reflection = new ReflectionClass( 'WC_Tracks_Client' );

		$queue_property = $reflection->getProperty( 'pixel_batch_queue' );
		$queue_property->setAccessible( true );
		$queue_property->setValue( array() );

		$hook_property = $reflection->getProperty( 'shutdown_hook_registered' );
		$hook_property->setAccessible( true );
		$hook_property->setValue( false );

		// Remove shutdown hook if it exists.
		remove_action( 'shutdown', array( 'WC_Tracks_Client', 'send_batched_pixels' ), 20 );
	}

	/**
	 * Get the pixel batch queue using reflection.
	 *
	 * @return array
	 */
	private function get_batch_queue() {
		$reflection = new ReflectionClass( 'WC_Tracks_Client' );
		$property   = $reflection->getProperty( 'pixel_batch_queue' );
		$property->setAccessible( true );
		return $property->getValue();
	}

	/**
	 * Test that batch requests are supported when Requests library is available.
	 */
	public function test_can_use_batch_requests_when_supported() {
		$reflection = new ReflectionClass( 'WC_Tracks_Client' );
		$method     = $reflection->getMethod( 'can_use_batch_requests' );
		$method->setAccessible( true );

		$can_batch = $method->invoke( null );

		// Should return true if either WpOrg\Requests\Requests or Requests class exists with request_multiple method.
		if ( class_exists( 'WpOrg\Requests\Requests' ) || class_exists( 'Requests' ) ) {
			$this->assertTrue( $can_batch );
		} else {
			$this->assertFalse( $can_batch );
		}
	}

	/**
	 * Test that pixels are queued when batching is enabled.
	 */
	public function test_record_pixel_queues_pixel_when_batching_enabled() {
		// Ensure batching is supported.
		if ( ! class_exists( 'WpOrg\Requests\Requests' ) && ! class_exists( 'Requests' ) ) {
			$this->markTestSkipped( 'Requests library not available for batching.' );
		}

		$pixel = 'https://pixel.wp.com/t.gif?_en=test_event&test=value';

		// Record the pixel.
		WC_Tracks_Client::record_pixel( $pixel );

		// Get the batch queue.
		$queue = $this->get_batch_queue();

		// Pixel should be in the queue (without timestamp/nocache since that's added on send).
		$this->assertCount( 1, $queue );
		$this->assertEquals( $pixel, $queue[0] );
	}

	/**
	 * Test that multiple pixels are queued correctly.
	 */
	public function test_record_multiple_pixels_queues_all() {
		// Ensure batching is supported.
		if ( ! class_exists( 'WpOrg\Requests\Requests' ) && ! class_exists( 'Requests' ) ) {
			$this->markTestSkipped( 'Requests library not available for batching.' );
		}

		$pixel1 = 'https://pixel.wp.com/t.gif?_en=event1';
		$pixel2 = 'https://pixel.wp.com/t.gif?_en=event2';
		$pixel3 = 'https://pixel.wp.com/t.gif?_en=event3';

		WC_Tracks_Client::record_pixel( $pixel1 );
		WC_Tracks_Client::record_pixel( $pixel2 );
		WC_Tracks_Client::record_pixel( $pixel3 );

		$queue = $this->get_batch_queue();

		$this->assertCount( 3, $queue );
		$this->assertEquals( $pixel1, $queue[0] );
		$this->assertEquals( $pixel2, $queue[1] );
		$this->assertEquals( $pixel3, $queue[2] );
	}

	/**
	 * Test that shutdown hook is registered when pixels are queued.
	 */
	public function test_shutdown_hook_registered_when_pixel_queued() {
		// Ensure batching is supported.
		if ( ! class_exists( 'WpOrg\Requests\Requests' ) && ! class_exists( 'Requests' ) ) {
			$this->markTestSkipped( 'Requests library not available for batching.' );
		}

		$pixel = 'https://pixel.wp.com/t.gif?_en=test_event';

		// Record the pixel.
		WC_Tracks_Client::record_pixel( $pixel );

		// Check if the shutdown hook is registered.
		$this->assertNotFalse(
			has_action( 'shutdown', array( 'WC_Tracks_Client', 'send_batched_pixels' ) ),
			'Shutdown hook should be registered after queuing a pixel.'
		);
	}

	/**
	 * Test that shutdown hook is only registered once.
	 */
	public function test_shutdown_hook_registered_only_once() {
		// Ensure batching is supported.
		if ( ! class_exists( 'WpOrg\Requests\Requests' ) && ! class_exists( 'Requests' ) ) {
			$this->markTestSkipped( 'Requests library not available for batching.' );
		}

		WC_Tracks_Client::record_pixel( 'https://pixel.wp.com/t.gif?_en=event1' );
		WC_Tracks_Client::record_pixel( 'https://pixel.wp.com/t.gif?_en=event2' );

		// Get the priority value (should be 20).
		$priority = has_action( 'shutdown', array( 'WC_Tracks_Client', 'send_batched_pixels' ) );

		$this->assertEquals( 20, $priority, 'Shutdown hook should be registered with priority 20.' );

		// Count how many times the hook is registered.
		global $wp_filter;
		$hook_count = 0;
		if ( isset( $wp_filter['shutdown'] ) && isset( $wp_filter['shutdown'][20] ) ) {
			foreach ( $wp_filter['shutdown'][20] as $hook ) {
				if ( is_array( $hook['function'] ) && 'WC_Tracks_Client' === $hook['function'][0] && 'send_batched_pixels' === $hook['function'][1] ) {
					++$hook_count;
				}
			}
		}

		$this->assertEquals( 1, $hook_count, 'Shutdown hook should only be registered once.' );
	}

	/**
	 * Test that batched pixels are sent with timestamp and nocache parameters.
	 */
	public function test_send_batched_pixels_adds_timestamp_and_nocache() {
		// Ensure batching is supported.
		if ( ! class_exists( 'WpOrg\Requests\Requests' ) && ! class_exists( 'Requests' ) ) {
			$this->markTestSkipped( 'Requests library not available for batching.' );
		}

		$pixel = 'https://pixel.wp.com/t.gif?_en=test_event';

		// Queue a pixel.
		WC_Tracks_Client::record_pixel( $pixel );

		// Clear intercepted requests before sending.
		$this->intercepted_requests = array();

		// Send the batched pixels.
		WC_Tracks_Client::send_batched_pixels();

		// Verify that an HTTP request was intercepted.
		$this->assertNotEmpty( $this->intercepted_requests, 'HTTP request should have been intercepted.' );

		// Verify the request URL contains timestamp and nocache parameters.
		$intercepted_url = $this->intercepted_requests[0]['url'];
		$this->assertStringContainsString( '_rt=', $intercepted_url, 'Request should contain timestamp parameter.' );
		$this->assertStringContainsString( '_=_', $intercepted_url, 'Request should contain nocache parameter.' );

		// Queue should be empty after sending.
		$queue = $this->get_batch_queue();
		$this->assertEmpty( $queue, 'Queue should be empty after sending batched pixels.' );
	}

	/**
	 * Test that send_batched_pixels does nothing when queue is empty.
	 */
	public function test_send_batched_pixels_does_nothing_when_queue_empty() {
		$this->reset_batch_state();

		// Call send_batched_pixels with empty queue.
		WC_Tracks_Client::send_batched_pixels();

		// Should not cause any errors and queue should remain empty.
		$queue = $this->get_batch_queue();
		$this->assertEmpty( $queue );
	}

	/**
	 * Test the wc_tracks_use_batch_requests filter to disable batching.
	 */
	public function test_filter_can_disable_batching() {
		// Ensure batching is supported.
		if ( ! class_exists( 'WpOrg\Requests\Requests' ) && ! class_exists( 'Requests' ) ) {
			$this->markTestSkipped( 'Requests library not available for batching.' );
		}

		// Add filter to disable batching.
		add_filter( 'wc_tracks_use_batch_requests', '__return_false' );

		$pixel = 'https://pixel.wp.com/t.gif?_en=test_event';

		// Record the pixel.
		WC_Tracks_Client::record_pixel( $pixel );

		// Queue should be empty since batching is disabled.
		$queue = $this->get_batch_queue();
		$this->assertEmpty( $queue, 'Queue should be empty when batching is disabled via filter.' );

		// Clean up filter.
		remove_filter( 'wc_tracks_use_batch_requests', '__return_false' );
	}

	/**
	 * Test the wc_tracks_use_batch_requests filter to enable batching.
	 */
	public function test_filter_can_enable_batching() {
		// Ensure batching is supported.
		if ( ! class_exists( 'WpOrg\Requests\Requests' ) && ! class_exists( 'Requests' ) ) {
			$this->markTestSkipped( 'Requests library not available for batching.' );
		}

		// Add filter to explicitly enable batching.
		add_filter( 'wc_tracks_use_batch_requests', '__return_true' );

		$pixel = 'https://pixel.wp.com/t.gif?_en=test_event';

		// Record the pixel.
		WC_Tracks_Client::record_pixel( $pixel );

		// Queue should have the pixel.
		$queue = $this->get_batch_queue();
		$this->assertCount( 1, $queue );

		// Clean up filter.
		remove_filter( 'wc_tracks_use_batch_requests', '__return_true' );
	}

	/**
	 * Test that timestamp is generated correctly.
	 */
	public function test_build_timestamp() {
		$timestamp = WC_Tracks_Client::build_timestamp();

		// Should be a numeric string.
		$this->assertIsString( $timestamp );
		$this->assertMatchesRegularExpression( '/^\d+$/', $timestamp );

		// Should be approximately current time in milliseconds (13 digits).
		$this->assertGreaterThanOrEqual( 13, strlen( $timestamp ) );
	}

	/**
	 * Test that request timestamp and nocache are added to pixel.
	 */
	public function test_add_request_timestamp_and_nocache() {
		$pixel = 'https://pixel.wp.com/t.gif?_en=test_event';

		$pixel_with_params = WC_Tracks_Client::add_request_timestamp_and_nocache( $pixel );

		// Should contain _rt parameter.
		$this->assertStringContainsString( '&_rt=', $pixel_with_params );

		// Should contain _=_ terminator.
		$this->assertStringContainsString( '&_=_', $pixel_with_params );

		// Parse URL to verify parameters.
		$parsed_url = wp_parse_url( $pixel_with_params );
		parse_str( $parsed_url['query'], $query_params );

		$this->assertArrayHasKey( '_en', $query_params );
		$this->assertEquals( 'test_event', $query_params['_en'] );
		$this->assertArrayHasKey( '_rt', $query_params );
		$this->assertArrayHasKey( '_', $query_params );
		$this->assertEquals( '_', $query_params['_'] );
	}

	/**
	 * Test record_event with batching.
	 */
	public function test_record_event_uses_batching() {
		// Ensure batching is supported.
		if ( ! class_exists( 'WpOrg\Requests\Requests' ) && ! class_exists( 'Requests' ) ) {
			$this->markTestSkipped( 'Requests library not available for batching.' );
		}

		$event_props = array(
			'_en'       => 'test_event',
			'_ts'       => WC_Tracks_Client::build_timestamp(),
			'test_prop' => 'test_value',
		);

		$event = new \WC_Tracks_Event( $event_props );

		// Record the event.
		WC_Tracks_Client::record_event( $event );

		// Queue should have one pixel.
		$queue = $this->get_batch_queue();
		$this->assertCount( 1, $queue );

		// The queued pixel should contain the event name.
		$this->assertStringContainsString( '_en=test_event', $queue[0] );
		$this->assertStringContainsString( 'test_prop=test_value', $queue[0] );
	}

	/**
	 * Test that batching works with multiple events.
	 */
	public function test_batch_multiple_events() {
		// Ensure batching is supported.
		if ( ! class_exists( 'WpOrg\Requests\Requests' ) && ! class_exists( 'Requests' ) ) {
			$this->markTestSkipped( 'Requests library not available for batching.' );
		}

		// Create multiple events.
		for ( $i = 1; $i <= 5; $i++ ) {
			$event_props = array(
				'_en'    => "test_event_$i",
				'_ts'    => WC_Tracks_Client::build_timestamp(),
				'number' => $i,
			);

			$event = new \WC_Tracks_Event( $event_props );
			WC_Tracks_Client::record_event( $event );
		}

		// Queue should have 5 pixels.
		$queue = $this->get_batch_queue();
		$this->assertCount( 5, $queue );

		// Verify each event is in the queue.
		for ( $i = 1; $i <= 5; $i++ ) {
			$this->assertStringContainsString( "_en=test_event_$i", $queue[ $i - 1 ] );
		}
	}

	/**
	 * Test that send_with_requests_multiple can be called with an empty array.
	 */
	public function test_send_with_requests_multiple_handles_empty_array() {
		$reflection = new ReflectionClass( 'WC_Tracks_Client' );
		$method     = $reflection->getMethod( 'send_with_requests_multiple' );
		$method->setAccessible( true );

		// Call with empty array - should not cause any issues.
		$method->invoke( null, array() );

		// Explicit assertion that no exception was thrown.
		$this->addToAssertionCount( 1 );
	}

	/**
	 * Test that queue is cleared after sending.
	 */
	public function test_queue_cleared_after_sending() {
		// Ensure batching is supported.
		if ( ! class_exists( 'WpOrg\Requests\Requests' ) && ! class_exists( 'Requests' ) ) {
			$this->markTestSkipped( 'Requests library not available for batching.' );
		}

		// Queue multiple pixels.
		WC_Tracks_Client::record_pixel( 'https://pixel.wp.com/t.gif?_en=event1' );
		WC_Tracks_Client::record_pixel( 'https://pixel.wp.com/t.gif?_en=event2' );
		WC_Tracks_Client::record_pixel( 'https://pixel.wp.com/t.gif?_en=event3' );

		$queue = $this->get_batch_queue();
		$this->assertCount( 3, $queue );

		// Send batched pixels.
		WC_Tracks_Client::send_batched_pixels();

		// Queue should be empty.
		$queue = $this->get_batch_queue();
		$this->assertEmpty( $queue );
	}

	/**
	 * Test that HTTP requests are intercepted and not actually sent.
	 */
	public function test_http_requests_are_intercepted() {
		// Ensure batching is supported.
		if ( ! class_exists( 'WpOrg\Requests\Requests' ) && ! class_exists( 'Requests' ) ) {
			$this->markTestSkipped( 'Requests library not available for batching.' );
		}

		// Clear intercepted requests.
		$this->intercepted_requests = array();

		// Record multiple pixels.
		WC_Tracks_Client::record_pixel( 'https://pixel.wp.com/t.gif?_en=event1&prop=value1' );
		WC_Tracks_Client::record_pixel( 'https://pixel.wp.com/t.gif?_en=event2&prop=value2' );

		// Send batched pixels.
		WC_Tracks_Client::send_batched_pixels();

		// Verify that HTTP requests were intercepted.
		$this->assertCount( 2, $this->intercepted_requests, 'Should have intercepted 2 HTTP requests.' );

		// Verify the intercepted requests contain the correct event names.
		$this->assertStringContainsString( '_en=event1', $this->intercepted_requests[0]['url'] );
		$this->assertStringContainsString( '_en=event2', $this->intercepted_requests[1]['url'] );

		// Verify the requests are non-blocking.
		$this->assertFalse( $this->intercepted_requests[0]['args']['blocking'], 'Requests should be non-blocking.' );
		$this->assertFalse( $this->intercepted_requests[1]['args']['blocking'], 'Requests should be non-blocking.' );
	}

	/**
	 * Test that non-batched requests are also intercepted.
	 */
	public function test_fallback_requests_are_intercepted() {
		// Disable batching via filter.
		add_filter( 'wc_tracks_use_batch_requests', '__return_false' );

		// Clear intercepted requests.
		$this->intercepted_requests = array();

		// Record a pixel (should use fallback method).
		WC_Tracks_Client::record_pixel( 'https://pixel.wp.com/t.gif?_en=fallback_event' );

		// Verify that HTTP request was intercepted.
		$this->assertCount( 1, $this->intercepted_requests, 'Should have intercepted 1 HTTP request.' );
		$this->assertStringContainsString( '_en=fallback_event', $this->intercepted_requests[0]['url'] );

		// Clean up filter.
		remove_filter( 'wc_tracks_use_batch_requests', '__return_false' );
	}
}
