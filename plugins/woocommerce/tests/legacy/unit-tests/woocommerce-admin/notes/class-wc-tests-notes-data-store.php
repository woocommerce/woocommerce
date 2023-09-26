<?php
/**
 * Admin notes tests
 *
 * @package WooCommerce\Admin\Tests\Notes
 */

use Automattic\WooCommerce\Admin\Notes\Notes;
use Automattic\WooCommerce\Admin\Notes\Note;
use Automattic\WooCommerce\Admin\Notes\DataStore;

/**
 * Class WC_Admin_Tests_Notes_Data_Store
 */
class WC_Admin_Tests_Notes_Data_Store extends WC_Unit_Test_Case {

	/**
	 * Tests that the read data store method works as expected.
	 */
	public function test_read() {
		$note = new Note();
		$note->set_title( 'PHPUNIT_TEST_NOTE' );
		$note->set_content( 'PHPUNIT_TEST_NOTE_CONTENT' );
		$note->set_type( Note::E_WC_ADMIN_NOTE_INFORMATIONAL );
		$note->set_name( 'PHPUNIT_TEST_NOTE_NAME' );
		$note->set_source( 'PHPUNIT_TEST' );
		$note->set_is_snoozable( false );
		$note->set_layout( 'plain' );
		$note->set_image( '' );
		$note->add_action(
			'PHPUNIT_TEST_ACTION_SLUG',
			'PHPUNIT_TEST_ACTION_LABEL',
			'?s=PHPUNIT_TEST_ACTION_URL'
		);
		$note->set_is_deleted( false );
		$note->save();

		// Load in a new instance of the note.
		$read_note = new Note();
		$read_note->set_id( $note->get_id() );
		WC_Data_Store::load( 'admin-note' )->read( $read_note );

		$this->assertEquals( $note->get_title(), $read_note->get_title() );
		$this->assertEquals( $note->get_content(), $read_note->get_content() );
		$this->assertEquals( $note->get_type(), $read_note->get_type() );
		$this->assertEquals( $note->get_name(), $read_note->get_name() );
		$this->assertEquals( $note->get_source(), $read_note->get_source() );
		$this->assertEquals( $note->get_is_snoozable(), '0' !== $read_note->get_is_snoozable() );
		$this->assertEquals( $note->get_layout(), $read_note->get_layout() );
		$this->assertEquals( $note->get_image(), $read_note->get_image() );
		$this->assertEquals( $note->get_actions(), $read_note->get_actions() );
		$this->assertEquals( $note->get_is_deleted(), $read_note->get_is_deleted() );
	}

	/**
	 * Tests that the read data store method does not fail for invalid content data fields.
	 */
	public function test_read_with_invalid_content_data() {
		global $wpdb;
		$data_store = WC_Data_Store::load( 'admin-note' );

		$note = new Note();
		$note->set_name( 'PHPUNIT_TEST_NOTE_NAME' );
		$note->set_source( 'PHPUNIT_TEST' );
		$note->set_title( 'PHPUNIT_TEST_NOTE' );
		$note->set_content( 'PHPUNIT_TEST_NOTE_CONTENT' );
		$note->save();

		// Make sure that empty content_data does not break the note.
		$wpdb->update(
			$wpdb->prefix . 'wc_admin_notes',
			array(
				'content_data' => '',
			),
			array( 'note_id' => $note->get_id() )
		);
		$data_store->read( $note );

		$this->assertEquals( 'PHPUNIT_TEST_NOTE', $note->get_title() );
		$this->assertEquals( 'PHPUNIT_TEST_NOTE_CONTENT', $note->get_content() );

		// We also want to make sure an empty array does not break the content.
		$wpdb->update(
			$wpdb->prefix . 'wc_admin_notes',
			array(
				'content_data' => '[]',
			),
			array( 'note_id' => $note->get_id() )
		);
		$data_store->read( $note );

		$this->assertEquals( 'PHPUNIT_TEST_NOTE', $note->get_title() );
		$this->assertEquals( 'PHPUNIT_TEST_NOTE_CONTENT', $note->get_content() );
	}

	/**
	 * Tests that the delete_notes_with_name method works when a single name is
	 * passed in.
	 */
	public function test_delete_notes_with_single_name() {
		$note_name_to_delete = 'PHPUNIT_TEST_NOTE_TO_DELETE_' . gmdate( 'U' );
		$note_name_to_keep   = 'PHPUNIT_TEST_NOTE_TO_KEEP_' . gmdate( 'U' );
		$note_names          = array(
			$note_name_to_delete,
			$note_name_to_keep,
		);

		$data_store = WC_Data_Store::load( 'admin-note' );

		// Create notes.
		foreach ( $note_names as $note_name ) {
			for ( $i = 0; $i < 3; $i++ ) {
				$note = new Note();
				$note->set_name( $note_name );
				$note->set_source( 'PHPUNIT_TEST' );
				$note->set_title( 'PHPUNIT_TEST_NOTE' );
				$note->set_content( 'PHPUNIT_TEST_NOTE_CONTENT' );
				$note->save();
			}
		}

		// Make sure that we know that the notes were added properly.
		$this->assertEquals(
			3,
			count( $data_store->get_notes_with_name( $note_name_to_delete ) )
		);
		$this->assertEquals(
			3,
			count( $data_store->get_notes_with_name( $note_name_to_keep ) )
		);

		// Delete the notes.
		Notes::delete_notes_with_name( $note_name_to_delete );

		// Make sure the notes were deleted.
		$this->assertEquals(
			0,
			count( $data_store->get_notes_with_name( $note_name_to_delete ) )
		);
		$this->assertEquals(
			3,
			count( $data_store->get_notes_with_name( $note_name_to_keep ) )
		);
	}

	/**
	 * Tests that the delete_notes_with_name method works when multiple names
	 * are passed in.
	 */
	public function test_delete_notes_with_multiple_names() {
		$note_name_to_delete_1 = 'PHPUNIT_TEST_NOTE_TO_DELETE_1_' . gmdate( 'U' );
		$note_name_to_delete_2 = 'PHPUNIT_TEST_NOTE_TO_DELETE_2_' . gmdate( 'U' );
		$note_name_to_keep     = 'PHPUNIT_TEST_NOTE_TO_KEEP_' . gmdate( 'U' );
		$note_names            = array(
			$note_name_to_delete_1,
			$note_name_to_delete_2,
			$note_name_to_keep,
		);

		$data_store = WC_Data_Store::load( 'admin-note' );

		// Create notes.
		foreach ( $note_names as $note_name ) {
			for ( $i = 0; $i < 3; $i++ ) {
				$note = new Note();
				$note->set_name( $note_name );
				$note->set_source( 'PHPUNIT_TEST' );
				$note->set_title( 'PHPUNIT_TEST_NOTE' );
				$note->set_content( 'PHPUNIT_TEST_NOTE_CONTENT' );
				$note->save();
			}
		}

		// Make sure that we know that the notes were added properly.
		$this->assertEquals(
			3,
			count( $data_store->get_notes_with_name( $note_name_to_delete_1 ) )
		);
		$this->assertEquals(
			3,
			count( $data_store->get_notes_with_name( $note_name_to_delete_2 ) )
		);
		$this->assertEquals(
			3,
			count( $data_store->get_notes_with_name( $note_name_to_keep ) )
		);

		// Delete the notes.
		Notes::delete_notes_with_name(
			array(
				$note_name_to_delete_1,
				$note_name_to_delete_2,
			)
		);

		// Make sure the notes were deleted.
		$this->assertEquals(
			0,
			count( $data_store->get_notes_with_name( $note_name_to_delete_1 ) )
		);
		$this->assertEquals(
			0,
			count( $data_store->get_notes_with_name( $note_name_to_delete_2 ) )
		);
		$this->assertEquals(
			3,
			count( $data_store->get_notes_with_name( $note_name_to_keep ) )
		);
	}

	/**
	 * Test that fatal errors aren't thrown when the Notes datastore read() throws an exception.
	 */
	public function test_read_throws_exception() {
		// Create a test note.
		$note = new Note();
		$note->set_name( 'PHPUNIT_TEST_NOTE_NAME' );
		$note->set_source( 'PHPUNIT_TEST' );
		$note->set_title( 'PHPUNIT_TEST_NOTE_TITLE' );
		$note->set_content( 'PHPUNIT_TEST_NOTE_CONTENT' );
		$note->save();

		$note_id = $note->get_id();

		// Sub in a mock datastore that throws an error on read().
		$mock_datastore = $this->getMockBuilder( \Automattic\WooCommerce\Admin\Notes\DataStore::class )
			->setMethods( array( 'read' ) )
			->getMock();
		$mock_datastore->method( 'read' )->will( $this->throwException( new \Exception() ) );

		// Suppress deliberately caused errors.
		$log_file = ini_set( 'error_log', '/dev/null' );  // phpcs:ignore WordPress.PHP.IniSet.Risky

		$filter_datastore = function() use ( $mock_datastore ) {
			return $mock_datastore;
		};

		add_filter( 'woocommerce_admin-note_data_store', $filter_datastore );

		// Attempt to retrieve the test note.
		$note = Notes::get_note( $note_id );

		remove_filter( 'woocommerce_admin-note_data_store', $filter_datastore );

		ini_set( 'error_log', $log_file );  // phpcs:ignore WordPress.PHP.IniSet.Risky

		$this->assertFalse( $note );
		$this->assertEquals( 1, did_action( 'woocommerce_caught_exception' ) );
	}

	/**
	 * Test order and orderby sanitization in get_notes()
	 */
	public function test_get_notes_order_args_sanitized() {
		global $wpdb;

		$data_store = WC_Data_Store::load( 'admin-note' );

		// Attempt to pass a nonstandard direction.
		// It should be replaced with the default: DESC.
		$data_store->get_notes( array( 'order' => 'increasing' ) );
		$this->assertFalse( stripos( 'increasing', $wpdb->last_query ) );
		$this->assertTrue( stripos( 'DESC', $wpdb->last_query ) >= 0 );

		// Attempt to pass a standard direction in lowercase.
		// It should be replaced with the all-caps equivalent.
		$data_store->get_notes( array( 'order' => 'asc' ) );
		$this->assertFalse( strpos( 'asc', $wpdb->last_query ) );
		$this->assertTrue( strpos( 'ASC', $wpdb->last_query ) >= 0 );

		// Attempt to pass a suspicious string for orderby.
		// It should have backticks stripped from it and get wrapped in backticks, thus causing an error.
		$log_file = ini_set( 'error_log', '/dev/null' );
		$wpdb->hide_errors();
		$this->assertTrue( '' === $wpdb->last_error );

		$data_store->get_notes( array( 'orderby' => '`name`;select 1;' ) );

		$this->assertFalse( stripos( '`name`;select', $wpdb->last_query ) );
		$this->assertTrue( stripos( '`name;select 1;`', $wpdb->last_query ) >= 0 );
		$this->assertFalse( '' === $wpdb->last_error );

		ini_set( 'error_log', $log_file );
		$wpdb->show_errors();
	}

	/**
	 * Test order and orderby sanitization in lookup_notes()
	 */
	public function test_lookup_notes_order_args_sanitized() {
		global $wpdb;

		$data_store = WC_Data_Store::load( 'admin-note' );

		// Attempt to pass a nonstandard direction.
		// It should be replaced with the default: DESC.
		$data_store->lookup_notes( array( 'order' => 'increasing' ) );
		$this->assertFalse( stripos( 'increasing', $wpdb->last_query ) );
		$this->assertTrue( stripos( 'DESC', $wpdb->last_query ) >= 0 );

		// Attempt to pass a standard direction in lowercase.
		// It should be replaced with the all-caps equivalent.
		$data_store->lookup_notes( array( 'order' => 'asc' ) );
		$this->assertFalse( strpos( 'asc', $wpdb->last_query ) );
		$this->assertTrue( strpos( 'ASC', $wpdb->last_query ) >= 0 );

		// Attempt to pass a suspicious string for orderby.
		// It should have backticks stripped from it and get wrapped in backticks, thus causing an error.
		$log_file = ini_set( 'error_log', '/dev/null' );
		$wpdb->hide_errors();
		$this->assertTrue( '' === $wpdb->last_error );

		$data_store->lookup_notes( array( 'orderby' => '`name`;select 1;' ) );

		$this->assertFalse( stripos( '`name`;select', $wpdb->last_query ) );
		$this->assertTrue( stripos( '`name;select 1;`', $wpdb->last_query ) >= 0 );
		$this->assertFalse( '' === $wpdb->last_error );

		ini_set( 'error_log', $log_file );
		$wpdb->show_errors();
	}

	/**
	 * Test that sources are correctly added to where clause.
	 */
	public function test_get_notes_where_clauses_with_sources() {
		$data_store = WC_Data_Store::load( 'admin-note' );

		$where_clause = $data_store->get_notes_where_clauses(
			array(
				'source' => array( 'source', 'another-source' ),
			)
		);
		$this->assertEquals( " AND source IN ('source','another-source') AND is_deleted = 0", $where_clause );
	}

	/**
	 * Test that invalid types are filtered out.
	 */
	public function test_get_notes_where_clauses_with_invalid_types() {
		$data_store = WC_Data_Store::load( 'admin-note' );

		$where_clause = $data_store->get_notes_where_clauses(
			array(
				'type' => array( 'random', Note::E_WC_ADMIN_NOTE_MARKETING ),
			)
		);
		$this->assertEquals(
			sprintf( " AND type IN ('%s') AND is_deleted = 0", Note::E_WC_ADMIN_NOTE_MARKETING ),
			$where_clause
		);
	}

	/**
	 * Test the differences between lookup_notes() and get_notes()
	 */
	public function test_lookup_notes_versus_get_notes() {
		// Create two test notes.
		for ( $i = 0; $i < 2; $i++ ) {
			$note = new Note();
			$note->set_title( 'PHPUNIT_PAGING_TEST_NOTE_' . $i );
			$note->set_content( 'PHPUNIT_PAGING_TEST_NOTE_CONTENT' );
			$note->set_type( Note::E_WC_ADMIN_NOTE_INFORMATIONAL );
			$note->set_name( 'PHPUNIT_PAGING_TEST_NOTE_NAME' );
			$note->set_source( 'PHPUNIT_TEST' );
			$note->set_is_snoozable( false );
			$note->set_layout( 'plain' );
			$note->set_image( '' );
			$note->add_action(
				'PHPUNIT_TEST_ACTION_SLUG',
				'PHPUNIT_TEST_ACTION_LABEL',
				'?s=PHPUNIT_TEST_ACTION_URL'
			);
			$note->set_is_deleted( false );
			$note->save();
		}

		// Add filter for 'woocommerce_note_where_clauses' that should be called only once.
		$filter_hit_count = 0;
		$filter_callback  = function( $arg ) use ( &$filter_hit_count ) {
			$filter_hit_count++;
			return $arg;
		};
		add_filter( 'woocommerce_note_where_clauses', $filter_callback, 1 );

		// Retrieve test notes by lookup_notes and get_notes.
		$data_store = WC_Data_Store::load( 'admin-note' );

		$args = array(
			'source'   => array( 'PHPUNIT_TEST' ),
			'name'     => array( 'PHPUNIT_PAGING_TEST_NOTE_NAME' ),
			'per_page' => 1, // lookup_notes should ignore this.
		);

		$lookup_result = $data_store->lookup_notes( $args );
		$get_result    = $data_store->get_notes( $args );

		// We should have hit the filter exactly once.
		$this->assertEquals( $filter_hit_count, 1 );

		// We should have two notes in the lookup_result, because lookup_notes() ignores the 'per_page' arg.
		$this->assertEquals( count( $lookup_result ), 2 );

		// We should have one note in the get_result, because get_notes() respects the 'per_page' arg.
		$this->assertEquals( count( $get_result ), 1 );

		// Lookup and get should return well-formed Notes.
		$lookup_note_zero = new Note();
		$lookup_note_zero->set_id( $lookup_result[0]->note_id );
		$data_store->read( $lookup_note_zero );

		$get_note_zero = new Note();
		$get_note_zero->set_id( $get_result[0]->note_id );
		$data_store->read( $get_note_zero );

		// The first note in each result set should be the same.
		$this->assertEquals( $lookup_note_zero->get_id(), $get_note_zero->get_id() );
	}

	/**
	 * Test that get_notes properly handles the $context parameter
	 */
	public function test_get_notes_context_param() {
		$data_store = WC_Data_Store::load( 'admin-note' );

		// Create two Notes: one in context and one out of it.
		$test_context        = self::class;
		$global_context      = DataStore::WC_ADMIN_NOTE_OPER_GLOBAL;
		$context_name        = 'PHP_UNIT_IN_CONTEXT_TEST_NOTE';
		$out_of_context_name = 'PHP_UNIT_OUT_OF_CONTEXT_TEST_NOTE';

		foreach ( array( $context_name, $out_of_context_name ) as $note_name ) {
			$note = new Note();
			$note->set_name( $note_name );
			$note->set_title( 'PHPUNIT_CONTEXT_TEST_NOTE' );
			$note->set_content( 'PHPUNIT_CONTEXT_TEST_NOTE_CONTENT' );
			$note->set_type( Note::E_WC_ADMIN_NOTE_INFORMATIONAL );
			$note->set_source( 'PHPUNIT_TEST' );
			$note->set_is_snoozable( false );
			$note->set_layout( 'plain' );
			$note->set_image( '' );
			$note->add_action(
				'PHPUNIT_TEST_ACTION_SLUG',
				'PHPUNIT_TEST_ACTION_LABEL',
				'?s=PHPUNIT_TEST_ACTION_URL'
			);
			$note->set_is_deleted( false );
			$note->save();
		}

		// Add filter for 'woocommerce_note_where_clauses' that applies only in context.
		$context_filter_hit_count = 0;
		$context_filter_callback  = function( $where_clauses, $args, $context ) use ( $test_context, $global_context, $context_name, &$context_filter_hit_count ) {
			if ( $context === $test_context ) {
				$context_filter_hit_count++;
				$where_clauses .= ' AND name = "' . $context_name . '"';
			}
			return $where_clauses;
		};
		add_filter( 'woocommerce_note_where_clauses', $context_filter_callback, 10, 3 );

		// Add filter for 'woocommerce_note_where_clauses' that applies in any context.
		$no_context_filter_hit_count = 0;
		$global_context_received     = null;
		$no_context_filter_callback  = function( $where_clauses, $args, $context ) use ( $test_context, &$global_context_received, &$no_context_filter_hit_count ) {
			// Record the context we get passed in.
			if ( $test_context !== $context ) {
				$global_context_received = $context;
			}

			// Record that we're here.
			$no_context_filter_hit_count++;
			$where_clauses .= ' AND source = "PHPUNIT_TEST"';
			return $where_clauses;
		};
		add_filter( 'woocommerce_note_where_clauses', $no_context_filter_callback, 10, 3 );

		// Get note counts.
		$no_context_note_count   = $data_store->get_notes_count( array( Note::E_WC_ADMIN_NOTE_INFORMATIONAL ), array() );
		$test_context_note_count = $data_store->get_notes_count( array( Note::E_WC_ADMIN_NOTE_INFORMATIONAL ), array(), $test_context );

		// The no context filter should have been hit twice, the context filter once.
		$this->assertEquals( 2, $no_context_filter_hit_count );
		$this->assertEquals( 1, $context_filter_hit_count );

		// There should be only one note that satisfies the context filter.
		$this->assertEquals( 1, $test_context_note_count );

		// There should be more than one note that satisfies the no-context filter.
		$this->assertGreaterThan( 1, $no_context_note_count );

		// Same tests with get_notes().
		$no_context_get_notes   = $data_store->get_notes( array( 'type' => array( Note::E_WC_ADMIN_NOTE_INFORMATIONAL ) ) );
		$test_context_get_notes = $data_store->get_notes( array( 'type' => array( Note::E_WC_ADMIN_NOTE_INFORMATIONAL ) ), $test_context );

		// There should only be one note in the context result set.
		$this->assertEquals( 1, count( $test_context_get_notes ) );

		// There should be more than one note in the no-context result set.
		$this->assertGreaterThan( 1, count( $no_context_get_notes ) );

		// When not explicitly passed, context should default to WC_ADMIN_NOTE_OPER_GLOBAL.
		$this->assertEquals( $global_context, $global_context_received );
	}

	/**
	 * Delete notes created by this class's tests.
	 */
	public function tearDown(): void {
		global $wpdb;

		parent::tearDown();
		$wpdb->query( $wpdb->prepare( 'DELETE n.*, a.* FROM ' . $wpdb->prefix . 'wc_admin_notes n JOIN ' . $wpdb->prefix . 'wc_admin_note_actions a ON n.note_id = a.note_id WHERE n.source = %s', 'PHPUNIT_TEST' ) );
	}
}
