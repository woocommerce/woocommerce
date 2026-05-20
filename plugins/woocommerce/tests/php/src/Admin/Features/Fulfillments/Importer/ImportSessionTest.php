<?php declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Admin\Features\Fulfillments\Importer;

use Automattic\WooCommerce\Admin\Features\Fulfillments\Importer\ImportSession;

/**
 * Tests for the ImportSession value object.
 */
class ImportSessionTest extends \WC_Unit_Test_Case {

	/**
	 * Track sessions to delete in tearDown so stray transients can't bleed between tests.
	 *
	 * @var array<int, ImportSession>
	 */
	private array $sessions = array();

	/**
	 * Clean up any sessions created during a test.
	 */
	public function tearDown(): void {
		foreach ( $this->sessions as $session ) {
			$session->delete();
		}
		$this->sessions = array();
		parent::tearDown();
	}

	/**
	 * Create a session and remember it for tearDown.
	 *
	 * @param int $user_id User ID.
	 * @return ImportSession
	 */
	private function make_session( int $user_id ): ImportSession {
		$session          = ImportSession::create(
			$user_id,
			'/tmp/sample.csv',
			',',
			array( 'order_number', 'tracking_number', 'shipment_provider' ),
			42,
			false,
			true
		);
		$this->sessions[] = $session;
		return $session;
	}

	/**
	 * @testdox create() persists the payload and load() returns it back unchanged.
	 */
	public function test_create_then_load_roundtrip(): void {
		$user_id = 7;
		$session = $this->make_session( $user_id );
		$token   = $session->token();

		$this->assertNotEmpty( $token );

		$loaded = ImportSession::load( $user_id, $token );
		$this->assertNotNull( $loaded );
		$this->assertSame( '/tmp/sample.csv', $loaded->file() );
		$this->assertSame( ',', $loaded->delimiter() );
		$this->assertSame( array( 'order_number', 'tracking_number', 'shipment_provider' ), $loaded->headers() );
		$this->assertSame( 42, $loaded->total() );
		$this->assertSame( 0, $loaded->processed() );
		$this->assertFalse( $loaded->notify_customer() );
		$this->assertTrue( $loaded->update_existing() );
		$this->assertSame( array(), $loaded->seen_tracking_pairs() );
	}

	/**
	 * @testdox A missing transient yields a null load result.
	 */
	public function test_load_returns_null_for_unknown_token(): void {
		$this->assertNull( ImportSession::load( 9, 'no-such-token' ) );
	}

	/**
	 * @testdox load() respects user scoping — another user cannot load someone else's session.
	 */
	public function test_load_is_user_scoped(): void {
		$session = $this->make_session( 11 );
		$token   = $session->token();

		$this->assertNotNull( ImportSession::load( 11, $token ) );
		$this->assertNull( ImportSession::load( 12, $token ) );
	}

	/**
	 * @testdox record_chunk() advances processed, merges counts, persists dedupe and byte_offset.
	 */
	public function test_record_chunk_persists_state(): void {
		$session = $this->make_session( 21 );

		$session->record_chunk(
			10,
			array(
				'created'  => 7,
				'updated'  => 2,
				'skipped'  => 1,
				'failed'   => 0,
				'notified' => 3,
			),
			array( '100|abc' => true ),
			1234
		);
		$session->record_chunk(
			20,
			array(
				'created'  => 5,
				'updated'  => 0,
				'skipped'  => 5,
				'failed'   => 0,
				'notified' => 1,
			),
			array( '100|abc' => true, '200|xyz' => true ),
			5678
		);

		$reloaded = ImportSession::load( 21, $session->token() );
		$this->assertNotNull( $reloaded );
		$this->assertSame( 20, $reloaded->processed() );
		$this->assertSame( 5678, $reloaded->byte_offset() );
		$this->assertSame(
			array( '100|abc' => true, '200|xyz' => true ),
			$reloaded->seen_tracking_pairs()
		);
		$counts = $reloaded->counts();
		$this->assertSame( 12, $counts['created'] );
		$this->assertSame( 2, $counts['updated'] );
		$this->assertSame( 6, $counts['skipped'] );
		$this->assertSame( 4, $counts['notified'] );
	}

	/**
	 * @testdox record_chunk() never regresses processed below a previously stored value.
	 */
	public function test_record_chunk_never_goes_backwards_on_processed(): void {
		$session = $this->make_session( 23 );
		$session->record_chunk( 30, array(), array(), 999 );
		$session->record_chunk( 10, array(), array(), 1000 );

		$reloaded = ImportSession::load( 23, $session->token() );
		$this->assertNotNull( $reloaded );
		$this->assertSame( 30, $reloaded->processed() );
	}

	/**
	 * @testdox record_chunk() honors the latest byte_offset verbatim, including lower values from retries.
	 */
	public function test_record_chunk_byte_offset_is_not_clamped_upward(): void {
		$session = $this->make_session( 24 );
		$session->record_chunk( 5, array(), array(), 5000 );
		$session->record_chunk( 10, array(), array(), 3500 );

		$reloaded = ImportSession::load( 24, $session->token() );
		$this->assertNotNull( $reloaded );
		$this->assertSame( 3500, $reloaded->byte_offset() );
	}

	/**
	 * @testdox active_for_user() returns the current session, or null if none.
	 */
	public function test_active_for_user_returns_open_session(): void {
		$this->assertNull( ImportSession::active_for_user( 51 ) );

		$session = $this->make_session( 51 );
		$active  = ImportSession::active_for_user( 51 );
		$this->assertNotNull( $active );
		$this->assertSame( $session->token(), $active->token() );
	}

	/**
	 * @testdox cleanup_abandoned_file() deletes the staged file when the session transient is gone.
	 */
	public function test_cleanup_abandoned_file_deletes_when_session_is_gone(): void {
		$upload_dir = wp_upload_dir();
		$file       = trailingslashit( $upload_dir['basedir'] ) . 'wc-fulfillments-import-' . wp_generate_uuid4() . '.csv';
		file_put_contents( $file, "a,b,c\n" );

		// Fake an abandoned session: no transient is set for token "ghost".
		ImportSession::cleanup_abandoned_file( 71, 'ghost', $file );

		$this->assertFileDoesNotExist( $file );
	}

	/**
	 * @testdox cleanup_abandoned_file() leaves the file alone while the session is still active.
	 */
	public function test_cleanup_abandoned_file_skips_live_session(): void {
		$upload_dir = wp_upload_dir();
		$file       = trailingslashit( $upload_dir['basedir'] ) . 'wc-fulfillments-import-' . wp_generate_uuid4() . '.csv';
		file_put_contents( $file, "a,b,c\n" );

		$session = ImportSession::create(
			81,
			$file,
			',',
			array( 'order_number', 'tracking_number', 'shipment_provider' ),
			3,
			false,
			true
		);
		$this->sessions[] = $session;

		ImportSession::cleanup_abandoned_file( 81, $session->token(), $file );

		$this->assertFileExists( $file );

		// Test-only cleanup.
		@unlink( $file );
	}

	/**
	 * @testdox cleanup_abandoned_file() refuses to delete paths outside the uploads directory.
	 */
	public function test_cleanup_abandoned_file_refuses_paths_outside_uploads(): void {
		$file = '/tmp/wc-fulfillments-not-in-uploads-' . wp_generate_uuid4() . '.csv';
		file_put_contents( $file, "a,b,c\n" );

		ImportSession::cleanup_abandoned_file( 91, 'no-such-token', $file );

		$this->assertFileExists( $file );
		@unlink( $file );
	}

	/**
	 * @testdox delete() removes the session and subsequent loads return null.
	 */
	public function test_delete_removes_session(): void {
		$session = $this->make_session( 33 );
		$token   = $session->token();
		$session->delete();
		$this->sessions = array(); // Avoid double-delete in tearDown.

		$this->assertNull( ImportSession::load( 33, $token ) );
	}

	/**
	 * @testdox Creating a second session for the same user invalidates the first.
	 */
	public function test_create_replaces_prior_session_for_same_user(): void {
		$first  = $this->make_session( 44 );
		$first_token = $first->token();

		$second = $this->make_session( 44 );
		$this->assertNotSame( $first_token, $second->token() );

		$this->assertNull( ImportSession::load( 44, $first_token ), 'Prior session should be invalidated.' );
		$this->assertNotNull( ImportSession::load( 44, $second->token() ) );
	}
}
