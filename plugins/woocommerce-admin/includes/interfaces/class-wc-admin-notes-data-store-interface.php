<?php
/**
 * Admin (Dashboard) Notes Data Store Interface
 *
 * @version  3.5.0
 * @package  WooCommerce Admin/Interface
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WooCommerce Admin (Dashboard) Notes data store interface.
 *
 * @since 3.5.0
 */
interface WC_Admin_Notes_Data_Store_Interface {

	/**
	 * Mark a note as actioned
	 *
	 * @param WC_Admin_Note $note Note object.
	 */
	public function mark_note_as_actioned( &$note );

	/**
	 * Mark a note for reminder
	 *
	 * @param WC_Admin_Note $note Note object.
	 * @param int           $days Number of days until reminder
	 */
	public function mark_note_for_reminder( &$note, $days = 3 );
}
