<?php
/**
 * REST API Admin Note Action controller
 *
 * Handles requests to the admin note action endpoint.
 *
 * @package WooCommerce Admin/API
 */

namespace Automattic\WooCommerce\Admin\API;

defined( 'ABSPATH' ) || exit;

use \Automattic\WooCommerce\Admin\Notes\WC_Admin_Notes;

/**
 * REST API Admin Note Action controller class.
 *
 * @package WooCommerce/API
 * @extends WC_REST_CRUD_Controller
 */
class NoteActions extends Notes {

	/**
	 * Register the routes for admin notes.
	 */
	public function register_routes() {
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/(?P<note_id>[\d-]+)/action/(?P<action_id>[\d-]+)',
			array(
				'args'   => array(
					'note_id'   => array(
						'description' => __( 'Unique ID for the Note.', 'woocommerce-admin' ),
						'type'        => 'integer',
					),
					'action_id' => array(
						'description' => __( 'Unique ID for the Note Action.', 'woocommerce-admin' ),
						'type'        => 'integer',
					),
				),
				array(
					'methods'             => \WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'trigger_note_action' ),
					// @todo - double check these permissions for taking note actions.
					'permission_callback' => array( $this, 'get_item_permissions_check' ),
				),
				'schema' => array( $this, 'get_public_item_schema' ),
			)
		);
	}

	/**
	 * Trigger a note action.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Request|WP_Error
	 */
	public function trigger_note_action( $request ) {
		$note = WC_Admin_Notes::get_note( $request->get_param( 'note_id' ) );

		if ( ! $note ) {
			return new \WP_Error(
				'woocommerce_note_invalid_id',
				__( 'Sorry, there is no resource with that ID.', 'woocommerce-admin' ),
				array( 'status' => 404 )
			);
		}

		// Find note action by ID.
		$action_id        = $request->get_param( 'action_id' );
		$actions          = $note->get_actions( 'edit' );
		$triggered_action = false;

		foreach ( $actions as $action ) {
			if ( $action->id === $action_id ) {
				$triggered_action = $action;
			}
		}

		if ( ! $triggered_action ) {
			return new \WP_Error(
				'woocommerce_note_action_invalid_id',
				__( 'Sorry, there is no resource with that ID.', 'woocommerce-admin' ),
				array( 'status' => 404 )
			);
		}

		/**
		 * Fires when an admin note action is taken.
		 *
		 * @param string        $name The triggered action name.
		 * @param WC_Admin_Note $note The corresponding Note.
		 */
		do_action( 'woocommerce_note_action', $triggered_action->name, $note );

		/**
		 * Fires when an admin note action is taken.
		 * For more specific targeting of note actions.
		 *
		 * @param WC_Admin_Note $note The corresponding Note.
		 */
		do_action( 'woocommerce_note_action_' . $triggered_action->name, $note );

		// Update the note with the status for this action.
		if ( ! empty( $triggered_action->status ) ) {
			$note->set_status( $triggered_action->status );
		}

		$note->save();

		if ( in_array( $note->get_type(), array( 'error', 'update' ) ) ) {
			$tracks_event = 'wcadmin_store_alert_action';
		} else {
			$tracks_event = 'wcadmin_inbox_action_click';
		}

		wc_admin_record_tracks_event(
			$tracks_event,
			array(
				'note_name'    => $note->get_name(),
				'note_type'    => $note->get_type(),
				'note_title'   => $note->get_title(),
				'note_content' => $note->get_content(),
				'note_icon'    => $note->get_icon(),
				'action_name'  => $triggered_action->name,
				'action_label' => $triggered_action->label,
			)
		);

		$data = $note->get_data();
		$data = $this->prepare_item_for_response( $data, $request );
		$data = $this->prepare_response_for_collection( $data );

		return rest_ensure_response( $data );
	}
}
