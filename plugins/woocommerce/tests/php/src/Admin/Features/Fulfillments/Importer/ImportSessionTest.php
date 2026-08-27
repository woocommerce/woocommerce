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
	 * @testdox create() stores the staged file's real size and mtime so handle_run can detect swapped files.
	 */
	public function test_create_stores_file_size_and_mtime(): void {
		$path = wp_tempnam( 'wc-fulfillments-session-' );
		file_put_contents( $path, "order_number,tracking_number,shipment_provider\n1,TRK-1,ups\n" ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents -- Test fixture write.

		$session          = ImportSession::create(
			31,
			$path,
			',',
			array( 'order_number', 'tracking_number', 'shipment_provider' ),
			1,
			false,
			true
		);
		$this->sessions[] = $session;

		$loaded = ImportSession::load( 31, $session->token() );
		$this->assertNotNull( $loaded );
		$this->assertSame( (int) filesize( $path ), $loaded->file_size() );
		$this->assertGreaterThan( 0, $loaded->file_size() );
		$this->assertSame( (int) filemtime( $path ), $loaded->file_mtime() );

		wp_delete_file( $path );
	}

	/**
	 * @testdox A missing transient yields a null load result.
	 */
	public function test_load_returns_null_for_unknown_token(): void {
		$this->assertNull( ImportSession::load( 9, 'no-such-token' ) );
	}

	/**
	 * @testdox load() respects user scoping; another user cannot load someone else's session.
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
			array(
				'100|abc' => true,
				'200|xyz' => true,
			),
			5678
		);

		$reloaded = ImportSession::load( 21, $session->token() );
		$this->assertNotNull( $reloaded );
		$this->assertSame( 20, $reloaded->processed() );
		$this->assertSame( 5678, $reloaded->byte_offset() );
		$this->assertSame(
			array(
				'100|abc' => true,
				'200|xyz' => true,
			),
			$reloaded->seen_tracking_pairs()
		);
		$counts = $reloaded->counts();
		$this->assertSame( 12, $counts['created'] );
		$this->assertSame( 2, $counts['updated'] );
		$this->assertSame( 6, $counts['skipped'] );
		$this->assertSame( 4, $counts['notified'] );
	}

	/**
	 * @testdox record_chunk() reports success when the payload is unchanged, since set_transient returns false for identical values.
	 */
	public function test_record_chunk_with_identical_payload_still_reports_success(): void {
		$session = $this->make_session( 22 );

		$counts = array(
			'created'  => 1,
			'updated'  => 0,
			'skipped'  => 0,
			'failed'   => 0,
			'notified' => 0,
		);

		$this->assertTrue( $session->record_chunk( 5, $counts, array( '1|a' => true ), 100 ) );
		$this->assertTrue(
			$session->record_chunk( 5, array(), array( '1|a' => true ), 100 ),
			'An identical rewrite must not be reported as a persistence failure'
		);
		$this->assertTrue( $session->persisted() );
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
	 * @testdox cleanup_abandoned_file() refuses a readable path outside the uploads directory.
	 */
	public function test_cleanup_abandoned_file_refuses_path_outside_uploads(): void {
		$file = ABSPATH . 'wc-fulfillments-import-' . wp_generate_uuid4() . '.csv';
		file_put_contents( $file, "a,b,c\n" ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents -- Test fixture write.

		try {
			ImportSession::cleanup_abandoned_file( 75, 'ghost', $file );
			$this->assertFileExists( $file, 'Only files staged in the uploads directory may be cleaned up' );
		} finally {
			wp_delete_file( $file );
		}
	}

	/**
	 * @testdox The cleanup hook callback coerces loosely typed arguments instead of fataling.
	 */
	public function test_cleanup_hook_callback_coerces_arguments(): void {
		$upload_dir = wp_upload_dir();
		$file       = trailingslashit( $upload_dir['basedir'] ) . 'wc-fulfillments-import-' . wp_generate_uuid4() . '.csv';
		file_put_contents( $file, "a,b,c\n" ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents -- Test fixture write.

		add_action( ImportSession::CLEANUP_HOOK, array( ImportSession::class, 'handle_cleanup_hook' ), 10, 4 );

		try {
			// Action Scheduler payloads are persisted, so the user ID can come back as a string.
			do_action( ImportSession::CLEANUP_HOOK, '73', 'ghost', $file, '0' );
		} finally {
			remove_action( ImportSession::CLEANUP_HOOK, array( ImportSession::class, 'handle_cleanup_hook' ), 10 );
		}

		$this->assertFileDoesNotExist( $file );
	}

	/**
	 * @testdox The cleanup hook callback ignores a non-scalar file argument.
	 */
	public function test_cleanup_hook_callback_ignores_non_scalar_args(): void {
		ImportSession::handle_cleanup_hook( 74, 'ghost', array( 'not', 'a', 'path' ) );

		$this->assertTrue( true, 'A malformed payload must not fatal' );
	}

	/**
	 * @testdox cleanup_abandoned_file() deletes the staged file when the session transient is gone.
	 */
	public function test_cleanup_abandoned_file_deletes_when_session_is_gone(): void {
		$upload_dir = wp_upload_dir();
		$file       = trailingslashit( $upload_dir['basedir'] ) . 'wc-fulfillments-import-' . wp_generate_uuid4() . '.csv';
		file_put_contents( $file, "a,b,c\n" ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents -- Test fixture write.

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
		file_put_contents( $file, "a,b,c\n" ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents -- Test fixture write.

		$session          = ImportSession::create(
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

		wp_delete_file( $file );
	}

	/**
	 * @testdox cleanup_abandoned_file() also deletes the attachment post created for the staged file.
	 */
	public function test_cleanup_abandoned_file_deletes_attachment(): void {
		$upload_dir = wp_upload_dir();
		$file       = trailingslashit( $upload_dir['basedir'] ) . 'wc-fulfillments-import-' . wp_generate_uuid4() . '.csv';
		file_put_contents( $file, "a,b,c\n" ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents -- Test fixture write.

		$attachment_id = wp_insert_attachment(
			array(
				'post_title'     => 'Fulfillments import test CSV',
				'post_mime_type' => 'text/csv',
			),
			$file
		);
		$this->assertGreaterThan( 0, $attachment_id );

		ImportSession::cleanup_abandoned_file( 72, 'ghost', $file, (int) $attachment_id );

		$this->assertFileDoesNotExist( $file );
		$this->assertNull( get_post( $attachment_id ), 'The attachment post must not be left behind' );
	}

	/**
	 * @testdox cleanup_abandoned_file() ignores an attachment ID that no longer points at the staged file.
	 */
	public function test_cleanup_abandoned_file_ignores_mismatched_attachment(): void {
		$upload_dir = wp_upload_dir();
		$file       = trailingslashit( $upload_dir['basedir'] ) . 'wc-fulfillments-import-' . wp_generate_uuid4() . '.csv';
		$other      = trailingslashit( $upload_dir['basedir'] ) . 'wc-fulfillments-other-' . wp_generate_uuid4() . '.csv';
		file_put_contents( $file, "a,b,c\n" ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents -- Test fixture write.
		file_put_contents( $other, "x,y,z\n" ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents -- Test fixture write.

		$attachment_id = wp_insert_attachment(
			array(
				'post_title'     => 'Unrelated attachment',
				'post_mime_type' => 'text/csv',
			),
			$other
		);
		$this->assertGreaterThan( 0, $attachment_id );

		ImportSession::cleanup_abandoned_file( 73, 'ghost', $file, (int) $attachment_id );

		$this->assertFileDoesNotExist( $file );
		$this->assertNotNull( get_post( $attachment_id ), 'An attachment for a different file must not be deleted' );

		wp_delete_attachment( (int) $attachment_id, true );
		wp_delete_file( $other );
	}

	/**
	 * @testdox cleanup_abandoned_file() refuses to delete paths outside the uploads directory.
	 */
	public function test_cleanup_abandoned_file_refuses_paths_outside_uploads(): void {
		$file = '/tmp/wc-fulfillments-not-in-uploads-' . wp_generate_uuid4() . '.csv';
		file_put_contents( $file, "a,b,c\n" ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents -- Test fixture write outside uploads on purpose.

		ImportSession::cleanup_abandoned_file( 91, 'no-such-token', $file );

		$this->assertFileExists( $file );
		wp_delete_file( $file );
	}

	/**
	 * @testdox delete() removes the session and subsequent loads return null.
	 */
	public function test_delete_removes_session(): void {
		$session = $this->make_session( 33 );
		$token   = $session->token();
		$session->delete();
		$this->sessions = array();
		// Avoid double-delete in tearDown.

		$this->assertNull( ImportSession::load( 33, $token ) );
	}

	/**
	 * @testdox Creating a second session for the same user invalidates the first.
	 */
	public function test_create_replaces_prior_session_for_same_user(): void {
		$first       = $this->make_session( 44 );
		$first_token = $first->token();

		$second = $this->make_session( 44 );
		$this->assertNotSame( $first_token, $second->token() );

		$this->assertNull( ImportSession::load( 44, $first_token ), 'Prior session should be invalidated.' );
		$this->assertNotNull( ImportSession::load( 44, $second->token() ) );
	}
}
