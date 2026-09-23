<?php
/**
 * REST API Admin Note Action controller
 *
 * Handles requests to the admin note action endpoint.
 */

namespace Automattic\WooCommerce\Admin\API;

defined( 'ABSPATH' ) || exit;

use Automattic\WooCommerce\Admin\Notes\Note;
use Automattic\WooCommerce\Admin\Notes\Notes as NotesFactory;
use Automattic\WooCommerce\Internal\Admin\Notes\NoteActionForbiddenException;

/**
 * REST API Admin Note Action controller class.
 *
 * @internal
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
						'description' => __( 'Unique ID for the Note.', 'woocommerce' ),
						'type'        => 'integer',
					),
					'action_id' => array(
						'description' => __( 'Unique ID for the Note Action.', 'woocommerce' ),
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
		$note = NotesFactory::get_note( $request->get_param( 'note_id' ) );

		if ( ! $note ) {
			return new \WP_Error(
				'woocommerce_note_invalid_id',
				__( 'Sorry, there is no resource with that ID.', 'woocommerce' ),
				array( 'status' => 404 )
			);
		}

		$note->set_is_read( true );
		$note->save();

		$triggered_action = NotesFactory::get_action_by_id( $note, $request->get_param( 'action_id' ) );

		if ( ! $triggered_action ) {
			return new \WP_Error(
				'woocommerce_note_action_invalid_id',
				__( 'Sorry, there is no resource with that ID.', 'woocommerce' ),
				array( 'status' => 404 )
			);
		}

		try {
			$triggered_note = NotesFactory::trigger_note_action( $note, $triggered_action );
		} catch ( NoteActionForbiddenException $e ) {
			// Handlers hooked into `woocommerce_note_action[_*]` throw this typed
			// exception when the current user lacks the per-action capability the
			// handler enforces (the route-level permission check is intentionally
			// coarser). Convert it to a 403 so REST clients get correct HTTP
			// semantics. Any other exception bubbles uncaught so genuine server
			// faults surface as 500s instead of being masked as auth errors.
			//
			// The ignore below matches the same `return.type` issue already captured
			// in the PHPStan baseline for the other two WP_Error returns in this
			// method (broken `@return` docblock — unqualified WP class names resolve
			// in the current namespace). Localized here so the baseline doesn't grow.
			// @phpstan-ignore-next-line return.type -- see rationale above.
			return new \WP_Error(
				'woocommerce_note_action_forbidden',
				$e->getMessage(),
				array( 'status' => rest_authorization_required_code() )
			);
		}

		$data = $triggered_note->get_data();
		$data = $this->prepare_item_for_response( $data, $request );
		$data = $this->prepare_response_for_collection( $data );

		return rest_ensure_response( $data );
	}
}
