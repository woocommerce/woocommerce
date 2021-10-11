<?php
/**
 * Refund and Returns Policy Page Note Provider.
 *
 * Adds notes to the merchant's inbox concerning the created page.
 *
 * @package WooCommerce
 */

defined( 'ABSPATH' ) || exit;

use Automattic\Jetpack\Constants;
use Automattic\WooCommerce\Admin\Notes\Note;

/**
 * WC_Notes_Refund_Returns.
 */
class WC_Notes_Refund_Returns {
	/**
	 * Name of the note for use in the database.
	 */
	const NOTE_NAME = 'wc-refund-returns-page';

	/**
	 * Maybe add a note to the inbox.
	 *
	 * @param int $page_id The ID of the page.
	 */
	public static function possibly_add_note( $page_id ) {
		$data_store = \WC_Data_Store::load( 'admin-note' );

		// Do we already have this note?
		$note_id = $data_store->get_notes_with_name( self::NOTE_NAME );

		if ( ! empty( $note_id ) ) {
			$note = new Note( $note_id );

			if ( false !== $note || $note::E_WC_ADMIN_NOTE_ACTIONED === $note->get_status() ) {
				// note actioned -> don't show it.
				return;
			}
		}

		// Add note.
		$note = self::get_note( $page_id );
		$note->save();
		delete_option( 'woocommerce_refund_returns_page_created' );
	}

	/**
	 * Get the note.
	 *
	 * @param int $page_id The ID of the page.
	 * @return object $note The note object.
	 */
	public static function get_note( $page_id ) {
		$note = new Note();
		$note->set_title( __( 'Setup a Refund and Returns Policy page to boost your store\'s credibility.', 'woocommerce' ) );
		$note->set_content( __( 'We have created a sample draft Refund and Returns Policy page for you. Please have a look and update it to fit your store.', 'woocommerce' ) );
		$note->set_type( Note::E_WC_ADMIN_NOTE_INFORMATIONAL );
		$note->set_name( self::NOTE_NAME );
		$note->set_content_data( (object) array() );
		$note->set_source( 'woocommerce-core' );
		$note->add_action(
			'notify-refund-returns-page',
			__( 'Edit page', 'woocommerce' ),
			admin_url( sprintf( 'post.php?post=%d&action=edit', (int) $page_id ) )
		);

		return $note;
	}
}
