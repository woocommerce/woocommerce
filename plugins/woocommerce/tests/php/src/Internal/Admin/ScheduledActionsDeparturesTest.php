<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\Admin;

use ActionScheduler;
use ActionScheduler_Store;
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
		$_GET['paged']  = '2';
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
	 * @testdox The live board pages through all statuses in newest-first order and keeps refreshing the selected page.
	 */
	public function test_live_board_pagination(): void {
		$store   = ActionScheduler::store();
		$ids     = array();
		$changes = array( null, 'log_execution', 'mark_failure', 'mark_complete', 'cancel_action' );
		for ( $i = 0; $i < 17; $i++ ) {
			$id     = as_schedule_single_action( strtotime( '2050-01-01 UTC' ), 'departures_paged_' . $i );
			$change = $changes[ $i % count( $changes ) ];
			if ( $change ) {
				$store->$change( $id );
			}
			$ids[] = (string) $id;
		}
		$_GET     = array(
			'live'   => '1',
			'status' => 'failed',
		);
		$html     = $this->render_board();
		$document = new DOMDocument();
		$document->loadHTML( $html, LIBXML_NOERROR | LIBXML_NOWARNING );
		$xpath   = new DOMXPath( $document );
		$visible = array();
		foreach ( $xpath->query( '//tbody/tr' ) as $row ) {
			$visible[] = $row->getAttribute( 'data-action-id' );
		}
		$this->assertSame( array_reverse( array_slice( $ids, 1 ) ), $visible );
		$this->assertSame( 0, $xpath->query( '//nav[@aria-label="Action status"]' )->length );
		foreach ( array( ActionScheduler_Store::STATUS_PENDING, ActionScheduler_Store::STATUS_RUNNING, ActionScheduler_Store::STATUS_FAILED, ActionScheduler_Store::STATUS_COMPLETE, ActionScheduler_Store::STATUS_CANCELED ) as $status ) {
			$this->assertStringContainsString( 'wc-departures__status--' . $status, $html );
		}
		$this->assertStringContainsString( 'paged=2', $xpath->query( '//a[@rel="next"]' )->item( 0 )->getAttribute( 'href' ) );

		$_GET['paged'] = '2';
		$html          = $this->render_board();
		$document->loadHTML( $html, LIBXML_NOERROR | LIBXML_NOWARNING );
		$xpath = new DOMXPath( $document );
		$this->assertSame( $ids[0], $xpath->query( '//tbody/tr' )->item( 0 )->getAttribute( 'data-action-id' ) );
		foreach ( $visible as $id ) {
			$this->assertStringNotContainsString( 'data-action-id="' . $id . '"', $html );
		}
		$this->assertStringContainsString( 'paged=1', $xpath->query( '//a[@rel="prev"]' )->item( 0 )->getAttribute( 'href' ) );
		$refresh_url = $xpath->query( '//*[@data-refresh-url]' )->item( 0 )->getAttribute( 'data-refresh-url' );
		$this->assertStringContainsString( 'paged=2', $refresh_url );
		$this->assertStringNotContainsString( 'status=', $refresh_url );
	}

	/**
	 * @testdox Invalid live page numbers fall back to the first page.
	 *
	 * @testWith ["-2"]
	 *           [["2"]]
	 *           ["999999999999999999999999999"]
	 *
	 * @param mixed $page Untrusted page input.
	 */
	public function test_invalid_live_page_falls_back( $page ): void {
		as_schedule_single_action( strtotime( '2050-01-01 UTC' ), 'departures_first_page' );
		$_GET = array(
			'live'  => '1',
			'paged' => $page,
		);
		$html = $this->render_board();

		$this->assertStringContainsString( 'departures_first_page', $html );
		$this->assertStringContainsString( 'Page 1', $html );
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
