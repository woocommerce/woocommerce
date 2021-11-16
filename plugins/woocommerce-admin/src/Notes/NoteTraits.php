<?php
/**
 * WC Admin Note Traits
 *
 * WC Admin Note Traits class that houses shared functionality across notes.
 */

namespace Automattic\WooCommerce\Admin\Notes;

use Automattic\WooCommerce\Admin\WCAdminHelper;

defined( 'ABSPATH' ) || exit;

/**
 * NoteTraits class.
 */
trait NoteTraits {
	/**
	 * Test how long WooCommerce Admin has been active.
	 *
	 * @param int $seconds Time in seconds to check.
	 * @return bool Whether or not WooCommerce admin has been active for $seconds.
	 */
	private static function wc_admin_active_for( $seconds ) {
		return WCAdminHelper::is_wc_admin_active_for( $seconds );
	}

	/**
	 * Test if WooCommerce Admin has been active within a pre-defined range.
	 *
	 * @param string $range range available in WC_ADMIN_STORE_AGE_RANGES.
	 * @param int    $custom_start custom start in range.
	 * @return bool Whether or not WooCommerce admin has been active within the range.
	 */
	private static function is_wc_admin_active_in_date_range( $range, $custom_start = null ) {
		return WCAdminHelper::is_wc_admin_active_in_date_range( $range, $custom_start );
	}

	/**
	 * Check if the note has been previously added.
	 *
	 * @throws NotesUnavailableException Throws exception when notes are unavailable.
	 */
	public static function note_exists() {
		$data_store = Notes::load_data_store();
		$note_ids   = $data_store->get_notes_with_name( self::NOTE_NAME );
		return ! empty( $note_ids );
	}

	/**
	 * Checks if a note can and should be added.
	 *
	 * @return bool
	 * @throws NotesUnavailableException Throws exception when notes are unavailable.
	 */
	public static function can_be_added() {
		$note = self::get_note();

		if ( ! $note instanceof Note && ! $note instanceof WC_Admin_Note ) {
			return;
		}

		if ( self::note_exists() ) {
			return false;
		}

		if (
			'no' === get_option( 'woocommerce_show_marketplace_suggestions', 'yes' ) &&
			Note::E_WC_ADMIN_NOTE_MARKETING === $note->get_type()
		) {
			return false;
		}

		return true;
	}

	/**
	 * Add the note if it passes predefined conditions.
	 *
	 * @throws NotesUnavailableException Throws exception when notes are unavailable.
	 */
	public static function possibly_add_note() {
		$note = self::get_note();

		if ( ! self::can_be_added() ) {
			return;
		}

		$note->save();
	}

	/**
	 * Alias this method for backwards compatibility.
	 *
	 * @throws NotesUnavailableException Throws exception when notes are unavailable.
	 */
	public static function add_note() {
		self::possibly_add_note();
	}

	/**
	 * Should this note exist? (Default implementation is generous. Override as needed.)
	 */
	public static function is_applicable() {
		return true;
	}

	/**
	 * Delete this note if it is not applicable, unless has been soft-deleted or actioned already.
	 */
	public static function delete_if_not_applicable() {
		if ( ! self::is_applicable() ) {
			$data_store = Notes::load_data_store();
			$note_ids   = $data_store->get_notes_with_name( self::NOTE_NAME );

			if ( ! empty( $note_ids ) ) {
				$note = Notes::get_note( $note_ids[0] );

				if ( ! $note->get_is_deleted() && ( Note::E_WC_ADMIN_NOTE_ACTIONED !== $note->get_status() ) ) {
					return self::possibly_delete_note();
				}
			}
		}
	}

	/**
	 * Possibly delete the note, if it exists in the database. Note that this
	 * is a hard delete, for where it doesn't make sense to soft delete or
	 * action the note.
	 *
	 * @throws NotesUnavailableException Throws exception when notes are unavailable.
	 */
	public static function possibly_delete_note() {
		$data_store = Notes::load_data_store();
		$note_ids   = $data_store->get_notes_with_name( self::NOTE_NAME );

		foreach ( $note_ids as $note_id ) {
			$note = Notes::get_note( $note_id );

			if ( $note ) {
				$data_store->delete( $note );
			}
		}
	}

	/**
	 * Get if the note has been actioned.
	 *
	 * @return bool
	 * @throws NotesUnavailableException Throws exception when notes are unavailable.
	 */
	public static function has_note_been_actioned() {
		$data_store = Notes::load_data_store();
		$note_ids   = $data_store->get_notes_with_name( self::NOTE_NAME );

		if ( ! empty( $note_ids ) ) {
			$note = Notes::get_note( $note_ids[0] );

			if ( Note::E_WC_ADMIN_NOTE_ACTIONED === $note->get_status() ) {
				return true;
			}
		}

		return false;
	}
}
