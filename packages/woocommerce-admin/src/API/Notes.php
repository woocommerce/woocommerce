<?php
/**
 * REST API Admin Notes controller
 *
 * Handles requests to the admin notes endpoint.
 *
 * @package WooCommerce Admin/API
 */

namespace Automattic\WooCommerce\Admin\API;

defined( 'ABSPATH' ) || exit;

use Automattic\WooCommerce\Admin\Notes\WC_Admin_Note;
use Automattic\WooCommerce\Admin\Notes\WC_Admin_Notes;

/**
 * REST API Admin Notes controller class.
 *
 * @package WooCommerce/API
 * @extends WC_REST_CRUD_Controller
 */
class Notes extends \WC_REST_CRUD_Controller {

	/**
	 * Endpoint namespace.
	 *
	 * @var string
	 */
	protected $namespace = 'wc-analytics';

	/**
	 * Route base.
	 *
	 * @var string
	 */
	protected $rest_base = 'admin/notes';

	/**
	 * Register the routes for admin notes.
	 */
	public function register_routes() {
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base,
			array(
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_items' ),
					'permission_callback' => array( $this, 'get_items_permissions_check' ),
					'args'                => $this->get_collection_params(),
				),
				'schema' => array( $this, 'get_public_item_schema' ),
			)
		);

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/(?P<id>[\d-]+)',
			array(
				'args'   => array(
					'id' => array(
						'description' => __( 'Unique ID for the resource.', 'woocommerce' ),
						'type'        => 'integer',
					),
				),
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_item' ),
					'permission_callback' => array( $this, 'get_item_permissions_check' ),
				),
				array(
					'methods'             => \WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'update_item' ),
					'permission_callback' => array( $this, 'update_items_permissions_check' ),
				),
				'schema' => array( $this, 'get_public_item_schema' ),
			)
		);
	}

	/**
	 * Get a single note.
	 *
	 * @param WP_REST_Request $request Request data.
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_item( $request ) {
		$note = WC_Admin_Notes::get_note( $request->get_param( 'id' ) );

		if ( ! $note ) {
			return new \WP_Error(
				'woocommerce_note_invalid_id',
				__( 'Sorry, there is no resource with that ID.', 'woocommerce' ),
				array( 'status' => 404 )
			);
		}

		if ( is_wp_error( $note ) ) {
			return $note;
		}

		$data = $note->get_data();
		$data = $this->prepare_item_for_response( $data, $request );
		$data = $this->prepare_response_for_collection( $data );

		return rest_ensure_response( $data );
	}

	/**
	 * Get all notes.
	 *
	 * @param WP_REST_Request $request Request data.
	 * @return WP_REST_Response
	 */
	public function get_items( $request ) {
		$query_args = $this->prepare_objects_query( $request );

		$notes = WC_Admin_Notes::get_notes( 'edit', $query_args );

		$data = array();
		foreach ( (array) $notes as $note_obj ) {
			$note   = $this->prepare_item_for_response( $note_obj, $request );
			$note   = $this->prepare_response_for_collection( $note );
			$data[] = $note;
		}

		$response = rest_ensure_response( $data );
		$response->header( 'X-WP-Total', WC_Admin_Notes::get_notes_count( $query_args['type'], $query_args['status'] ) );

		return $response;
	}

	/**
	 * Prepare objects query.
	 *
	 * @param  WP_REST_Request $request Full details about the request.
	 * @return array
	 */
	protected function prepare_objects_query( $request ) {
		$args             = array();
		$args['order']    = $request['order'];
		$args['orderby']  = $request['orderby'];
		$args['per_page'] = $request['per_page'];
		$args['page']     = $request['page'];
		$args['type']     = isset( $request['type'] ) ? $request['type'] : array();
		$args['status']   = isset( $request['status'] ) ? $request['status'] : array();

		if ( 'date' === $args['orderby'] ) {
			$args['orderby'] = 'date_created';
		}

		/**
		 * Filter the query arguments for a request.
		 *
		 * Enables adding extra arguments or setting defaults for a post
		 * collection request.
		 *
		 * @param array           $args    Key value array of query var to query value.
		 * @param WP_REST_Request $request The request used.
		 */
		$args = apply_filters( 'woocommerce_rest_notes_object_query', $args, $request );

		return $args;
	}

	/**
	 * Check whether a given request has permission to read a single note.
	 *
	 * @param  WP_REST_Request $request Full details about the request.
	 * @return WP_Error|boolean
	 */
	public function get_item_permissions_check( $request ) {
		if ( ! wc_rest_check_manager_permissions( 'system_status', 'read' ) ) {
			return new \WP_Error( 'woocommerce_rest_cannot_view', __( 'Sorry, you cannot list resources.', 'woocommerce' ), array( 'status' => rest_authorization_required_code() ) );
		}

		return true;
	}

	/**
	 * Check whether a given request has permission to read notes.
	 *
	 * @param  WP_REST_Request $request Full details about the request.
	 * @return WP_Error|boolean
	 */
	public function get_items_permissions_check( $request ) {
		if ( ! wc_rest_check_manager_permissions( 'system_status', 'read' ) ) {
			return new \WP_Error( 'woocommerce_rest_cannot_view', __( 'Sorry, you cannot list resources.', 'woocommerce' ), array( 'status' => rest_authorization_required_code() ) );
		}

		return true;
	}

	/**
	 * Update a single note.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Request|WP_Error
	 */
	public function update_item( $request ) {
		$note = WC_Admin_Notes::get_note( $request->get_param( 'id' ) );

		if ( ! $note ) {
			return new \WP_Error(
				'woocommerce_note_invalid_id',
				__( 'Sorry, there is no resource with that ID.', 'woocommerce' ),
				array( 'status' => 404 )
			);
		}

		// @todo Status is the only field that can be updated at the moment. We should also implement the "date reminder" setting.
		$note_changed = false;
		if ( ! is_null( $request->get_param( 'status' ) ) ) {
			$note->set_status( $request->get_param( 'status' ) );
			$note_changed = true;
		}

		if ( ! is_null( $request->get_param( 'date_reminder' ) ) ) {
			$note->set_date_reminder( $request->get_param( 'date_reminder' ) );
			$note_changed = true;
		}

		if ( $note_changed ) {
			$note->save();
		}
		return $this->get_item( $request );
	}

	/**
	 * Makes sure the current user has access to WRITE the settings APIs.
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 * @return WP_Error|bool
	 */
	public function update_items_permissions_check( $request ) {
		if ( ! wc_rest_check_manager_permissions( 'settings', 'edit' ) ) {
			return new \WP_Error( 'woocommerce_rest_cannot_edit', __( 'Sorry, you cannot edit this resource.', 'woocommerce' ), array( 'status' => rest_authorization_required_code() ) );
		}
		return true;
	}

	/**
	 * Prepare a path or query for serialization to the client.
	 *
	 * @param string $query The query, path, or URL to transform.
	 * @return string A fully formed URL.
	 */
	public function prepare_query_for_response( $query ) {
		if ( empty( $query ) ) {
			return $query;
		}
		if ( 'https://' === substr( $query, 0, 8 ) ) {
			return $query;
		}
		if ( 'http://' === substr( $query, 0, 7 ) ) {
			return $query;
		}
		if ( '?' === substr( $query, 0, 1 ) ) {
			return admin_url( 'admin.php' . $query );
		}

		return admin_url( $query );
	}

	/**
	 * Prepare a note object for serialization.
	 *
	 * @param array           $data Note data.
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response $response Response data.
	 */
	public function prepare_item_for_response( $data, $request ) {
		$context                   = ! empty( $request['context'] ) ? $request['context'] : 'view';
		$data                      = $this->add_additional_fields_to_object( $data, $request );
		$data['date_created_gmt']  = wc_rest_prepare_date_response( $data['date_created'] );
		$data['date_created']      = wc_rest_prepare_date_response( $data['date_created'], false );
		$data['date_reminder_gmt'] = wc_rest_prepare_date_response( $data['date_reminder'] );
		$data['date_reminder']     = wc_rest_prepare_date_response( $data['date_reminder'], false );
		$data['title']             = stripslashes( $data['title'] );
		$data['content']           = stripslashes( $data['content'] );
		$data['is_snoozable']      = (bool) $data['is_snoozable'];
		foreach ( (array) $data['actions'] as $key => $value ) {
			$data['actions'][ $key ]->label  = stripslashes( $data['actions'][ $key ]->label );
			$data['actions'][ $key ]->url    = $this->prepare_query_for_response( $data['actions'][ $key ]->query );
			$data['actions'][ $key ]->status = stripslashes( $data['actions'][ $key ]->status );
		}
		$data = $this->filter_response_by_context( $data, $context );

		// Wrap the data in a response object.
		$response = rest_ensure_response( $data );
		$response->add_links(
			array(
				'self'       => array(
					'href' => rest_url( sprintf( '/%s/%s/%d', $this->namespace, $this->rest_base, $data['id'] ) ),
				),
				'collection' => array(
					'href' => rest_url( sprintf( '%s/%s', $this->namespace, $this->rest_base ) ),
				),
			)
		);
		/**
		 * Filter a note returned from the API.
		 *
		 * Allows modification of the note data right before it is returned.
		 *
		 * @param WP_REST_Response $response The response object.
		 * @param array            $data The original note.
		 * @param WP_REST_Request  $request  Request used to generate the response.
		 */
		return apply_filters( 'woocommerce_rest_prepare_note', $response, $data, $request );
	}

	/**
	 * Get the query params for collections.
	 *
	 * @return array
	 */
	public function get_collection_params() {
		$params             = array();
		$params['context']  = $this->get_context_param( array( 'default' => 'view' ) );
		$params['order']    = array(
			'description'       => __( 'Order sort attribute ascending or descending.', 'woocommerce' ),
			'type'              => 'string',
			'default'           => 'desc',
			'enum'              => array( 'asc', 'desc' ),
			'validate_callback' => 'rest_validate_request_arg',
		);
		$params['orderby']  = array(
			'description'       => __( 'Sort collection by object attribute.', 'woocommerce' ),
			'type'              => 'string',
			'default'           => 'date',
			'enum'              => array(
				'note_id',
				'date',
				'type',
				'title',
				'status',
			),
			'validate_callback' => 'rest_validate_request_arg',
		);
		$params['page']     = array(
			'description'       => __( 'Current page of the collection.', 'woocommerce' ),
			'type'              => 'integer',
			'default'           => 1,
			'sanitize_callback' => 'absint',
			'validate_callback' => 'rest_validate_request_arg',
			'minimum'           => 1,
		);
		$params['per_page'] = array(
			'description'       => __( 'Maximum number of items to be returned in result set.', 'woocommerce' ),
			'type'              => 'integer',
			'default'           => 10,
			'minimum'           => 1,
			'maximum'           => 100,
			'sanitize_callback' => 'absint',
			'validate_callback' => 'rest_validate_request_arg',
		);
		$params['type']     = array(
			'description'       => __( 'Type of note.', 'woocommerce' ),
			'type'              => 'array',
			'sanitize_callback' => 'wp_parse_slug_list',
			'validate_callback' => 'rest_validate_request_arg',
			'items'             => array(
				'enum' => WC_Admin_Note::get_allowed_types(),
				'type' => 'string',
			),
		);
		$params['status']   = array(
			'description'       => __( 'Status of note.', 'woocommerce' ),
			'type'              => 'array',
			'sanitize_callback' => 'wp_parse_slug_list',
			'validate_callback' => 'rest_validate_request_arg',
			'items'             => array(
				'enum' => WC_Admin_Note::get_allowed_statuses(),
				'type' => 'string',
			),
		);
		return $params;
	}

	/**
	 * Get the note's schema, conforming to JSON Schema.
	 *
	 * @return array
	 */
	public function get_item_schema() {
		$schema = array(
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => 'note',
			'type'       => 'object',
			'properties' => array(
				'id'                => array(
					'description' => __( 'ID of the note record.', 'woocommerce' ),
					'type'        => 'integer',
					'context'     => array( 'view' ),
					'readonly'    => true,
				),
				'name'              => array(
					'description' => __( 'Name of the note.', 'woocommerce' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'type'              => array(
					'description' => __( 'The type of the note (e.g. error, warning, etc.).', 'woocommerce' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'locale'            => array(
					'description' => __( 'Locale used for the note title and content.', 'woocommerce' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'title'             => array(
					'description' => __( 'Title of the note.', 'woocommerce' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'content'           => array(
					'description' => __( 'Content of the note.', 'woocommerce' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'icon'              => array(
					'description' => __( 'Icon (gridicon) for the note.', 'woocommerce' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'content_data'      => array(
					'description' => __( 'Content data for the note. JSON string. Available for re-localization.', 'woocommerce' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'status'            => array(
					'description' => __( 'The status of the note (e.g. unactioned, actioned).', 'woocommerce' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
				'source'            => array(
					'description' => __( 'Source of the note.', 'woocommerce' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'date_created'      => array(
					'description' => __( 'Date the note was created.', 'woocommerce' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'date_created_gmt'  => array(
					'description' => __( 'Date the note was created (GMT).', 'woocommerce' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'date_reminder'     => array(
					'description' => __( 'Date after which the user should be reminded of the note, if any.', 'woocommerce' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true, // @todo Allow date_reminder to be updated.
				),
				'date_reminder_gmt' => array(
					'description' => __( 'Date after which the user should be reminded of the note, if any (GMT).', 'woocommerce' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'is_snoozable'      => array(
					'description' => __( 'Whether or a user can request to be reminded about the note.', 'woocommerce' ),
					'type'        => 'boolean',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'actions'           => array(
					'description' => __( 'An array of actions, if any, for the note.', 'woocommerce' ),
					'type'        => 'array',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
			),
		);
		return $this->add_additional_fields_schema( $schema );
	}
}
