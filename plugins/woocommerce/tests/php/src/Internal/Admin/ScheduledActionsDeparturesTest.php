<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\Admin;

use ActionScheduler;
use DOMDocument;
use DOMXPath;
use Automattic\WooCommerce\Internal\Admin\ScheduledActionsDepartures;
use WC_Unit_Test_Case;

/**
 * Behavioral coverage for the optional departures board.
 */
class ScheduledActionsDeparturesTest extends WC_Unit_Test_Case {

	/**
	 * The System Under Test.
	 *
	 * @var ScheduledActionsDepartures
	 */
	private $sut;

	/**
	 * Saved request parameters.
	 *
	 * @var array
	 */
	private $original_get;

	/**
	 * Prepare a permitted viewer.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->sut          = wc_get_container()->get( ScheduledActionsDepartures::class );
		$this->original_get = $_GET; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Preserve test-only request state.
		$_GET               = array();
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );
	}

	/**
	 * Restore the request.
	 */
	public function tearDown(): void {
		$_GET = $this->original_get;
		parent::tearDown();
	}

	/**
	 * Capture the rendered view.
	 *
	 * @return string
	 */
	private function render_board(): string {
		ob_start();
		try {
			$this->sut->render();
			return ob_get_contents();
		} finally {
			ob_end_clean();
		}
	}

	/**
	 * @testdox The board escapes hook and group names, omits arguments, and uses the site timezone.
	 */
	public function test_safe_localized_snapshot(): void {
		update_option( 'timezone_string', 'America/Toronto' );
		as_schedule_single_action( strtotime( '2050-01-02 15:30:00 UTC' ), 'departures_<script>alert(1)</script>', array( 'secret' => 'private-action-argument' ), 'group_<img src=x onerror=alert(1)>' );
		$html = $this->render_board();

		$this->assertStringContainsString( 'departures_&lt;script&gt;', $html );
		$this->assertStringContainsString( 'group_&lt;img', $html );
		$this->assertStringNotContainsString( '<script>', $html );
		$this->assertStringNotContainsString( '<img', $html );
		$this->assertStringNotContainsString( 'private-action-argument', $html );
		$this->assertStringContainsString( '10:30</span>', $html );
		$this->assertStringContainsString( 'Jan 2, 2050', $html );
	}

	/**
	 * @testdox The board limits large queues and filters by status without changing actions.
	 */
	public function test_bounded_read_only_status_view(): void {
		$store = ActionScheduler::store();
		$ids   = array();
		for ( $i = 0; $i < 17; $i++ ) {
			$id = as_schedule_single_action( strtotime( '2050-01-01 UTC' ) + $i, 'departures_failed_' . $i );
			$store->mark_failure( $id );
			$ids[] = $id;
		}
		$pending_id     = as_schedule_single_action( strtotime( '2050-01-01 UTC' ), 'departures_pending' );
		$_GET['status'] = 'failed';
		$html           = $this->render_board();

		$document = new DOMDocument();
		$document->loadHTML( $html, LIBXML_NOERROR | LIBXML_NOWARNING );
		$this->assertSame( 16, ( new DOMXPath( $document ) )->query( '//tbody/tr' )->length );
		$this->assertStringNotContainsString( 'departures_pending', $html );
		$this->assertStringContainsString( 'More departures in the action list', $html );
		$this->assertSame( 'pending', $store->get_status( $pending_id ) );
		foreach ( $ids as $id ) {
			$this->assertSame( 'failed', $store->get_status( $id ) );
		}
	}

	/**
	 * @testdox Invalid status input falls back to the upcoming view.
	 *
	 * @testWith ["unknown"]
	 *           [["failed"]]
	 *
	 * @param mixed $status Untrusted status input.
	 */
	public function test_invalid_status_falls_back( $status ): void {
		as_schedule_single_action( strtotime( '2050-01-01 UTC' ), 'departures_upcoming' );
		$_GET['status'] = $status;

		$this->assertStringContainsString( 'departures_upcoming', $this->render_board() );
	}

	/**
	 * @testdox Viewers without Action Scheduler's management capability cannot read the board.
	 */
	public function test_forbidden_viewer(): void {
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'shop_manager' ) ) );
		$this->expectException( \WPDieException::class );
		$this->render_board();
	}
}
