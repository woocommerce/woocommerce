<?php
/**
 * Handles storage and retrieval of admin notes
 *
 * @package WooCommerce Admin/Classes
 */

namespace Automattic\WooCommerce\Admin\Notes;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
/**
 * Admin Notes class.
 */
class WC_Admin_Notes {
	/**
	 * Hook used for recurring "unsnooze" action.
	 */
	const UNSNOOZE_HOOK = 'wc_admin_unsnooze_admin_notes';

	/**
	 * Action scheduler group.
	 */
	const QUEUE_GROUP = 'wc-admin-notes';

	/**
	 * Queue instance.
	 *
	 * @var WC_Queue_Interface
	 */
	protected static $queue = null;

	/**
	 * Get queue instance.
	 *
	 * @return WC_Queue_Interface
	 */
	public static function queue() {
		if ( is_null( self::$queue ) ) {
			self::$queue = WC()->queue();
		}

		return self::$queue;
	}

	/**
	 * Hook appropriate actions.
	 */
	public static function init() {
		add_action( 'admin_init', array( __CLASS__, 'schedule_unsnooze_notes' ) );
		add_action( self::UNSNOOZE_HOOK, array( __CLASS__, 'unsnooze_notes' ) );
	}

	/**
	 * Get notes from the database.
	 *
	 * @param string $context Getting notes for what context. Valid values: view, edit.
	 * @param array  $args Arguments to pass to the query( e.g. per_page and page).
	 * @return array Array of arrays.
	 */
	public static function get_notes( $context = 'edit', $args = array() ) {
		$data_store = \WC_Data_Store::load( 'admin-note' );
		$raw_notes  = $data_store->get_notes( $args );
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
			} catch ( \Exception $e ) {
				return false;
			}
		}
		return false;
	}

	/**
	 * Get the total number of notes
	 *
	 * @param string $type Comma separated list of note types.
	 * @param string $status Comma separated list of statuses.
	 * @return int
	 */
	public static function get_notes_count( $type = array(), $status = array() ) {
		$data_store = \WC_Data_Store::load( 'admin-note' );
		return $data_store->get_notes_count( $type, $status );
	}

	/**
	 * Deletes admin notes with a given name.
	 *
	 * @param string $name Name to search for.
	 */
	public static function delete_notes_with_name( $name ) {
		$data_store = \WC_Data_Store::load( 'admin-note' );
		$note_ids   = $data_store->get_notes_with_name( $name );
		foreach ( (array) $note_ids as $note_id ) {
			$note = new WC_Admin_Note( $note_id );
			$note->delete();
		}
	}

	/**
	 * Clear note snooze status if the reminder date has been reached.
	 */
	public static function unsnooze_notes() {
		$data_store = \WC_Data_Store::load( 'admin-note' );
		$raw_notes  = $data_store->get_notes(
			array(
				'status' => array( WC_Admin_Note::E_WC_ADMIN_NOTE_SNOOZED ),
			)
		);
		$now        = new \DateTime();

		foreach ( $raw_notes as $raw_note ) {
			$note          = new WC_Admin_Note( $raw_note );
			$date_reminder = $note->get_date_reminder( 'edit' );

			if ( $date_reminder < $now ) {
				$note->set_status( WC_Admin_Note::E_WC_ADMIN_NOTE_UNACTIONED );
				$note->set_date_reminder( null );
				$note->save();
			}
		}
	}

	/**
	 * Schedule unsnooze notes event.
	 */
	public static function schedule_unsnooze_notes() {
		if ( ! wp_next_scheduled( self::UNSNOOZE_HOOK ) ) {
			wp_schedule_event( time() + 5, 'hourly', self::UNSNOOZE_HOOK );
		}
	}

	/**
	 * Clears all queued actions.
	 */
	public static function clear_queued_actions() {
		$store = \ActionScheduler::store();

		if ( is_a( $store, 'Automattic\WooCommerce\Admin\Overrides\WPPostStore' ) ) {
			// If we're using our data store, call our bespoke deletion method.
			$action_types = array( self::UNSNOOZE_HOOK );
			$store->clear_pending_wcadmin_actions( $action_types );
		} else {
			self::queue()->cancel_all( null, array(), self::QUEUE_GROUP );
		}
	}
}
