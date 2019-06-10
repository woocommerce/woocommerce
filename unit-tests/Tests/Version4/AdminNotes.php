<?php
/**
 * Admin notes REST API Test
 *
 * @package WooCommerce\Tests\API
 */

namespace WooCommerce\RestApi\UnitTests\Tests\Version4;

defined( 'ABSPATH' ) || exit;

use \WooCommerce\RestApi\UnitTests\Helpers\AdminNotesHelper;
use \WC_REST_Unit_Test_Case;
use \WP_REST_Request;

/**
 * Class AdminNotes
 */
class AdminNotes extends WC_REST_Unit_Test_Case {

	/**
	 * Endpoints.
	 *
	 * @var string
	 */
	protected $endpoint = '/wc/v4/admin/notes';

	/**
	 * User variable.
	 *
	 * @var WP_User
	 */
	protected static $user;

	/**
	 * Setup once before running tests.
	 *
	 * @param object $factory Factory object.
	 */
	public static function wpSetUpBeforeClass( $factory ) {
		self::$user = $factory->user->create(
			array(
				'role' => 'administrator',
			)
		);
	}

	/**
	 * Setup test admin notes data. Called before every test.
	 *
	 * @since 3.5.0
	 */
	public function setUp() {
		if ( ! class_exists( '\WC_Admin_Note' ) ) {
			$this->markTestSkipped( 'Skipping admin notes tests - WC_Admin_Note class not found.' );
			return;
		}

		parent::setUp();
		wp_set_current_user( self::$user );
		AdminNotesHelper::reset_notes_dbs();
		AdminNotesHelper::add_notes_for_tests();
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
		$response = $this->server->dispatch( new WP_REST_Request( 'GET', $this->endpoint . '/1' ) );
		$note     = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );

		$this->assertEquals( 1, $note['id'] );
		$this->assertEquals( 'PHPUNIT_TEST_NOTE_NAME', $note['name'] );
		$this->assertEquals( \WC_Admin_Note::E_WC_ADMIN_NOTE_INFORMATIONAL, $note['type'] );
		$this->assertArrayHasKey( 'locale', $note );
		$this->assertEquals( 'PHPUNIT_TEST_NOTE_1_TITLE', $note['title'] );

		$this->assertEquals( 'PHPUNIT_TEST_NOTE_1_CONTENT', $note['content'] );
		$this->assertEquals( 'info', $note['icon'] );
		$this->assertArrayHasKey( 'content_data', $note );
		$this->assertEquals( 1.23, $note['content_data']->amount );
		$this->assertEquals( \WC_Admin_Note::E_WC_ADMIN_NOTE_UNACTIONED, $note['status'] );
		$this->assertEquals( 'PHPUNIT_TEST', $note['source'] );

		$this->assertArrayHasKey( 'date_created', $note );
		$this->assertArrayHasKey( 'date_created_gmt', $note );
		$this->assertArrayHasKey( 'date_reminder', $note );
		$this->assertArrayHasKey( 'date_reminder_gmt', $note );
		$this->assertArrayHasKey( 'actions', $note );

		$this->assertEquals( 'PHPUNIT_TEST_NOTE_1_ACTION_1_SLUG', $note['actions'][0]->name );
		$this->assertEquals( 'http://example.org/wp-admin/admin.php?s=PHPUNIT_TEST_NOTE_1_ACTION_1_URL', $note['actions'][0]->url );
	}

	/**
	 * Test getting a 404 from invalid ID.
	 *
	 * @since 3.5.0
	 */
	public function test_get_invalid_note() {
		$response = $this->server->dispatch( new WP_REST_Request( 'GET', $this->endpoint . '/999' ) );
		$note     = $response->get_data();

		$this->assertEquals( 404, $response->get_status() );
	}

	/**
	 * Test getting a single note without permission. It should fail.
	 *
	 * @since 3.5.0
	 */
	public function test_get_note_without_permission() {
		wp_set_current_user( 0 );
		$response = $this->server->dispatch( new WP_REST_Request( 'GET', $this->endpoint . '/1' ) );
		$this->assertEquals( 401, $response->get_status() );
	}

	/**
	 * Test updating a single note.
	 */
	public function test_update_note() {
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
		wp_set_current_user( 0 );
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
		$response = $this->server->dispatch( new WP_REST_Request( 'GET', $this->endpoint ) );
		$notes    = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( 3, count( $notes ) );
	}

	/**
	 * Test getting notes of a certain type.
	 *
	 * @since 3.5.0
	 */
	public function test_get_warning_notes() {
		$request = new WP_REST_Request( 'GET', $this->endpoint );
		$request->set_query_params( array( 'type' => 'warning' ) );
		$response = $this->server->dispatch( $request );
		$notes    = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( 1, count( $notes ) );
		$this->assertEquals( $notes[0]['title'], 'PHPUNIT_TEST_NOTE_2_TITLE' );
	}


	/**
	 * Test getting notes of a certain status.
	 */
	public function test_get_actioned_notes() {
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
		$request = new WP_REST_Request( 'GET', $this->endpoint );
		$request->set_query_params( array( 'status' => 'snoozed' ) );
		$response = $this->server->dispatch( $request );
		$notes    = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( 1, count( $notes ) );
		$this->assertEquals( $notes[0]['title'], 'PHPUNIT_TEST_NOTE_3_TITLE' );

		// The test snoozed note's reminder date is an hour ago.
		\WC_Admin_Notes::unsnooze_notes();

		$response = $this->server->dispatch( $request );
		$notes    = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEmpty( $notes );
	}

	/**
	 * Test ordering of notes.
	 */
	public function test_order_notes() {
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
		$this->assertEquals( 3, count( $notes ) );
		$this->assertEquals( $notes[0]['title'], 'PHPUNIT_TEST_NOTE_1_TITLE' );
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

		$this->assertEquals( 3, count( $notes ) );
		$this->assertEquals( $notes[0]['status'], \WC_Admin_Note::E_WC_ADMIN_NOTE_UNACTIONED );
		$this->assertEquals( $notes[1]['status'], \WC_Admin_Note::E_WC_ADMIN_NOTE_SNOOZED );
		$this->assertEquals( $notes[2]['status'], \WC_Admin_Note::E_WC_ADMIN_NOTE_ACTIONED );
	}

	/**
	 * Test getting lots of notes without permission. It should fail.
	 *
	 * @since 3.5.0
	 */
	public function test_get_notes_without_permission() {
		wp_set_current_user( 0 );
		$response = $this->server->dispatch( new WP_REST_Request( 'GET', $this->endpoint ) );
		$this->assertEquals( 401, $response->get_status() );
	}

	/**
	 * Test getting the notes schema.
	 *
	 * @since 3.5.0
	 */
	public function test_get_notes_schema() {
		$request    = new WP_REST_Request( 'OPTIONS', $this->endpoint );
		$response   = $this->server->dispatch( $request );
		$data       = $response->get_data();
		$properties = $data['schema']['properties'];

		$this->assertEquals( 16, count( $properties ) );

		$this->assertArrayHasKey( 'id', $properties );
		$this->assertArrayHasKey( 'name', $properties );
		$this->assertArrayHasKey( 'type', $properties );
		$this->assertArrayHasKey( 'locale', $properties );
		$this->assertArrayHasKey( 'title', $properties );

		$this->assertArrayHasKey( 'content', $properties );
		$this->assertArrayHasKey( 'icon', $properties );
		$this->assertArrayHasKey( 'content_data', $properties );
		$this->assertArrayHasKey( 'status', $properties );
		$this->assertArrayHasKey( 'source', $properties );

		$this->assertArrayHasKey( 'date_created', $properties );
		$this->assertArrayHasKey( 'date_created_gmt', $properties );
		$this->assertArrayHasKey( 'date_reminder', $properties );
		$this->assertArrayHasKey( 'date_reminder_gmt', $properties );
		$this->assertArrayHasKey( 'actions', $properties );
		$this->assertArrayHasKey( 'is_snoozable', $properties );
	}
}
