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
 * Class WC_Tests_NoteTraits
 */
class WC_Tests_NoteTraits extends WC_Unit_Test_Case {

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
