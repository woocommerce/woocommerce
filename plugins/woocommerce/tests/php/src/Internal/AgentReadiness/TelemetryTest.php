<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Internal\AgentReadiness;

use Automattic\WooCommerce\Internal\AgentReadiness\AgentReadiness;
use Automattic\WooCommerce\Internal\AgentReadiness\Telemetry;
use WC_Unit_Test_Case;

/**
 * Tests for the agent readiness fields in the WC Tracker snapshot.
 */
class TelemetryTest extends WC_Unit_Test_Case {

	/**
	 * System under test.
	 *
	 * @var Telemetry
	 */
	private Telemetry $sut;

	/**
	 * Set up.
	 */
	public function setUp(): void {
		parent::setUp();
		update_option( 'woocommerce_feature_agent_readiness_enabled', 'yes' );
		delete_option( AgentReadiness::REPORT_OPTION );
		$this->sut = wc_get_container()->get( Telemetry::class );
	}

	/**
	 * Tear down.
	 */
	public function tearDown(): void {
		delete_option( 'woocommerce_feature_agent_readiness_enabled' );
		parent::tearDown();
	}

	/**
	 * Store a report as the scan would.
	 */
	private function store_report(): void {
		update_option(
			AgentReadiness::REPORT_OPTION,
			array(
				'generated_at' => '2026-11-14T08:30:00+00:00',
				'store_checks' => array(
					array(
						'id'      => 'robots_allows_agents',
						'ok'      => true,
						'name'    => 'AI assistants can read your store',
						'fix_url' => 'https://example.com/wp-admin/options-reading.php',
					),
					array(
						'id'   => 'product_schema',
						'ok'   => false,
						'name' => 'Product structured data',
					),
					array(
						'id'   => 'llms_txt',
						'ok'   => null,
						'name' => 'llms.txt',
					),
					array(
						'id'      => 'open_graph',
						'ok'      => false,
						'pending' => true,
						'name'    => 'Open Graph',
					),
				),
				'checks'       => array(
					array(
						'id'          => 'missing_brand',
						'label'       => 'Add a brand',
						'count'       => 3,
						'product_ids' => array( 11, 12, 13 ),
					),
					array(
						'id'          => 'missing_image',
						'label'       => 'Add an image',
						'count'       => 0,
						'product_ids' => array(),
					),
				),
			)
		);
	}

	/**
	 * @testdox The snapshot is added to the tracker data under agent_readiness.
	 */
	public function test_adds_the_block_to_the_tracker_data(): void {
		global $wp_filter;
		$registered = false;
		foreach ( $wp_filter['woocommerce_tracker_data']->callbacks ?? array() as $callbacks ) {
			foreach ( $callbacks as $callback ) {
				$registered = $registered || ( is_array( $callback['function'] ) && $callback['function'][0] instanceof Telemetry );
			}
		}
		$this->assertTrue( $registered, 'WooCommerce registers the snapshot filter on load' );

		$data = $this->sut->add_snapshot_to_tracker_data( array( 'version' => '11.4.0' ) );

		$this->assertSame( '11.4.0', $data['version'] );
		$this->assertSame( 'yes', $data['agent_readiness']['feature_enabled'] );
	}

	/**
	 * @testdox With the feature off, the snapshot says so and reads nothing else.
	 */
	public function test_feature_off(): void {
		update_option( 'woocommerce_feature_agent_readiness_enabled', 'no' );
		$this->store_report();

		$snapshot = $this->sut->snapshot();

		$this->assertSame( 'no', $snapshot['feature_enabled'] );
		$this->assertSame( '', $snapshot['last_scan'] );
		$this->assertSame( 'no', $snapshot['truncated'] );
		$this->assertSame( array(), $snapshot['store_checks'] );
		$this->assertSame( array(), $snapshot['product_checks'] );
	}

	/**
	 * @testdox Before the first scan, the snapshot has the setup steps but no scan date or product checks.
	 */
	public function test_before_the_first_scan(): void {
		$snapshot = $this->sut->snapshot();

		$this->assertSame( 'yes', $snapshot['feature_enabled'] );
		$this->assertSame( '', $snapshot['last_scan'] );
		$this->assertSame( 'no', $snapshot['truncated'] );
		$this->assertArrayHasKey( 'https', $snapshot['store_checks'] );
		$this->assertArrayNotHasKey( 'product_schema', $snapshot['store_checks'], 'Output checks only run in the scan' );
		$this->assertSame( array(), $snapshot['product_checks'] );
	}

	/**
	 * @testdox Each check is pass, problem or unknown, with the scan's date and no store content.
	 */
	public function test_statuses_from_the_stored_report(): void {
		$this->store_report();

		$snapshot = $this->sut->snapshot();

		$this->assertSame( '2026-11-14', $snapshot['last_scan'] );
		$this->assertSame( 'no', $snapshot['truncated'] );
		$this->assertSame( Telemetry::PASS, $snapshot['store_checks']['robots_allows_agents'] );
		$this->assertSame( Telemetry::PROBLEM, $snapshot['store_checks']['product_schema'] );
		$this->assertSame( Telemetry::UNKNOWN, $snapshot['store_checks']['llms_txt'], 'A check that could not run' );
		$this->assertSame( Telemetry::UNKNOWN, $snapshot['store_checks']['open_graph'], 'A check still waiting for a result' );
		$this->assertArrayHasKey( 'https', $snapshot['store_checks'], 'Setup steps are read now, not from the report' );
		$this->assertSame( Telemetry::PROBLEM, $snapshot['product_checks']['missing_brand'] );
		$this->assertSame( Telemetry::PASS, $snapshot['product_checks']['missing_image'] );

		$sent = (string) wp_json_encode( $snapshot );
		$this->assertStringNotContainsString( 'http', str_replace( '"https"', '', $sent ), 'No URLs' );
		$this->assertStringNotContainsString( 'Add a brand', $sent, 'No check text' );
		$this->assertStringNotContainsString( 'product_ids', $sent, 'No product ids' );
	}

	/**
	 * @testdox A scan that stopped at the product limit is marked truncated in the snapshot.
	 */
	public function test_truncated_scan_is_flagged(): void {
		$this->store_report();
		$report              = get_option( AgentReadiness::REPORT_OPTION );
		$report['truncated'] = true;
		update_option( AgentReadiness::REPORT_OPTION, $report );

		$snapshot = $this->sut->snapshot();

		$this->assertSame( 'yes', $snapshot['truncated'] );
	}

	/**
	 * @testdox Without the usage tracking opt-in, the tracker never builds the snapshot.
	 */
	public function test_nothing_is_built_without_the_tracking_opt_in(): void {
		update_option( 'woocommerce_allow_tracking', 'no' );
		$this->store_report();
		$built = 0;
		$spy   = function ( $data ) use ( &$built ) {
			++$built;
			return $data;
		};
		add_filter( 'woocommerce_tracker_data', $spy );

		WC()->add_woocommerce_tracker_send_event_wrapper();

		remove_filter( 'woocommerce_tracker_data', $spy );
		$this->assertSame( 0, $built );
	}
}
