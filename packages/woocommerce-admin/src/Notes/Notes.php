<?php
/**
 * Handles storage and retrieval of admin notes
 */

namespace Automattic\WooCommerce\Admin\Notes;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
/**
 * Admin Notes class.
 */
class Notes {
	/**
	 * Hook used for recurring "unsnooze" action.
	 */
	const UNSNOOZE_HOOK = 'wc_admin_unsnooze_admin_notes';

	/**
	 * Hook appropriate actions.
	 */
	public static function init() {
		add_action( 'admin_init', array( __CLASS__, 'schedule_unsnooze_notes' ) );
		add_action( 'admin_init', array( __CLASS__, 'possibly_delete_survey_notes' ) );
		add_action( 'update_option_woocommerce_show_marketplace_suggestions', array( __CLASS__, 'possibly_delete_marketing_notes' ), 10, 2 );
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
			try {
				$note                               = new Note( $raw_note );
				$note_id                            = $note->get_id();
				$notes[ $note_id ]                  = $note->get_data();
				$notes[ $note_id ]['name']          = $note->get_name( $context );
				$notes[ $note_id ]['type']          = $note->get_type( $context );
				$notes[ $note_id ]['locale']        = $note->get_locale( $context );
				$notes[ $note_id ]['title']         = $note->get_title( $context );
				$notes[ $note_id ]['content']       = $note->get_content( $context );
				$notes[ $note_id ]['content_data']  = $note->get_content_data( $context );
				$notes[ $note_id ]['status']        = $note->get_status( $context );
				$notes[ $note_id ]['source']        = $note->get_source( $context );
				$notes[ $note_id ]['date_created']  = $note->get_date_created( $context );
				$notes[ $note_id ]['date_reminder'] = $note->get_date_reminder( $context );
				$notes[ $note_id ]['actions']       = $note->get_actions( $context );
				$notes[ $note_id ]['layout']        = $note->get_layout( $context );
				$notes[ $note_id ]['image']         = $note->get_image( $context );
				$notes[ $note_id ]['is_deleted']    = $note->get_is_deleted( $context );
			} catch ( \Exception $e ) {
				wc_caught_exception( $e, __CLASS__ . '::' . __FUNCTION__, array( $note_id ) );
			}
		}
		return $notes;
	}

	/**
	 * Get admin note using it's ID
	 *
	 * @param int $note_id Note ID.
	 * @return Note|bool
	 */
	public static function get_note( $note_id ) {
		if ( false !== $note_id ) {
			try {
				return new Note( $note_id );
			} catch ( \Exception $e ) {
				wc_caught_exception( $e, __CLASS__ . '::' . __FUNCTION__, array( $note_id ) );
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
	 * @param string|array $names Name(s) to search for.
	 */
	public static function delete_notes_with_name( $names ) {
		if ( is_string( $names ) ) {
			$names = array( $names );
		} elseif ( ! is_array( $names ) ) {
			return;
		}

		$data_store = \WC_Data_Store::load( 'admin-note' );

		foreach ( $names as $name ) {
			$note_ids = $data_store->get_notes_with_name( $name );
			foreach ( (array) $note_ids as $note_id ) {
				$note = self::get_note( $note_id );
				if ( $note ) {
					$note->delete();
				}
			}
		}
	}

	/**
	 * Update a note.
	 *
	 * @param Note  $note              The note that will be updated.
	 * @param array $requested_updates a list of requested updates.
	 */
	public static function update_note( $note, $requested_updates ) {
		$note_changed = false;
		if ( isset( $requested_updates['status'] ) ) {
			$note->set_status( $requested_updates['status'] );
			$note_changed = true;
		}

		if ( isset( $requested_updates['date_reminder'] ) ) {
			$note->set_date_reminder( $requested_updates['date_reminder'] );
			$note_changed = true;
		}

		if ( isset( $requested_updates['is_deleted'] ) ) {
			$note->set_is_deleted( $requested_updates['is_deleted'] );
			$note_changed = true;
		}

		if ( $note_changed ) {
			$note->save();
		}
	}

	/**
	 * Soft delete of a note.
	 *
	 * @param Note $note The note that will be deleted.
	 */
	public static function delete_note( $note ) {
		$note->set_is_deleted( 1 );
		$note->save();
	}

	/**
	 * Soft delete of all the admin notes. Returns the deleted items.
	 *
	 * @return array Array of notes.
	 */
	public static function delete_all_notes() {
		$data_store = \WC_Data_Store::load( 'admin-note' );
		// Here we filter for the same params we are using to show the note list in client side.
		$raw_notes = $data_store->get_notes(
			array(
				'order'      => 'desc',
				'orderby'    => 'date_created',
				'per_page'   => 25,
				'page'       => 1,
				'type'       => array(
					Note::E_WC_ADMIN_NOTE_INFORMATIONAL,
					Note::E_WC_ADMIN_NOTE_MARKETING,
					Note::E_WC_ADMIN_NOTE_WARNING,
					Note::E_WC_ADMIN_NOTE_SURVEY,
				),
				'is_deleted' => 0,
			)
		);

		$notes = array();
		foreach ( (array) $raw_notes as $raw_note ) {
			$note = self::get_note( $raw_note->note_id );
			if ( $note ) {
				self::delete_note( $note );
				array_push( $notes, $note );
			}
		}
		return $notes;
	}

	/**
	 * Clear note snooze status if the reminder date has been reached.
	 */
	public static function unsnooze_notes() {
		$data_store = \WC_Data_Store::load( 'admin-note' );
		$raw_notes  = $data_store->get_notes(
			array(
				'status' => array( Note::E_WC_ADMIN_NOTE_SNOOZED ),
			)
		);
		$now        = new \DateTime();

		foreach ( $raw_notes as $raw_note ) {
			$note = self::get_note( $raw_note->note_id );
			if ( false === $note ) {
				continue;
			}

			$date_reminder = $note->get_date_reminder( 'edit' );

			if ( $date_reminder < $now ) {
				$note->set_status( Note::E_WC_ADMIN_NOTE_UNACTIONED );
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
	 * Unschedule unsnooze notes event.
	 */
	public static function clear_queued_actions() {
		wp_clear_scheduled_hook( self::UNSNOOZE_HOOK );
	}

	/**
	 * Delete marketing notes if marketing has been opted out.
	 *
	 * @param string $old_value Old value.
	 * @param string $value New value.
	 */
	public static function possibly_delete_marketing_notes( $old_value, $value ) {
		if ( 'no' !== $value ) {
			return;
		}

		$data_store = \WC_Data_Store::load( 'admin-note' );
		$notes      = $data_store->get_notes(
			array(
				'type' => array( Note::E_WC_ADMIN_NOTE_MARKETING ),
			)
		);

		foreach ( $notes as $note ) {
			$note = self::get_note( $note->note_id );
			if ( $note ) {
				$note->delete();
			}
		}
	}

	/**
	 * Delete actioned survey notes.
	 */
	public static function possibly_delete_survey_notes() {
		$data_store = \WC_Data_Store::load( 'admin-note' );
		$notes      = $data_store->get_notes(
			array(
				'type'   => array( Note::E_WC_ADMIN_NOTE_SURVEY ),
				'status' => array( 'actioned' ),
			)
		);

		foreach ( $notes as $note ) {
			$note = self::get_note( $note->note_id );
			if ( $note ) {
				$note->set_is_deleted( 1 );
				$note->save();
			}
		}
	}

	/**
	 * Get the status of a given note by name.
	 *
	 * @param string $note_name Name of the note.
	 * @return string|bool The note status.
	 */
	public static function get_note_status( $note_name ) {
		$data_store = \WC_Data_Store::load( 'admin-note' );
		$note_ids   = $data_store->get_notes_with_name( $note_name );

		if ( empty( $note_ids ) ) {
			return false;
		}

		$note = self::get_note( $note_ids[0] );

		return $note->get_status();
	}
}
