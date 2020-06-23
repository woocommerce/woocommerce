<?php
/**
 * WC_Admin_Note_Data_Store class file.
 *
 * @package WooCommerce Admin/Classes
 */

namespace Automattic\WooCommerce\Admin\Notes;

defined( 'ABSPATH' ) || exit;

/**
 * WC Admin Note Data Store (Custom Tables)
 */
class DataStore extends \WC_Data_Store_WP implements \WC_Object_Data_Store_Interface {
	/**
	 * Method to create a new note in the database.
	 *
	 * @param WC_Admin_Note $note Admin note.
	 */
	public function create( &$note ) {
		$date_created = time();
		$note->set_date_created( $date_created );

		global $wpdb;

		$note_to_be_inserted = array(
			'name'         => $note->get_name(),
			'type'         => $note->get_type(),
			'locale'       => $note->get_locale(),
			'title'        => $note->get_title(),
			'content'      => $note->get_content(),
			'status'       => $note->get_status(),
			'source'       => $note->get_source(),
			'is_snoozable' => (int) $note->get_is_snoozable(),
			'layout'       => $note->get_layout(),
			'image'        => $note->get_image(),
			'is_deleted'   => (int) $note->get_is_deleted(),
		);

		$note_to_be_inserted['content_data']  = wp_json_encode( $note->get_content_data() );
		$note_to_be_inserted['date_created']  = gmdate( 'Y-m-d H:i:s', $date_created );
		$note_to_be_inserted['date_reminder'] = null;

		$wpdb->insert( $wpdb->prefix . 'wc_admin_notes', $note_to_be_inserted );
		$note_id = $wpdb->insert_id;
		$note->set_id( $note_id );
		$note->save_meta_data();
		$this->save_actions( $note );
		$note->apply_changes();

		/**
		 * Fires when an admin note is created.
		 *
		 * @param int $note_id Note ID.
		 */
		do_action( 'woocommerce_note_created', $note_id );
	}

	/**
	 * Method to read a note.
	 *
	 * @param WC_Admin_Note $note Admin note.
	 * @throws \Exception Throws exception when invalid data is found.
	 */
	public function read( &$note ) {
		global $wpdb;

		$note->set_defaults();
		$note_row = false;

		$note_id = $note->get_id();
		if ( 0 !== $note_id || '0' !== $note_id ) {
			$note_row = $wpdb->get_row(
				$wpdb->prepare(
					"SELECT name, type, locale, title, content, content_data, status, source, date_created, date_reminder, is_snoozable, layout, image FROM {$wpdb->prefix}wc_admin_notes WHERE note_id = %d LIMIT 1",
					$note->get_id()
				)
			);
		}

		if ( 0 === $note->get_id() || '0' === $note->get_id() ) {
			$this->read_actions( $note );
			$note->read_meta_data();
			$note->set_object_read( true );

			/**
			 * Fires when an admin note is loaded.
			 *
			 * @param int $note_id Note ID.
			 */
			do_action( 'woocommerce_note_loaded', $note );
		} elseif ( $note_row ) {
			$note->set_name( $note_row->name );
			$note->set_type( $note_row->type );
			$note->set_locale( $note_row->locale );
			$note->set_title( $note_row->title );
			$note->set_content( $note_row->content );

			// The default for 'content_value' used to be an array, so there might be rows with invalid data!
			$content_data = json_decode( $note_row->content_data );
			if ( ! $content_data ) {
				$content_data = new \stdClass();
			} elseif ( is_array( $content_data ) ) {
				$content_data = (object) $content_data;
			}
			$note->set_content_data( $content_data );

			$note->set_status( $note_row->status );
			$note->set_source( $note_row->source );
			$note->set_date_created( $note_row->date_created );
			$note->set_date_reminder( $note_row->date_reminder );
			$note->set_is_snoozable( $note_row->is_snoozable );
			$note->set_layout( $note_row->layout );
			$note->set_image( $note_row->image );
			$this->read_actions( $note );
			$note->read_meta_data();
			$note->set_object_read( true );

			/**
			 * Fires when an admin note is loaded.
			 *
			 * @param int $note_id Note ID.
			 */
			do_action( 'woocommerce_note_loaded', $note );
		} else {
			throw new \Exception( __( 'Invalid data store for admin note.', 'woocommerce' ) );
		}
	}

	/**
	 * Updates a note in the database.
	 *
	 * @param WC_Admin_Note $note Admin note.
	 */
	public function update( &$note ) {
		global $wpdb;

		if ( $note->get_id() ) {
			$date_created           = $note->get_date_created();
			$date_created_timestamp = $date_created->getTimestamp();
			$date_created_to_db     = gmdate( 'Y-m-d H:i:s', $date_created_timestamp );

			$date_reminder = $note->get_date_reminder();
			if ( is_null( $date_reminder ) ) {
				$date_reminder_to_db = null;
			} else {
				$date_reminder_timestamp = $date_reminder->getTimestamp();
				$date_reminder_to_db     = gmdate( 'Y-m-d H:i:s', $date_reminder_timestamp );
			}

			$wpdb->update(
				$wpdb->prefix . 'wc_admin_notes',
				array(
					'name'          => $note->get_name(),
					'type'          => $note->get_type(),
					'locale'        => $note->get_locale(),
					'title'         => $note->get_title(),
					'content'       => $note->get_content(),
					'content_data'  => wp_json_encode( $note->get_content_data() ),
					'status'        => $note->get_status(),
					'source'        => $note->get_source(),
					'date_created'  => $date_created_to_db,
					'date_reminder' => $date_reminder_to_db,
					'is_snoozable'  => $note->get_is_snoozable(),
					'layout'        => $note->get_layout(),
					'image'         => $note->get_image(),
					'is_deleted'    => $note->get_is_deleted(),
				),
				array( 'note_id' => $note->get_id() )
			);
		}

		$note->save_meta_data();
		$this->save_actions( $note );
		$note->apply_changes();

		/**
		 * Fires when an admin note is updated.
		 *
		 * @param int $note_id Note ID.
		 */
		do_action( 'woocommerce_note_updated', $note->get_id() );
	}

	/**
	 * Deletes a note from the database.
	 *
	 * @param WC_Admin_Note $note Admin note.
	 * @param array         $args Array of args to pass to the delete method (not used).
	 */
	public function delete( &$note, $args = array() ) {
		$note_id = $note->get_id();
		if ( $note_id ) {
			global $wpdb;
			$wpdb->delete( $wpdb->prefix . 'wc_admin_notes', array( 'note_id' => $note_id ) );
			$wpdb->delete( $wpdb->prefix . 'wc_admin_note_actions', array( 'note_id' => $note_id ) );
			$note->set_id( null );
		}

		/**
		 * Fires when an admin note is deleted.
		 *
		 * @param int $note_id Note ID.
		 */
		do_action( 'woocommerce_note_deleted', $note_id );
	}

	/**
	 * Read actions from the database.
	 *
	 * @param WC_Admin_Note $note Admin note.
	 */
	private function read_actions( &$note ) {
		global $wpdb;

		$db_actions = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT action_id, name, label, query, status, is_primary
				FROM {$wpdb->prefix}wc_admin_note_actions
				WHERE note_id = %d",
				$note->get_id()
			)
		);

		$note_actions = array();

		if ( $db_actions ) {
			foreach ( $db_actions as $action ) {
				$note_actions[] = (object) array(
					'id'      => (int) $action->action_id,
					'name'    => $action->name,
					'label'   => $action->label,
					'query'   => $action->query,
					'status'  => $action->status,
					'primary' => (bool) $action->is_primary,
				);
			}
		}

		$note->set_actions( $note_actions );
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
		global $wpdb;

		$changed_props = array_keys( $note->get_changes() );

		if ( ! in_array( 'actions', $changed_props, true ) ) {
			return false;
		}

		// Process action removal. Actions are removed from
		// the note if they aren't part of the changeset.
		// See WC_Admin_Note::add_action().
		$changed_actions = $note->get_actions( 'edit' );
		$actions_to_keep = array();

		foreach ( $changed_actions as $action ) {
			if ( ! empty( $action->id ) ) {
				$actions_to_keep[] = (int) $action->id;
			}
		}

		$clear_actions_query = $wpdb->prepare(
			"DELETE FROM {$wpdb->prefix}wc_admin_note_actions WHERE note_id = %d",
			$note->get_id()
		);

		if ( $actions_to_keep ) {
			$clear_actions_query .= sprintf( ' AND action_id NOT IN (%s)', implode( ',', $actions_to_keep ) );
		}

		$wpdb->query( $clear_actions_query ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

		// Update/insert the actions in this changeset.
		foreach ( $changed_actions as $action ) {
			$action_data = array(
				'note_id'    => $note->get_id(),
				'name'       => $action->name,
				'label'      => $action->label,
				'query'      => $action->query,
				'status'     => $action->status,
				'is_primary' => $action->primary,
			);

			$data_format = array(
				'%d',
				'%s',
				'%s',
				'%s',
				'%s',
				'%d',
			);

			if ( ! empty( $action->id ) ) {
				$action_data['action_id'] = $action->id;
				$data_format[]            = '%d';
			}

			$wpdb->replace(
				$wpdb->prefix . 'wc_admin_note_actions',
				$action_data,
				$data_format
			);
		}

		// Update actions from DB (to grab new IDs).
		$this->read_actions( $note );
	}

	/**
	 * Return an ordered list of notes.
	 *
	 * @param array $args Query arguments.
	 * @return array An array of objects containing a note id.
	 */
	public function get_notes( $args = array() ) {
		global $wpdb;

		$defaults = array(
			'per_page' => get_option( 'posts_per_page' ),
			'page'     => 1,
			'order'    => 'DESC',
			'orderby'  => 'date_created',
		);
		$args     = wp_parse_args( $args, $defaults );

		$offset        = $args['per_page'] * ( $args['page'] - 1 );
		$where_clauses = $this->get_notes_where_clauses( $args );

		$query = $wpdb->prepare(
			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			"SELECT note_id, title, content, layout, image FROM {$wpdb->prefix}wc_admin_notes WHERE 1=1{$where_clauses} ORDER BY {$args['orderby']} {$args['order']} LIMIT %d, %d",
			$offset,
			$args['per_page']
		);

		return $wpdb->get_results( $query ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
	}

	/**
	 * Return a count of notes.
	 *
	 * @param string $type Comma separated list of note types.
	 * @param string $status Comma separated list of statuses.
	 * @return array An array of objects containing a note id.
	 */
	public function get_notes_count( $type = array(), $status = array() ) {
		global $wpdb;

		$where_clauses = $this->get_notes_where_clauses(
			array(
				'type'   => $type,
				'status' => $status,
			)
		);

		if ( ! empty( $where_clauses ) ) {
			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			return $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}wc_admin_notes WHERE 1=1{$where_clauses}" );
		}

		return $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}wc_admin_notes" );
	}

	/**
	 * Return where clauses for getting notes by status and type. For use in both the count and listing queries.
	 *
	 *  @param array $args Array of args to pass.
	 * @return string Where clauses for the query.
	 */
	public function get_notes_where_clauses( $args = array() ) {
		$allowed_types    = WC_Admin_Note::get_allowed_types();
		$where_type_array = array();
		if ( isset( $args['type'] ) ) {
			foreach ( $args['type'] as $args_type ) {
				$args_type = trim( $args_type );
				if ( in_array( $args_type, $allowed_types, true ) ) {
					$where_type_array[] = "'" . esc_sql( $args_type ) . "'";
				}
			}
		}

		$allowed_statuses   = WC_Admin_Note::get_allowed_statuses();
		$where_status_array = array();
		if ( isset( $args['status'] ) ) {
			foreach ( $args['status'] as $args_status ) {
				$args_status = trim( $args_status );
				if ( in_array( $args_status, $allowed_statuses, true ) ) {
					$where_status_array[] = "'" . esc_sql( $args_status ) . "'";
				}
			}
		}

		$escaped_is_deleted = '';
		if ( isset( $args['is_deleted'] ) ) {
			$escaped_is_deleted = esc_sql( $args['is_deleted'] );
		}

		$where_name_array = [];
		if ( isset( $args['name'] ) ) {
			foreach ( $args['name'] as $args_name ) {
				$args_name          = trim( $args_name );
				$where_name_array[] = sprintf( "'%s'", esc_sql( $args_name ) );
			}
		}

		$escaped_where_types  = implode( ',', $where_type_array );
		$escaped_where_status = implode( ',', $where_status_array );
		$escaped_where_names  = implode( ',', $where_name_array );
		$where_clauses        = '';

		if ( ! empty( $escaped_where_types ) ) {
			$where_clauses .= " AND type IN ($escaped_where_types)";
		}

		if ( ! empty( $escaped_where_status ) ) {
			$where_clauses .= " AND status IN ($escaped_where_status)";
		}

		if ( ! empty( $escaped_where_names ) ) {
			$where_clauses .= " AND name IN ($escaped_where_names)";
		}

		$where_clauses .= $escaped_is_deleted ? ' AND is_deleted = 1' : ' AND is_deleted = 0';

		/**
		 * Filter the notes WHERE clause before retrieving the data.
		 *
		 * Allows modification of the notes select criterial.
		 *
		 * @param string $where_clauses The generated WHERE clause.
		 * @param array  $args          The original arguments for the request.
		 */
		return apply_filters( 'woocommerce_note_where_clauses', $where_clauses, $args );
	}

	/**
	 * Find all the notes with a given name.
	 *
	 * @param string $name Name to search for.
	 * @return array An array of matching note ids.
	 */
	public function get_notes_with_name( $name ) {
		global $wpdb;
		return $wpdb->get_col(
			$wpdb->prepare(
				"SELECT note_id FROM {$wpdb->prefix}wc_admin_notes WHERE name = %s ORDER BY note_id ASC",
				$name
			)
		);
	}
}
