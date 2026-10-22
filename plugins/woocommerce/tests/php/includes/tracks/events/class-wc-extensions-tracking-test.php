<?php
declare( strict_types = 1 );

/**
 * Tests for the WC_Extensions_Tracking class.
 */
class WC_Extensions_Tracking_Test extends \WC_Unit_Test_Case {

	/**
	 * The System Under Test.
	 *
	 * @var WC_Extensions_Tracking
	 */
	private $sut;

	/**
	 * Properties of the recorded Tracks events, keyed by event name.
	 *
	 * @var array<string, array>
	 */
	private $recorded = array();

	/**
	 * $_GET as it was before the test, since the WordPress test case doesn't restore it.
	 *
	 * @var array
	 */
	private $original_get = array();

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->original_get = $_GET; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		include_once WC_ABSPATH . 'includes/tracks/events/class-wc-extensions-tracking.php';
		$this->sut = new WC_Extensions_Tracking();

		update_option( 'woocommerce_allow_tracking', 'yes' );
		// WC_Tracks skips events for the test suite's own users.
		wp_set_current_user( 0 );

		$this->recorded = array();
		add_filter( 'woocommerce_tracks_event_properties', array( $this, 'record_event_properties' ), 10, 2 );
	}

	/**
	 * Capture the properties of each recorded event.
	 *
	 * @param array  $properties Event properties.
	 * @param string $event_name Event name.
	 * @return array
	 */
	public function record_event_properties( $properties, $event_name ) {
		$this->recorded[ $event_name ] = $properties;
		return $properties;
	}

	/**
	 * Tear down test fixtures.
	 */
	public function tearDown(): void {
		$_GET = $this->original_get;
		remove_filter( 'woocommerce_tracks_event_properties', array( $this, 'record_event_properties' ), 10 );
		parent::tearDown();
	}

	/**
	 * @testdox The connect event should carry the funnel id from the hook, and omit it when it is missing or invalid.
	 *
	 * @testWith ["0a5b6c1e-2d3f-4a5b-8c9d-0e1f2a3b4c5d", "0a5b6c1e-2d3f-4a5b-8c9d-0e1f2a3b4c5d"]
	 *           ["not valid!", null]
	 *
	 * @param string      $funnel_id Funnel id passed to the hook.
	 * @param string|null $expected  Recorded funnel_id property, null when absent.
	 */
	public function test_connect_event_records_funnel_id_from_hook( string $funnel_id, ?string $expected ): void {
		$this->sut->track_helper_connection_start( $funnel_id );

		$this->assertArrayHasKey( 'wcadmin_extensions_subscriptions_connect', $this->recorded, 'The connect event should be recorded' );
		$this->assertSame( $expected, $this->recorded['wcadmin_extensions_subscriptions_connect']['funnel_id'] ?? null );
	}

	/**
	 * @testdox The connected and cancelled events should carry a valid funnel id from the return URL.
	 */
	public function test_return_events_record_funnel_id_from_request(): void {
		$_GET = array(
			'funnel_id'  => '0a5b6c1e-2d3f-4a5b-8c9d-0e1f2a3b4c5d',
			'utm_source' => 'pu',
		);

		$this->sut->track_helper_connection_complete();
		$this->sut->track_helper_connection_cancelled();

		$connected = $this->recorded['wcadmin_extensions_subscriptions_connected'] ?? array();
		$this->assertSame( '0a5b6c1e-2d3f-4a5b-8c9d-0e1f2a3b4c5d', $connected['funnel_id'] ?? null, 'The connected event should carry the funnel id' );
		$this->assertSame( 'pu', $connected['utm_source'] ?? null, 'The connected event should keep its utm properties' );
		$this->assertSame( '0a5b6c1e-2d3f-4a5b-8c9d-0e1f2a3b4c5d', $this->recorded['wcadmin_extensions_subscriptions_cancelled']['funnel_id'] ?? null, 'The cancelled event should carry the funnel id' );
	}
}
