<?php
/**
 * WCTracksObservabilityHandlerTest class file.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Internal\MCP;

use Automattic\WooCommerce\Internal\MCP\WCTracksObservabilityHandler;
use WP\MCP\Infrastructure\Observability\Contracts\McpObservabilityHandlerInterface;

/**
 * Tests for the WCTracksObservabilityHandler class.
 */
class WCTracksObservabilityHandlerTest extends \WC_Unit_Test_Case {

	/**
	 * The system under test.
	 *
	 * @var WCTracksObservabilityHandler
	 */
	private $sut;

	/**
	 * Captured track events for verification.
	 *
	 * @var array
	 */
	private $captured_events = array();

	/**
	 * Original tracking option value.
	 *
	 * @var string
	 */
	private $original_tracking_option;

	/**
	 * Set up before each test.
	 */
	public function setUp(): void {
		parent::setUp();

		// Bootstrap the MCP Adapter for tests.
		if ( ! interface_exists( McpObservabilityHandlerInterface::class ) ) {
			$mcp_bootstrap = WP_PLUGIN_DIR . '/woocommerce/vendor/wordpress/mcp-adapter/includes/Autoloader.php';
			if ( file_exists( $mcp_bootstrap ) ) {
				require_once $mcp_bootstrap;
				if ( class_exists( 'WP\\MCP\\Autoloader' ) ) {
					\WP\MCP\Autoloader::autoload();
				}
			}
		}

		// Store original tracking option.
		$this->original_tracking_option = get_option( 'woocommerce_allow_tracking', 'no' );

		// Reset captured events.
		$this->captured_events = array();

		// Hook into WC_Tracks to capture events.
		add_filter( 'woocommerce_tracks_event_properties', array( $this, 'capture_track_event' ), 10, 2 );

		$this->sut = new WCTracksObservabilityHandler();
	}

	/**
	 * Clean up after each test.
	 */
	public function tearDown(): void {
		// Restore original tracking option.
		update_option( 'woocommerce_allow_tracking', $this->original_tracking_option );

		// Remove filter.
		remove_filter( 'woocommerce_tracks_event_properties', array( $this, 'capture_track_event' ), 10 );

		parent::tearDown();
	}

	/**
	 * Capture track events for verification.
	 *
	 * @param array  $properties Event properties.
	 * @param string $event_name Event name.
	 * @return array
	 */
	public function capture_track_event( array $properties, string $event_name ): array {
		$this->captured_events[] = array(
			'event_name' => $event_name,
			'properties' => $properties,
		);
		return $properties;
	}

	/**
	 * Test that class implements the MCP observability interface.
	 */
	public function test_implements_mcp_observability_interface(): void {
		$this->assertInstanceOf(
			McpObservabilityHandlerInterface::class,
			$this->sut,
			'Should implement McpObservabilityHandlerInterface'
		);
	}

	/**
	 * Test that no tracking occurs when tracking is disabled.
	 */
	public function test_does_not_track_when_tracking_disabled(): void {
		// Disable tracking.
		update_option( 'woocommerce_allow_tracking', 'no' );

		$tags = array(
			'method' => 'tools/call',
			'params' => array(
				'name'           => 'woocommerce-create-product',
				'arguments_keys' => array( 'name', 'price' ),
			),
			'status' => 'success',
		);

		$this->sut->record_event( 'mcp.request', $tags, 100.0 );

		$this->assertEmpty( $this->captured_events, 'Should not track when tracking is disabled' );
	}

	/**
	 * Test that tracking occurs when tracking is enabled.
	 */
	public function test_tracks_tool_call_when_tracking_enabled(): void {
		// Enable tracking.
		update_option( 'woocommerce_allow_tracking', 'yes' );

		$tags = array(
			'method' => 'tools/call',
			'params' => array(
				'name'           => 'woocommerce-create-product',
				'arguments_keys' => array( 'name', 'price' ),
			),
			'status' => 'success',
		);

		$this->sut->record_event( 'mcp.request', $tags, 100.0 );

		$this->assertNotEmpty( $this->captured_events, 'Should track when tracking is enabled' );
	}

	/**
	 * Test that tool name is extracted from params.
	 */
	public function test_extracts_tool_name_from_params(): void {
		update_option( 'woocommerce_allow_tracking', 'yes' );

		$tags = array(
			'method' => 'tools/call',
			'params' => array(
				'name'           => 'woocommerce-create-product',
				'arguments_keys' => array(),
			),
			'status' => 'success',
		);

		$this->sut->record_event( 'mcp.request', $tags, 100.0 );

		$this->assertNotEmpty( $this->captured_events );
		$event = $this->captured_events[0];
		$this->assertArrayHasKey( 'tool_name', $event['properties'] );
		$this->assertSame( 'woocommerce_create_product', $event['properties']['tool_name'] );
	}

	/**
	 * Test that parameter fields are extracted from arguments_keys.
	 */
	public function test_extracts_parameter_fields_from_arguments_keys(): void {
		update_option( 'woocommerce_allow_tracking', 'yes' );

		$tags = array(
			'method' => 'tools/call',
			'params' => array(
				'name'           => 'woocommerce-create-product',
				'arguments_keys' => array( 'name', 'regular_price', 'sku' ),
			),
			'status' => 'success',
		);

		$this->sut->record_event( 'mcp.request', $tags, 100.0 );

		$this->assertNotEmpty( $this->captured_events );
		$event = $this->captured_events[0];
		$this->assertArrayHasKey( 'param_fields', $event['properties'] );
		$this->assertSame( 'name,regular_price,sku', $event['properties']['param_fields'] );
	}

	/**
	 * Test that only tool/call events are tracked.
	 */
	public function test_only_tracks_tool_call_events(): void {
		update_option( 'woocommerce_allow_tracking', 'yes' );

		// Non-tool call event.
		$tags = array(
			'method' => 'resources/list',
			'status' => 'success',
		);

		$this->sut->record_event( 'mcp.request', $tags, 100.0 );

		$this->assertEmpty( $this->captured_events, 'Should not track non-tool-call events' );
	}

	/**
	 * Test that event name is formatted correctly.
	 */
	public function test_formats_event_name_for_tracks(): void {
		update_option( 'woocommerce_allow_tracking', 'yes' );

		$tags = array(
			'method' => 'tools/call',
			'params' => array(
				'name'           => 'woocommerce-test',
				'arguments_keys' => array(),
			),
			'status' => 'success',
		);

		$this->sut->record_event( 'mcp.request', $tags, 100.0 );

		$this->assertNotEmpty( $this->captured_events );
		$event = $this->captured_events[0];
		$this->assertSame( 'wcadmin_mcp_tool_call', $event['event_name'] );
	}

	/**
	 * Test that duration is included in properties.
	 */
	public function test_includes_duration_in_properties(): void {
		update_option( 'woocommerce_allow_tracking', 'yes' );

		$tags = array(
			'method' => 'tools/call',
			'params' => array(
				'name'           => 'woocommerce-test',
				'arguments_keys' => array(),
			),
			'status' => 'success',
		);

		$this->sut->record_event( 'mcp.request', $tags, 125.456 );

		$this->assertNotEmpty( $this->captured_events );
		$event = $this->captured_events[0];
		$this->assertArrayHasKey( 'duration_ms', $event['properties'] );
		$this->assertSame( 125.46, $event['properties']['duration_ms'] );
	}

	/**
	 * Test that status is included in properties.
	 */
	public function test_includes_status_in_properties(): void {
		update_option( 'woocommerce_allow_tracking', 'yes' );

		$tags = array(
			'method' => 'tools/call',
			'params' => array(
				'name'           => 'woocommerce-test',
				'arguments_keys' => array(),
			),
			'status' => 'error',
		);

		$this->sut->record_event( 'mcp.request', $tags, 100.0 );

		$this->assertNotEmpty( $this->captured_events );
		$event = $this->captured_events[0];
		$this->assertArrayHasKey( 'status', $event['properties'] );
		$this->assertSame( 'error', $event['properties']['status'] );
	}

	/**
	 * Test handling of missing params.
	 */
	public function test_handles_missing_params_gracefully(): void {
		update_option( 'woocommerce_allow_tracking', 'yes' );

		$tags = array(
			'method' => 'tools/call',
			'status' => 'success',
		);

		$this->sut->record_event( 'mcp.request', $tags, 100.0 );

		$this->assertNotEmpty( $this->captured_events );
		$event = $this->captured_events[0];
		$this->assertSame( 'unknown', $event['properties']['tool_name'] );
		$this->assertSame( '', $event['properties']['param_fields'] );
	}
}
