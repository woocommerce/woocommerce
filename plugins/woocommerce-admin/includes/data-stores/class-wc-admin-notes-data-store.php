<?php

/**
 * WC_Admin_Note_Data_Store class file.
 *
 * @package WooCommerce Admin/Classes
 */

defined( 'ABSPATH' ) || exit;

/**
 * WC Admin Note Data Store (Custom Tables)
 */
class WC_Admin_Notes_Data_Store extends WC_Data_Store_WP implements WC_Admin_Notes_Data_Store_Interface, WC_Object_Data_Store_Interface {
	/**
	 * Method to create a new note in the database.
	 *
	 * @param WC_Admin_Note
	 */
	public function create( &$note ) {
		$note->set_date_created( current_time( 'timestamp' ) );

		global $wpdb;
		$wpdb->insert(
			$wpdb->prefix . 'woocommerce_admin_notes', array(
				'note_type'     => $note->get_type(),
				'note_locale'   => $note->get_locale(),
				'note_title'    => $note->get_title(),
				'note_content'  => $note->get_content(),
				'note_icon'     => $note->get_icon(),
				'note_data'     => $note->get_data(),
				'status'        => $note->get_status(),
				'source'        => $note->get_source(),
				'date_created'  => $note->get_date_created(),
				'date_reminder' => $note->get_date_reminder(),
			)
		);
		$note->set_id( $wpdb->insert_id );
		$note->save_meta_data();
		$note->save_actions( $note );
		$note->apply_changes();

		do_action( 'woocommerce_new_note', $note_id );
	}

	/**
	 * Method to read a note.
	 *
	 * @param WC_Admin_Note
	 */
	public function read( &$note ) {
		global $wpdb;

		$note->set_defaults();
		$note_row = false;

		$note_id = $note->get_id();
		if ( 0 !== $note_id || '0' !== $note_id ) {
			$note_row = $wpdb->get_row(
				$wpdb->prepare(
					"SELECT note_type, note_locale, note_title, note_content, note_icon, note_data, status, source, date_created, date_reminder FROM {$wpdb->prefix}woocommerce_admin_notes WHERE note_id = %d LIMIT 1",
					$note->get_id()
				)
			);
		}

		if ( 0 === $note->get_id() || '0' === $note->get_id() ) {
			$this->read_actions( $note );
			$note->read_meta_data();
			$note->set_object_read( true );
			do_action( 'woocommerce_admin_note_loaded', $note );
		} elseif ( $note_row ) {
			$note->set_type( $note_row->note_type );
			$note->set_locale( $note_row->note_locale );
			$note->set_title( $note_row->note_title );
			$note->set_content( $note_row->note_content );
			$note->set_icon( $note_row->note_icon );
			$note->set_data( $note_row->note_data );
			$note->set_status( $note_row->note_status );
			$note->set_source( $note_row->note_source );
			$note->set_date_created( $note_row->note_date_created );
			$note->set_date_reminder( $note_row->note_date_reminder );
			$this->read_actions( $note );
			$zone->read_meta_data();
			$zone->set_object_read( true );
			do_action( 'woocommerce_admin_note_loaded', $zone );
		} else {
			throw new Exception( __( 'Invalid data store for admin note.', 'woocommerce' ) );
		}
	}

	/**
	 * Updates a note in the database.
	 *
	 * @param WC_Admin_Note
	 */
	public function update( &$note ) {
		global $wpdb;
		if ( $note->get_id() ) {
			$wpdb->update(
				$wpdb->prefix . 'woocommerce_admin_notes', array(
					'note_type'     => $note->get_type(),
					'note_locale'   => $note->get_locale(),
					'note_title'    => $note->get_title(),
					'note_content'  => $note->get_content(),
					'note_icon'     => $note->get_icon(),
					'note_data'     => $note->get_data(),
					'status'        => $note->get_status(),
					'source'        => $note->get_source(),
					'date_created'  => $note->get_date_created(),
					'date_reminder' => $note->get_date_reminder(),
				), array( 'note_id' => $note->get_id() )
			);
		}

		$note->save_meta_data();
		$note->save_actions( $note );
		$note->apply_changes();
		do_action( 'woocommerce_update_note', $note->get_id() );
	}

	/**
	 * Deletes a note from the database.
	 *
	 * @param WC_Admin_Note
	 * @param array $args Array of args to pass to the delete method (not used).
	 */
	public function delete( &$note, $args = array() ) {
		$note_id = $note->get_id();
		if ( $note->get_id() ) {
			global $wpdb;
			$wpdb->delete( $wpdb->prefix . 'woocommerce_admin_notes', array( 'note_id' => $note_id ) );
			$wpdb->delete( $wpdb->prefix . 'woocommerce_admin_note_actions', array( 'note_id' => $note_id ) );
			$note->set_id( null );
		}
		do_action( 'woocommerce_trash_note', $id );
	}

	/**
	 * Read actions from the database.
	 *
	 * @param WC_Admin_Note $note Note object.
	 *
	 */
	private function read_actions( &$note ) {
		global $wpdb;

		$actions = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT action_name, action_label, action_query FROM {$wpdb->prefix}woocommerce_admin_note_actions WHERE note_id = %d",
				$note->get_id()
			)
		);
		if ( $actions ) {
			foreach ( $actions as $action ) {
				$note->add_action( $action->action_name, $action->action_label, $action->action_query );
			}
		}
	}

	/**
	 * Save actions to the database.
	 * This function clears old actions, then re-inserts new if any changes are found.
	 *
	 * @param WC_Admin_Note $note Note object.
	 *
	 * @return bool|void
	 */
	private function save_actions( &$note ) {
		$changed_props = array_keys( $note->get_changes() );
		if ( ! in_array( 'note_actions', $changed_props, true ) ) {
			return false;
		}

		global $wpdb;
		$wpdb->delete( $wpdb->prefix . 'woocommerce_admin_note_actions', array( 'note_id' => $note->get_id() ) );
		foreach ( $note->get_actions( 'edit' ) as $action ) {
			$wpdb->insert(
				$wpdb->prefix . 'woocommerce_admin_note_actions', array(
					'note_id'       => $note->get_id(),
					'action_name'   => $action->action_name,
					'action_label'  => $action->action_label,
					'action_query'  => $action->action_query,
				)
			);
		}
	}

	/**
	 * Mark a note as actioned
	 *
	 * @param WC_Admin_Note $note Note object.
	 */
	public function mark_note_as_actioned( &$note ) {
		$note->set_status( E_WC_ADMIN_NOTE_ACTIONED );
	}

	/**
	 * Mark a note for reminder
	 *
	 * @param WC_Admin_Note $note Note object.
	 * @param int           $days Number of days until reminder
	 */
	public function mark_note_for_reminder( &$note, $days = 3 ) {
		$when = current_time( 'timestamp', 1 ) + intval( $days ) * DAY_IN_SECONDS;
		$note->set_date_reminder( $when );
	}
}
