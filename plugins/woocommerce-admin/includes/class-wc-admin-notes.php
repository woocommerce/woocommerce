<?php
/**
 * Handles storage and retrieval of admin notes
 *
 * @package WooCommerce Admin/Classes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
/**
 * Admin Notes class.
 */
class WC_Admin_Notes {
	/**
	 * Get notes from the database.
	 *
	 * @param string $context Getting notes for what context. Valid values: view, edit.
	 * @return array Array of arrays.
	 */
	public static function get_notes( $context = 'admin' ) {
		$data_store = WC_Data_Store::load( 'admin-note' );
		$raw_notes  = $data_store->get_notes();
		$notes      = array();
		foreach ( (array) $raw_notes as $raw_note ) {
			$note                               = new WC_Admin_Note( $raw_note );
			$note_id                            = $note->get_id();
			$notes[ $note_id ]                  = $note->get_data();
			$notes[ $note_id ]['name']          = $note->get_name( $context );
			$notes[ $note_id ]['type']          = $note->get_type( $context );
			$notes[ $note_id ]['locale']        = $note->get_locale( $context );
			$notes[ $note_id ]['title']         = $note->get_title( $context );
			$notes[ $note_id ]['content']       = $note->get_content( $context );
			$notes[ $note_id ]['icon']          = $note->get_icon( $context );
			$notes[ $note_id ]['content_data']  = $note->get_content_data( $context );
			$notes[ $note_id ]['status']        = $note->get_status( $context );
			$notes[ $note_id ]['source']        = $note->get_source( $context );
			$notes[ $note_id ]['date_created']  = $note->get_date_created( $context );
			$notes[ $note_id ]['date_reminder'] = $note->get_date_reminder( $context );
			$notes[ $note_id ]['actions']       = $note->get_actions( $context );
		}
		return $notes;
	}

	/**
	 * Get admin note using it's ID
	 *
	 * @param int $note_id Note ID.
	 * @return WC_Admin_Note|bool
	 */
	public static function get_note( $note_id ) {
		if ( false !== $note_id ) {
			try {
				return new WC_Admin_Note( $note_id );
			} catch ( Exception $e ) {
				return false;
			}
		}
		return false;
	}
}
