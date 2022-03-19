<?php
/**
 * NoteTraits tests
 *
 * @package WooCommerce\Admin\Tests\Notes
 */

use Automattic\WooCommerce\Admin\Notes\NotesUnavailableException;
use Automattic\WooCommerce\Admin\Notes\Note;
use Automattic\WooCommerce\Admin\Notes\NoteTraits;

/**
 * Class WC_Admin_Tests_NoteTraits
 */
class WC_Admin_Tests_NoteTraits extends WC_Unit_Test_Case {

	/** Host the traits class we are testing */
	use NoteTraits;

	/**
	 * Constant required to use NoteTraits.
	 */
	const NOTE_NAME = 'Test note';

	/**
	 * @doesNotPerformAssertions
	 * @dataProvider methods_causing_exception_if_data_store_cannot_be_loaded_provider
	 * @dataProvider methods_never_causing_exception_provider
	 *
	 * @param callable $callback Tested NoteTraits method.
	 */
	public function test_no_exception_is_thrown_if_data_store_can_be_loaded( $callback ) {
		$callback();
	}

	/**
	 * @dataProvider methods_causing_exception_if_data_store_cannot_be_loaded_provider
	 *
	 * @param callable $callback Tested NoteTraits method.
	 */
	public function test_exception_is_thrown_if_data_store_cannot_be_loaded( $callback ) {
		add_filter( 'woocommerce_data_stores', '__return_empty_array' );
		$this->expectException( NotesUnavailableException::class );
		$callback();
		remove_filter( 'woocommerce_data_stores', '__return_empty_array' );
	}

	/**
	 * @doesNotPerformAssertions
	 * @dataProvider methods_never_causing_exception_provider
	 *
	 * @param callable $callback Tested NoteTraits method.
	 */
	public function test_no_exception_is_thrown_even_if_data_store_cannot_be_loaded( $callback ) {
		add_filter( 'woocommerce_data_stores', '__return_empty_array' );
		$callback();
		remove_filter( 'woocommerce_data_stores', '__return_empty_array' );
	}

	/**
	 * Test should convert to array if it's a stdClass object.
	 * @return void
	 */
	public function test_possibly_convert_object_to_array() {
		$this->assertEquals( self::possibly_convert_object_to_array( new stdClass() ), array() );
		$this->assertEquals( self::possibly_convert_object_to_array( 1 ), 1 );
		$this->assertEquals( self::possibly_convert_object_to_array( 'string' ), 'string' );
	}


	/**
	 * Method required to use NoteTraits.
	 *
	 * @return Note
	 */
	public static function get_note() {
		return new Note();
	}

	/**
	 * Data provider providing methods that should throw an exception
	 * only if the "admin-note" data store cannot be loaded.
	 *
	 * @return array[]
	 */
	public function methods_causing_exception_if_data_store_cannot_be_loaded_provider() {
		return array(
			array(
				function () {
					self::note_exists();
				},
			),
			array(
				function () {
					self::can_be_added();
				},
			),
			array(
				function () {
					self::possibly_add_note();
				},
			),
			array(
				function () {
					self::add_note();
				},
			),
			array(
				function () {
					self::possibly_delete_note();
				},
			),
			array(
				function () {
					self::possibly_update_note();
				},
			),
			array(
				function () {
					self::has_note_been_actioned();
				},
			),
		);
	}

	/**
	 * Test update_note_field_if_changed method should update note1 and return true.
	 * @return void
	 */
	public function test_should_update_note_name_and_return_true() {
		// Test name is different from note1.
		$note1 = $this->createMock( Note::class );
		$note1->expects( $this->once() )
			->method( 'get_name' )
			->willReturn( 'old name' );

		$note1->expects( $this->once() )
			->method( 'set_name' )
			->with( 'new name' );

		$note2 = $this->createMock( Note::class );
		$note2->expects( $this->exactly( 2 ) )
			->method( 'get_name' )
			->willReturn( 'new name' );

		$this->assertTrue( self::update_note_field_if_changed( $note1, $note2, 'name' ) );

		// Test actions are the same as note1.
		$actions1 = array(
			(object) array(
				'id'            => '1',
				'name'          => 'action1',
				'label'         => 'wca',
				'query'         => '?query',
				'status'        => Note::E_WC_ADMIN_NOTE_ACTIONED,
				'actioned_text' => '',
				'nonce_name'    => 0,
				'nonce_action'  => 0,
			),
			(object) array(
				'id'            => '2',
				'name'          => 'action2',
				'label'         => 'wca',
				'query'         => '?query',
				'status'        => Note::E_WC_ADMIN_NOTE_ACTIONED,
				'actioned_text' => 'text',
				'nonce_name'    => 0,
				'nonce_action'  => 0,
			),
		);

		$actions2 = array(
			(object) array(
				'name'          => 'action1',
				'label'         => 'wca',
				'query'         => '?query',
				'status'        => Note::E_WC_ADMIN_NOTE_ACTIONED,
				'actioned_text' => '',
				'nonce_name'    => null,
				'nonce_action'  => null,
			),
			(object) array(
				'name'          => 'new action',
				'label'         => 'wca',
				'query'         => '?query',
				'status'        => Note::E_WC_ADMIN_NOTE_ACTIONED,
				'actioned_text' => 'text',
				'nonce_name'    => null,
				'nonce_action'  => null,
			),
		);

		$note1->expects( $this->once() )
			->method( 'get_actions' )
			->willReturn( $actions1 );

		$note1->expects( $this->once() )
			->method( 'set_actions' )
			->with( $actions2 );

		$note2 = $this->createMock( Note::class );
		$note2->expects( $this->exactly( 2 ) )
			->method( 'get_actions' )
			->willReturn( $actions2 );

		$this->assertTrue( self::update_note_field_if_changed( $note1, $note2, 'actions' ) );
	}

	/**
	 * Test update_note_field_if_changed method should not update the note1 and return false.
	 * @return void
	 */
	public function test_should_not_update_note_name_and_return_false() {
		// Test name is same as note1.
		$note1 = $this->createMock( Note::class );
		$note1->expects( $this->once() )
			->method( 'get_name' )
			->willReturn( 'name' );

		$note1->expects( $this->exactly( 0 ) )
			->method( 'set_name' );

		$note2 = $this->createMock( Note::class );
		$note2->expects( $this->once() )
			->method( 'get_name' )
			->willReturn( 'name' );

		$this->assertFalse( self::update_note_field_if_changed( $note1, $note2, 'name' ) );

		// Test actions are the same as note1.
		$actions1 = array(
			(object) array(
				'id'            => '1',
				'name'          => 'action1',
				'label'         => 'wca',
				'query'         => '?query',
				'status'        => Note::E_WC_ADMIN_NOTE_ACTIONED,
				'actioned_text' => '',
				'nonce_name'    => 0,
				'nonce_action'  => 0,
			),
			(object) array(
				'id'            => '2',
				'name'          => 'action2',
				'label'         => 'wca',
				'query'         => '?query',
				'status'        => Note::E_WC_ADMIN_NOTE_ACTIONED,
				'actioned_text' => 'text',
				'nonce_name'    => 0,
				'nonce_action'  => 0,
			),
		);

		$actions2 = array(
			(object) array(
				'name'          => 'action1',
				'label'         => 'wca',
				'query'         => '?query',
				'status'        => Note::E_WC_ADMIN_NOTE_ACTIONED,
				'actioned_text' => '',
				'nonce_name'    => null,
				'nonce_action'  => null,
			),
			(object) array(
				'name'          => 'action2',
				'label'         => 'wca',
				'query'         => '?query',
				'status'        => Note::E_WC_ADMIN_NOTE_ACTIONED,
				'actioned_text' => 'text',
				'nonce_name'    => null,
				'nonce_action'  => null,
			),
		);

		$note1->expects( $this->once() )
			->method( 'get_actions' )
			->willReturn( $actions1 );

		$note1->expects( $this->exactly( 0 ) )
			->method( 'set_actions' );

		$note2 = $this->createMock( Note::class );
		$note2->expects( $this->exactly( 1 ) )
			->method( 'get_actions' )
			->willReturn( $actions2 );

		$this->assertFalse( self::update_note_field_if_changed( $note1, $note2, 'actions' ) );
	}

	/**
	 * Data provider providing methods that should not throw
	 * an exception regardless of the data store being available.
	 *
	 * @return array[]
	 */
	public function methods_never_causing_exception_provider() {
		return array(
			array(
				function () {
					self::wc_admin_active_for( 123 );
				},
			),
		);
	}

}
