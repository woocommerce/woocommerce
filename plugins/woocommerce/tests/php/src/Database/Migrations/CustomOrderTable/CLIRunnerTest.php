<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Database\Migrations\CustomOrderTable;

use Automattic\WooCommerce\Database\Migrations\CustomOrderTable\CLIRunner;

/**
 * Tests for the HPOS CLI runner.
 */
class CLIRunnerTest extends \WC_Unit_Test_Case {

	/**
	 * The System Under Test.
	 *
	 * @var CLIRunner
	 */
	private $sut;

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->sut = wc_get_container()->get( CLIRunner::class );
	}

	/**
	 * @testdox Should support JSON output for HPOS status.
	 */
	public function test_status_supports_json_output(): void {
		ob_start();
		$this->sut->status( array(), array( 'format' => 'json' ) );
		$output = ob_get_clean();

		$this->assertStringNotContainsString( 'HPOS enabled?: ', $output );

		$status = json_decode( trim( $output ), true );

		$this->assertIsArray( $status, 'JSON status output should decode into an array.' );
		$this->assertArrayHasKey( 'hpos_enabled', $status );
		$this->assertArrayHasKey( 'compatibility_mode_enabled', $status );
		$this->assertArrayHasKey( 'unsynced_orders', $status );
		$this->assertArrayHasKey( 'orders_subject_to_cleanup', $status );
		$this->assertIsBool( $status['hpos_enabled'] );
		$this->assertIsBool( $status['compatibility_mode_enabled'] );
		$this->assertIsInt( $status['unsynced_orders'] );
		$this->assertIsInt( $status['orders_subject_to_cleanup'] );
	}
}
