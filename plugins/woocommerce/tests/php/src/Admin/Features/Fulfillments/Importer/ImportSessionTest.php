<?php declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Admin\Features\Fulfillments\Importer;

use Automattic\WooCommerce\Admin\Features\Fulfillments\Importer\ImportSession;

/**
 * Tests for the ImportSession value object.
 */
class ImportSessionTest extends \WC_Unit_Test_Case {

	/**
	 * Action Scheduler group the session schedules its cleanup in.
	 */
	private const CLEANUP_GROUP = 'woocommerce-fulfillments-importer';

	/**
	 * Track sessions to delete in tearDown so stray transients can't bleed between tests.
	 *
	 * @var array<int, ImportSession>
	 */
	private array $sessions = array();

	/**
	 * Temporary files created by tests; removed in tearDown.
	 *
	 * @var array<int, string>
	 */
	private array $temp_files = array();

	/**
	 * Clean up any sessions and files created during a test.
	 */
	public function tearDown(): void {
		foreach ( $this->sessions as $session ) {
			$session->delete();
		}
		$this->sessions = array();
		foreach ( $this->temp_files as $path ) {
			if ( file_exists( $path ) ) {
				wp_delete_file( $path );
			}
		}
		$this->temp_files = array();
		parent::tearDown();
	}

	/**
	 * Create a session and remember it for tearDown.
	 *
	 * @param int    $user_id User ID.
	 * @param string $file    Staged file path to store.
	 * @return ImportSession
	 */
	private function make_session( int $user_id, string $file = '/tmp/sample.csv' ): ImportSession {
		$session          = ImportSession::create(
			$user_id,
			$file,
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
	 * Write a small CSV inside the uploads directory and track it for tearDown.
	 *
	 * @return string Absolute path.
	 */
	private function make_staged_file(): string {
		$upload_dir = wp_upload_dir();
		$file       = trailingslashit( $upload_dir['basedir'] ) . 'wc-fulfillments-import-' . wp_generate_uuid4() . '.csv';
		file_put_contents( $file, "a,b,c\n" ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents -- Test fixture write.
		$this->temp_files[] = $file;
		return $file;
	}

	/**
	 * Action Scheduler arguments the session schedules its cleanup with.
	 *
	 * @param ImportSession $session Session.
	 * @return array{0:int, 1:string, 2:string, 3:int}
	 */
	private function cleanup_args( ImportSession $session ): array {
		return array( $session->user_id(), $session->token(), $session->file(), $session->attachment_id() );
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
	 * @testdox create() stores the staged file's real size and mtime for the staged-file integrity check.
	 */
	public function test_create_stores_file_size_and_mtime(): void {
		$path               = wp_tempnam( 'wc-fulfillments-session-' );
		$this->temp_files[] = $path;
		file_put_contents( $path, "order_number,tracking_number,shipment_provider\n1,TRK-1,ups\n" ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents -- Test fixture write.

		$session = $this->make_session( 31, $path );

		$loaded = ImportSession::load( 31, $session->token() );
		$this->assertNotNull( $loaded );
		$this->assertSame( (int) filesize( $path ), $loaded->file_size() );
		$this->assertGreaterThan( 0, $loaded->file_size() );
		$this->assertSame( (int) filemtime( $path ), $loaded->file_mtime() );
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
	 * @testdox cleanup_abandoned_file() refuses a readable path under ABSPATH that is outside the uploads directory.
	 */
	public function test_cleanup_abandoned_file_refuses_path_under_abspath(): void {
		$file               = ABSPATH . 'wc-fulfillments-import-' . wp_generate_uuid4() . '.csv';
		$this->temp_files[] = $file;
		file_put_contents( $file, "a,b,c\n" ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents -- Test fixture write.

		ImportSession::cleanup_abandoned_file( 75, 'ghost', $file );

		$this->assertFileExists( $file, 'Only files staged in the uploads directory may be cleaned up' );
	}

	/**
	 * @testdox cleanup_abandoned_file() refuses a path in the system temp directory.
	 */
	public function test_cleanup_abandoned_file_refuses_path_in_system_temp_dir(): void {
		$file               = '/tmp/wc-fulfillments-not-in-uploads-' . wp_generate_uuid4() . '.csv';
		$this->temp_files[] = $file;
		file_put_contents( $file, "a,b,c\n" ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents -- Test fixture write outside uploads on purpose.

		ImportSession::cleanup_abandoned_file( 91, 'no-such-token', $file );

		$this->assertFileExists( $file );
	}

	/**
	 * @testdox The cleanup hook callback coerces loosely typed arguments instead of fataling.
	 */
	public function test_cleanup_hook_callback_coerces_arguments(): void {
		$file = $this->make_staged_file();

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
		$file = $this->make_staged_file();

		ImportSession::handle_cleanup_hook( 74, 'ghost', array( $file ) );

		$this->assertFileExists( $file, 'A non-scalar file argument must not be coerced into a path' );
	}

	/**
	 * @testdox cleanup_abandoned_file() deletes the staged file when the session transient is gone.
	 */
	public function test_cleanup_abandoned_file_deletes_when_session_is_gone(): void {
		$file = $this->make_staged_file();

		// Fake an abandoned session: no transient is set for token "ghost".
		ImportSession::cleanup_abandoned_file( 71, 'ghost', $file );

		$this->assertFileDoesNotExist( $file );
	}

	/**
	 * @testdox cleanup_abandoned_file() leaves the file alone and reschedules itself while the session is still active.
	 */
	public function test_cleanup_abandoned_file_reschedules_for_live_session(): void {
		$file    = $this->make_staged_file();
		$session = $this->make_session( 81, $file );
		$args    = $this->cleanup_args( $session );

		// Drop the action create() scheduled so the one asserted below can only come from the cleanup.
		as_unschedule_all_actions( ImportSession::CLEANUP_HOOK, $args, self::CLEANUP_GROUP );
		$this->assertFalse( as_next_scheduled_action( ImportSession::CLEANUP_HOOK, $args, self::CLEANUP_GROUP ) );

		ImportSession::cleanup_abandoned_file( 81, $session->token(), $file );

		$this->assertFileExists( $file );
		$next = as_next_scheduled_action( ImportSession::CLEANUP_HOOK, $args, self::CLEANUP_GROUP );
		$this->assertIsInt( $next, 'A live session must get its cleanup scheduled again' );
		$this->assertGreaterThan( time() + 4 * MINUTE_IN_SECONDS, $next, 'The retry must wait at least the grace period' );
		$this->assertLessThanOrEqual( time() + HOUR_IN_SECONDS + 5 * MINUTE_IN_SECONDS, $next, 'The retry must not wait longer than TTL plus the grace period' );
	}

	/**
	 * @testdox cleanup_abandoned_file() also deletes the attachment post created for the staged file.
	 */
	public function test_cleanup_abandoned_file_deletes_attachment(): void {
		$file = $this->make_staged_file();

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
		$file  = $this->make_staged_file();
		$other = $this->make_staged_file();

		$attachment_id = wp_insert_attachment(
			array(
				'post_title'     => 'Unrelated attachment',
				'post_mime_type' => 'text/csv',
			),
			$other
		);
		$this->assertGreaterThan( 0, $attachment_id );

		try {
			ImportSession::cleanup_abandoned_file( 73, 'ghost', $file, (int) $attachment_id );

			$this->assertFileDoesNotExist( $file );
			$this->assertNotNull( get_post( $attachment_id ), 'An attachment for a different file must not be deleted' );
		} finally {
			wp_delete_attachment( (int) $attachment_id, true );
		}
	}

	/**
	 * @testdox delete() removes the session record, its index pointer and its cleanup action.
	 */
	public function test_delete_removes_session_and_cleanup_action(): void {
		$session = $this->make_session( 33 );
		$token   = $session->token();
		$args    = $this->cleanup_args( $session );

		$timeout = (int) get_option( '_transient_timeout_wc_fulfillment_import_33_' . $token );
		$this->assertGreaterThan( time(), $timeout );
		$this->assertLessThanOrEqual( time() + HOUR_IN_SECONDS, $timeout, 'A session must expire within an hour' );
		$this->assertIsInt( as_next_scheduled_action( ImportSession::CLEANUP_HOOK, $args, self::CLEANUP_GROUP ) );

		$session->delete();
		$this->sessions = array();

		$this->assertNull( ImportSession::load( 33, $token ) );
		$this->assertNull( ImportSession::active_for_user( 33 ), 'The index pointer must be cleared' );
		$this->assertFalse( as_next_scheduled_action( ImportSession::CLEANUP_HOOK, $args, self::CLEANUP_GROUP ), 'The cleanup action must be unscheduled' );
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

	/**
	 * @testdox Creating a second session for the same user unschedules the first session's cleanup action.
	 */
	public function test_create_unschedules_prior_session_cleanup_action(): void {
		$first      = $this->make_session( 45, $this->make_staged_file() );
		$first_args = $this->cleanup_args( $first );
		$this->assertIsInt( as_next_scheduled_action( ImportSession::CLEANUP_HOOK, $first_args, self::CLEANUP_GROUP ) );

		$second = $this->make_session( 45, $this->make_staged_file() );

		$this->assertFalse( as_next_scheduled_action( ImportSession::CLEANUP_HOOK, $first_args, self::CLEANUP_GROUP ), 'The replaced session must not keep a cleanup action' );
		$this->assertIsInt( as_next_scheduled_action( ImportSession::CLEANUP_HOOK, $this->cleanup_args( $second ), self::CLEANUP_GROUP ) );
	}
}
