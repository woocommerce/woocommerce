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
	 * @testdox update_processed() persists the cumulative count across reloads.
	 */
	public function test_update_processed_persists(): void {
		$session = $this->make_session( 21 );
		$session->update_processed( 75 );

		$reloaded = ImportSession::load( 21, $session->token() );
		$this->assertNotNull( $reloaded );
		$this->assertSame( 75, $reloaded->processed() );
	}

	/**
	 * @testdox update_seen_tracking_pairs() persists the cross-chunk dedupe state.
	 */
	public function test_update_seen_tracking_pairs_persists(): void {
		$session = $this->make_session( 22 );
		$session->update_seen_tracking_pairs( array( '100|abc' => true, '200|xyz' => true ) );

		$reloaded = ImportSession::load( 22, $session->token() );
		$this->assertNotNull( $reloaded );
		$this->assertSame( array( '100|abc' => true, '200|xyz' => true ), $reloaded->seen_tracking_pairs() );
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
