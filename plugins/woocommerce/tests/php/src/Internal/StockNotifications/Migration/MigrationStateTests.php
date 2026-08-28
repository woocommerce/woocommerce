<?php
/**
 * MigrationStateTests class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\StockNotifications\Migration;

use Automattic\WooCommerce\Internal\StockNotifications\Migration\Constants;
use Automattic\WooCommerce\Internal\StockNotifications\Migration\MigrationState;
use WC_Unit_Test_Case;

/**
 * Tests that the migration run state survives a malformed option.
 *
 * `wc_bis_migration_state` is an ordinary option, so anything on the site can overwrite it
 * with the wrong shape. Every read has to come back usable rather than fatal.
 */
class MigrationStateTests extends WC_Unit_Test_Case {

	/**
	 * System under test.
	 *
	 * @var MigrationState
	 */
	private MigrationState $sut;

	/**
	 * Set up the test case.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->sut = new MigrationState();
	}

	/**
	 * Tear down the test case.
	 */
	public function tearDown(): void {
		delete_option( Constants::STATE_OPTION );

		parent::tearDown();
	}

	/**
	 * @testdox a non-array option should read back as the default state.
	 */
	public function test_a_scalar_option_reads_back_as_the_default_state(): void {
		update_option( Constants::STATE_OPTION, 'corrupt', false );

		$state = $this->sut->get_state();

		$this->assertNull( $state['lock'] );
		$this->assertSame( array(), $state['cursor'] );
		$this->assertSame( array(), $state['counts'] );
		$this->assertSame( array(), $state['options'] );
		$this->assertNull( $state['losses'] );
	}

	/**
	 * @testdox a scalar lock should not reach the lock freshness check as a TypeError.
	 */
	public function test_a_scalar_lock_does_not_fatal(): void {
		update_option( Constants::STATE_OPTION, array( 'lock' => 'held' ), false );

		$this->assertFalse( $this->sut->is_lock_held() );
		$this->assertNull( $this->sut->get_lock() );
	}

	/**
	 * @testdox scalar nested fields should not break the accessors declared as ?array.
	 */
	public function test_scalar_nested_fields_do_not_break_the_array_accessors(): void {
		update_option(
			Constants::STATE_OPTION,
			array(
				'counts' => 'not-an-array',
				'losses' => 12,
			),
			false
		);

		$this->assertNull( $this->sut->get_count( 'notifications' ) );
		$this->assertNull( $this->sut->get_losses() );
	}

	/**
	 * @testdox a scalar entry inside counts should read back as no cached count.
	 */
	public function test_a_scalar_count_entry_reads_back_as_null(): void {
		update_option(
			Constants::STATE_OPTION,
			array( 'counts' => array( 'notifications' => 7 ) ),
			false
		);

		$this->assertNull( $this->sut->get_count( 'notifications' ) );
	}
}
