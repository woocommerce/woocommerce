<?php
/**
 * Admin notes REST API Test
 *
 * @package WooCommerce\Admin\Tests\API
 */

use Automattic\WooCommerce\Admin\Notes\Note;
use Automattic\WooCommerce\Admin\Notes\Notes;

/**
 * Class WC_Admin_Tests_API_Admin_Notes
 */
class WC_Admin_Tests_API_Admin_Notes extends WC_REST_Unit_Test_Case {

	/**
	 * Endpoints.
	 *
	 * @var string
	 */
	protected $endpoint = '/wc-analytics/admin/notes';

	/**
	 * Setup test admin notes data. Called before every test.
	 *
	 * @since 3.5.0
	 */
	public function setUp(): void {
		parent::setUp();

		$this->user = $this->factory->user->create(
			array(
				'role' => 'administrator',
			)
		);

		WC_Helper_Admin_Notes::reset_notes_dbs();
		WC_Helper_Admin_Notes::add_notes_for_tests();
	}

	/**
	 * Test route registration.
	 *
	 * @since 3.5.0
	 */
	public function test_register_routes() {
		$routes = $this->server->get_routes();

		$this->assertArrayHasKey( $this->endpoint, $routes );
	}

	/**
	 * Test getting a single note.
	 *
	 * @since 3.5.0
	 */
	public function test_get_note() {
		wp_set_current_user( $this->user );

		$response = $this->server->dispatch( new WP_REST_Request( 'GET', $this->endpoint . '/1' ) );
		$note     = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );

		$this->assertEquals( 1, $note['id'] );
		$this->assertEquals( 'PHPUNIT_TEST_NOTE_NAME', $note['name'] );
		$this->assertEquals( Note::E_WC_ADMIN_NOTE_INFORMATIONAL, $note['type'] );
		$this->assertArrayHasKey( 'locale', $note );
		$this->assertEquals( 'PHPUNIT_TEST_NOTE_1_TITLE', $note['title'] );

		$this->assertEquals( 'PHPUNIT_TEST_NOTE_1_CONTENT', $note['content'] );
		$this->assertArrayHasKey( 'content_data', $note );
		$this->assertEquals( 1.23, $note['content_data']->amount );
		$this->assertEquals( Note::E_WC_ADMIN_NOTE_UNACTIONED, $note['status'] );
		$this->assertEquals( 'PHPUNIT_TEST', $note['source'] );

		$this->assertArrayHasKey( 'date_created', $note );
		$this->assertArrayHasKey( 'date_created_gmt', $note );
		$this->assertArrayHasKey( 'date_reminder', $note );
		$this->assertArrayHasKey( 'date_reminder_gmt', $note );
		$this->assertArrayHasKey( 'actions', $note );
		$this->assertArrayHasKey( 'layout', $note );
		$this->assertArrayHasKey( 'image', $note );
		$this->assertArrayHasKey( 'is_deleted', $note );

		$this->assertEquals( 'PHPUNIT_TEST_NOTE_1_ACTION_1_SLUG', $note['actions'][0]->name );
		$this->assertEquals( 'http://' . WP_TESTS_DOMAIN . '/wp-admin/admin.php?s=PHPUNIT_TEST_NOTE_1_ACTION_1_URL', $note['actions'][0]->url );
	}

	/**
	 * Test support for nonces in actions.
	 */
	public function test_nonce() {
		wp_set_current_user( $this->user );

		WC_Helper_Admin_Notes::reset_notes_dbs();

		// Create a new note containing an action with a nonce.
		$note = new \Automattic\WooCommerce\Admin\Notes\Note();
		$note->set_name( 'nonce-note' );
		$note->add_action( 'learn-more', __( 'Learn More', 'woocommerce' ), 'https://woo.com/', 'unactioned' );
		$note->add_nonce_to_action( 'learn-more', 'foo', 'bar' );
		$note->save();

		$response = $this->server->dispatch( new WP_REST_Request( 'GET', $this->endpoint . '/1' ) );
		$note     = $response->get_data();

		$expected_url = 'https://woo.com/?bar=' . wp_create_nonce( 'foo' );

		$this->assertSame( $expected_url, $note['actions'][0]->url );
	}

	/**
	 * Tests support for nonces in actions when the URL has existing paarams.
	 */
	public function test_nonce_when_url_has_params() {
		wp_set_current_user( $this->user );

		WC_Helper_Admin_Notes::reset_notes_dbs();

		// Create a new note containing an action with a nonce.
		$note = new \Automattic\WooCommerce\Admin\Notes\Note();
		$note->set_name( 'nonce-note' );
		$note->add_action( 'learn-more', __( 'Learn More', 'woocommerce' ), 'https://example.com/?x=1&y=2', 'unactioned' );
		$note->add_nonce_to_action( 'learn-more', 'foo', 'bar' );
		$note->save();

		$response = $this->server->dispatch( new WP_REST_Request( 'GET', $this->endpoint . '/1' ) );
		$note     = $response->get_data();

		$expected_url = 'https://example.com/?x=1&y=2&bar=' . wp_create_nonce( 'foo' );

		$this->assertSame( $expected_url, $note['actions'][0]->url );
	}

	/**
	 * Test getting a 404 from invalid ID.
	 *
	 * @since 3.5.0
	 */
	public function test_get_invalid_note() {
		wp_set_current_user( $this->user );

		// Suppress deliberately caused errors.
		// phpcs:ignore WordPress.PHP.IniSet.Risky
		$log_file = ini_set( 'error_log', '/dev/null' );

		$response = $this->server->dispatch( new WP_REST_Request( 'GET', $this->endpoint . '/999' ) );
		$note     = $response->get_data();

		// phpcs:ignore WordPress.PHP.IniSet.Risky
		ini_set( 'error_log', $log_file );

		$this->assertEquals( 404, $response->get_status() );
	}

	/**
	 * Test getting a single note without permission. It should fail.
	 *
	 * @since 3.5.0
	 */
	public function test_get_note_without_permission() {
		$response = $this->server->dispatch( new WP_REST_Request( 'GET', $this->endpoint . '/1' ) );
		$this->assertEquals( 401, $response->get_status() );
	}

	/**
	 * Test updating a single note.
	 */
	public function test_update_note() {
		wp_set_current_user( $this->user );

		$response = $this->server->dispatch( new WP_REST_Request( 'GET', $this->endpoint . '/1' ) );
		$note     = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( 'unactioned', $note['status'] );

		$request = new WP_REST_Request( 'PUT', $this->endpoint . '/1' );
		$request->set_body_params(
			array(
				'status' => 'actioned',
			)
		);

		$response = $this->server->dispatch( $request );
		$note     = $response->get_data();
		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( 'actioned', $note['status'] );
	}

	/**
	 * Test updating a single note without permission. It should fail.
	 */
	public function test_update_note_without_permission() {
		$request = new WP_REST_Request( 'PUT', $this->endpoint . '/1' );
		$request->set_body_params(
			array(
				'status' => 'actioned',
			)
		);
		$response = $this->server->dispatch( $request );
		$this->assertEquals( 401, $response->get_status() );
	}

	/**
	 * Test getting lots of notes.
	 *
	 * @since 3.5.0
	 */
	public function test_get_notes() {
		wp_set_current_user( $this->user );

		$response = $this->server->dispatch( new WP_REST_Request( 'GET', $this->endpoint ) );
		$notes    = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( 4, count( $notes ) );
	}

	/**
	 * Test getting notes of a certain type.
	 *
	 * @since 3.5.0
	 */
	public function test_get_warning_notes() {
		wp_set_current_user( $this->user );

		$request = new WP_REST_Request( 'GET', $this->endpoint );
		$request->set_query_params( array( 'type' => 'warning' ) );
		$response = $this->server->dispatch( $request );
		$notes    = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( 1, count( $notes ) );
		$this->assertEquals( $notes[0]['title'], 'PHPUNIT_TEST_NOTE_2_TITLE' );
		$this->assertEquals( $notes[0]['is_snoozable'], true );
	}


	/**
	 * Test getting notes of a certain status.
	 */
	public function test_get_actioned_notes() {
		wp_set_current_user( $this->user );

		$request = new WP_REST_Request( 'GET', $this->endpoint );
		$request->set_query_params( array( 'status' => 'actioned' ) );
		$response = $this->server->dispatch( $request );
		$notes    = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( 1, count( $notes ) );
		$this->assertEquals( $notes[0]['title'], 'PHPUNIT_TEST_NOTE_2_TITLE' );

		$request = new WP_REST_Request( 'GET', $this->endpoint );
		$request->set_query_params( array( 'status' => 'invalid' ) );
		$response = $this->server->dispatch( $request );
		$notes    = $response->get_data();

		// get_notes returns all results since 'status' is not one of actioned or unactioned.
		$this->assertEquals( 3, count( $notes ) );
	}

	/**
	 * Test note "unsnoozing".
	 */
	public function test_note_unsnoozing() {
		wp_set_current_user( $this->user );

		$request = new WP_REST_Request( 'GET', $this->endpoint );
		$request->set_query_params( array( 'status' => 'snoozed' ) );
		$response = $this->server->dispatch( $request );
		$notes    = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( 1, count( $notes ) );
		$this->assertEquals( $notes[0]['title'], 'PHPUNIT_TEST_NOTE_3_TITLE' );

		// The test snoozed note's reminder date is an hour ago.
		Notes::unsnooze_notes();

		$response = $this->server->dispatch( $request );
		$notes    = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEmpty( $notes );
	}

	/**
	 * Test note with source.
	 */
	public function test_get_notes_with_one_source() {
		wp_set_current_user( $this->user );

		$note = new Note();
		$note->set_name( 'note name' );
		$note->set_title( 'note from a-source' );
		$note->set_content( 'PHPUNIT_TEST_NOTE_CONTENT' );
		$note->set_source( 'a-source' );
		$note->save();

		$request = new WP_REST_Request( 'GET', $this->endpoint );
		$request->set_query_params( array( 'source' => 'a-source' ) );
		$response = $this->server->dispatch( $request );
		$notes    = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( 1, count( $notes ) );
		$this->assertEquals( $notes[0]['title'], 'note from a-source' );
	}

	/**
	 * Test note with source.
	 */
	public function test_get_notes_with_multiple_sources() {
		wp_set_current_user( $this->user );

		$note = new Note();
		$note->set_name( 'note name' );
		$note->set_title( 'note from source-1' );
		$note->set_content( 'PHPUNIT_TEST_NOTE_CONTENT' );
		$note->set_source( 'source-1' );
		$note->save();
		$note = new Note();
		$note->set_name( 'note name' );
		$note->set_title( 'note from source-2' );
		$note->set_content( 'PHPUNIT_TEST_NOTE_CONTENT' );
		$note->set_source( 'source-2' );
		$note->save();

		$request = new WP_REST_Request( 'GET', $this->endpoint );
		$request->set_query_params( array( 'source' => 'source-1,source-2' ) );
		$response = $this->server->dispatch( $request );
		$notes    = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( 2, count( $notes ) );
		$this->assertEquals( $notes[0]['title'], 'note from source-1' );
		$this->assertEquals( $notes[1]['title'], 'note from source-2' );
	}

	/**
	 * Test ordering of notes.
	 */
	public function test_order_notes() {
		wp_set_current_user( $this->user );

		$request = new WP_REST_Request( 'GET', $this->endpoint );
		$request->set_query_params(
			array(
				'orderby' => 'title',
				'order'   => 'asc',
			)
		);
		$response = $this->server->dispatch( $request );
		$notes    = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( 4, count( $notes ) );
		$this->assertEquals( $notes[0]['title'], 'PHPUNIT_TEST_NOTE_1_TITLE' );
		$this->assertEquals( $notes[0]['is_snoozable'], false );
		$this->assertEquals( $notes[1]['title'], 'PHPUNIT_TEST_NOTE_2_TITLE' );
		$this->assertEquals( $notes[2]['title'], 'PHPUNIT_TEST_NOTE_3_TITLE' );

		$request = new WP_REST_Request( 'GET', $this->endpoint );
		$request->set_query_params(
			array(
				'orderby' => 'status',
				'order'   => 'desc',
			)
		);
		$response = $this->server->dispatch( $request );
		$notes    = $response->get_data();

		$this->assertEquals( 4, count( $notes ) );
		$this->assertEquals( $notes[0]['status'], Note::E_WC_ADMIN_NOTE_UNACTIONED );
		$this->assertEquals( $notes[1]['status'], Note::E_WC_ADMIN_NOTE_UNACTIONED );
		$this->assertEquals( $notes[2]['status'], Note::E_WC_ADMIN_NOTE_SNOOZED );
		$this->assertEquals( $notes[3]['status'], Note::E_WC_ADMIN_NOTE_ACTIONED );
	}

	/**
	 * Test getting lots of notes without permission. It should fail.
	 *
	 * @since 3.5.0
	 */
	public function test_get_notes_without_permission() {
		$response = $this->server->dispatch( new WP_REST_Request( 'GET', $this->endpoint ) );
		$this->assertEquals( 401, $response->get_status() );
	}

	/**
	 * Test getting the notes schema.
	 *
	 * @since 3.5.0
	 */
	public function test_get_notes_schema() {
		wp_set_current_user( $this->user );

		$request    = new WP_REST_Request( 'OPTIONS', $this->endpoint );
		$response   = $this->server->dispatch( $request );
		$data       = $response->get_data();
		$properties = $data['schema']['properties'];

		$this->assertEquals( 19, count( $properties ) );

		$this->assertArrayHasKey( 'id', $properties );
		$this->assertArrayHasKey( 'name', $properties );
		$this->assertArrayHasKey( 'type', $properties );
		$this->assertArrayHasKey( 'locale', $properties );
		$this->assertArrayHasKey( 'title', $properties );

		$this->assertArrayHasKey( 'content', $properties );
		$this->assertArrayHasKey( 'content_data', $properties );
		$this->assertArrayHasKey( 'status', $properties );
		$this->assertArrayHasKey( 'source', $properties );

		$this->assertArrayHasKey( 'date_created', $properties );
		$this->assertArrayHasKey( 'date_created_gmt', $properties );
		$this->assertArrayHasKey( 'date_reminder', $properties );
		$this->assertArrayHasKey( 'date_reminder_gmt', $properties );
		$this->assertArrayHasKey( 'actions', $properties );
		$this->assertArrayHasKey( 'is_snoozable', $properties );
		$this->assertArrayHasKey( 'layout', $properties );
		$this->assertArrayHasKey( 'image', $properties );
		$this->assertArrayHasKey( 'is_deleted', $properties );
		$this->assertArrayHasKey( 'is_read', $properties );
	}

	/**
	 * Test deleting a single note.
	 */
	public function test_delete_single_note() {
		wp_set_current_user( $this->user );

		$response = $this->server->dispatch( new WP_REST_Request( 'GET', $this->endpoint . '/3' ) );
		$note     = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( false, $note['is_deleted'] );

		$response = $this->server->dispatch( new WP_REST_Request( 'DELETE', $this->endpoint . '/delete/3' ) );
		$note     = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( true, $note['is_deleted'] );
	}

	/**
	 * Test deleting a single note without permission. It should fail.
	 */
	public function test_delete_single_note_without_permission() {
		$response = $this->server->dispatch( new WP_REST_Request( 'DELETE', $this->endpoint . '/delete/3' ) );
		$note     = $response->get_data();

		$this->assertEquals( 401, $response->get_status() );
	}

	/**
	 * Test undoing a single note delete.
	 */
	public function test_undo_single_notes_delete() {
		wp_set_current_user( $this->user );

		$response = $this->server->dispatch( new WP_REST_Request( 'DELETE', $this->endpoint . '/delete/3' ) );
		$note     = $response->get_data();
		$this->assertEquals( true, $note['is_deleted'] );

		$request = new WP_REST_Request( 'PUT', $this->endpoint . '/3' );
		$request->set_body_params(
			array(
				'is_deleted' => '0',
			)
		);

		$response = $this->server->dispatch( $request );
		$note     = $response->get_data();
		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( false, $note['is_deleted'] );
	}

	/**
	 * Test deleting all the notes.
	 */
	public function test_delete_all_notes() {
		wp_set_current_user( $this->user );

		// It deletes only unactioned notes.
		$response = $this->server->dispatch( new WP_REST_Request( 'DELETE', $this->endpoint . '/delete/all' ) );
		$notes    = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( 4, count( $notes ) );
	}

	/**
	 * Test deleting all the notes with a specific status.
	 */
	public function test_delete_all_notes_with_status() {
		wp_set_current_user( $this->user );

		// It deletes only unactioned notes.
		$request = new WP_REST_Request( 'DELETE', $this->endpoint . '/delete/all' );
		$request->set_query_params(
			array(
				'status' => 'unactioned',
			)
		);
		$response = $this->server->dispatch( $request );
		$notes    = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( 2, count( $notes ) );
	}

	/**
	 * Test deleting all the notes without permission. It should fail.
	 */
	public function test_delete_all_notes_without_permission() {
		$response = $this->server->dispatch( new WP_REST_Request( 'DELETE', $this->endpoint . '/delete/all' ) );
		$notes    = $response->get_data();

		$this->assertEquals( 401, $response->get_status() );
	}

	/**
	 * Test undiong all the notes delete.
	 */
	public function test_undo_all_notes_delete() {
		wp_set_current_user( $this->user );

		$response = $this->server->dispatch( new WP_REST_Request( 'DELETE', $this->endpoint . '/delete/all' ) );
		$notes    = $response->get_data();
		$this->assertEquals( 4, count( $notes ) );

		$request = new WP_REST_Request( 'PUT', $this->endpoint . '/update' );
		$request->set_body_params(
			array(
				'noteIds'    => array( '1', '4' ),
				'is_deleted' => '1',
			)
		);

		$response = $this->server->dispatch( $request );
		$notes    = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( 2, count( $notes ) );
	}
}
