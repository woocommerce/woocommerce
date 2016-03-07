<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Abstract Rest Terms Controler Class
 *
 * @author   WooThemes
 * @category API
 * @package  WooCommerce/Abstracts
 * @version  2.6.0
 */
abstract class WC_REST_Terms_Controller extends WP_REST_Controller {

	/**
	 * Route base.
	 *
	 * @var string
	 */
	protected $rest_base = '';

	/**
	 * Taxonomy.
	 *
	 * @var string
	 */
	protected $taxonomy = '';

	/**
	 * Register the routes for terms.
	 */
	public function register_routes() {
		register_rest_route( WC_API::REST_API_NAMESPACE, '/' . $this->rest_base, array(
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_items' ),
				'permission_callback' => array( $this, 'get_items_permissions_check' ),
				'args'                => $this->get_collection_params(),
			),
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'create_item' ),
				'permission_callback' => array( $this, 'create_item_permissions_check' ),
				'args'                => array_merge( $this->get_endpoint_args_for_item_schema( WP_REST_Server::CREATABLE ), array(
					'name' => array(
						'required' => true,
					),
				) ),
			),
			'schema' => array( $this, 'get_public_item_schema' ),
		));

		register_rest_route( WC_API::REST_API_NAMESPACE, '/' . $this->rest_base . '/(?P<id>[\d]+)', array(
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_item' ),
				'permission_callback' => array( $this, 'get_item_permissions_check' ),
				'args'                => array(
					'context'         => $this->get_context_param( array( 'default' => 'view' ) ),
				),
			),
			array(
				'methods'             => WP_REST_Server::EDITABLE,
				'callback'            => array( $this, 'update_item' ),
				'permission_callback' => array( $this, 'update_item_permissions_check' ),
				'args'                => $this->get_endpoint_args_for_item_schema( WP_REST_Server::EDITABLE ),
			),
			array(
				'methods'             => WP_REST_Server::DELETABLE,
				'callback'            => array( $this, 'delete_item' ),
				'permission_callback' => array( $this, 'delete_item_permissions_check' ),
				'args'                => array(
					'force' => array(
						'default'     => false,
						'description' => __( 'Required to be true, as resource does not support trashing.', 'woocommerce' ),
					),
				),
			),
			'schema' => array( $this, 'get_public_item_schema' ),
		) );
	}

	/**
	 * Check if a given request has access to read the terms.
	 *
	 * @param  WP_REST_Request $request Full details about the request.
	 * @return WP_Error|boolean
	 */
	public function get_items_permissions_check( $request ) {
		$taxonomy = get_taxonomy( $this->taxonomy );

		if ( 'edit' === $request['context'] && ! current_user_can( $taxonomy->cap->edit_terms ) ) {
			return new WP_Error( 'woocommerce_rest_forbidden_context', __( 'Sorry, you cannot view this resource with edit context.', 'woocommerce' ), array( 'status' => rest_authorization_required_code() ) );
		}

		return true;
	}

	/**
	 * Check if a given request has access to create a term.
	 *
	 * @param  WP_REST_Request $request Full details about the request.
	 * @return WP_Error|boolean
	 */
	public function create_item_permissions_check( $request ) {
		$taxonomy = get_taxonomy( $this->taxonomy );

		if ( ! current_user_can( $taxonomy->cap->manage_terms ) ) {
			return new WP_Error( 'woocommerce_rest_cannot_create', __( 'Sorry, you cannot create new resource.', 'woocommerce' ), array( 'status' => rest_authorization_required_code() ) );
		}

		return true;
	}

	/**
	 * Check if a given request has access to read a term.
	 *
	 * @param  WP_REST_Request $request Full details about the request.
	 * @return WP_Error|boolean
	 */
	public function get_item_permissions_check( $request ) {
		return $this->get_items_permissions_check( $request );
	}

	/**
	 * Check if a given request has access to update a term.
	 *
	 * @param  WP_REST_Request $request Full details about the request.
	 * @return WP_Error|boolean
	 */
	public function update_item_permissions_check( $request ) {
		$term = get_term( (int) $request['id'], $this->taxonomy );
		if ( ! $term ) {
			return new WP_Error( "woocommerce_rest_{$this->taxonomy}_term_invalid", __( "Resource doesn't exist.", 'woocommerce' ), array( 'status' => 404 ) );
		}

		$taxonomy = get_taxonomy( $this->taxonomy );
		if ( ! current_user_can( $taxonomy->cap->edit_terms ) ) {
			return new WP_Error( 'woocommerce_rest_cannot_update', __( 'Sorry, you cannot update resource.', 'woocommerce' ), array( 'status' => rest_authorization_required_code() ) );
		}

		return true;
	}

	/**
	 * Check if a given request has access to delete a term.
	 *
	 * @param  WP_REST_Request $request Full details about the request.
	 * @return WP_Error|boolean
	 */
	public function delete_item_permissions_check( $request ) {
		$term = get_term( (int) $request['id'], $this->taxonomy );
		if ( ! $term ) {
			return new WP_Error( "woocommerce_rest_{$this->taxonomy}_term_invalid", __( "Resource doesn't exist.", 'woocommerce' ), array( 'status' => 404 ) );
		}

		$taxonomy = get_taxonomy( $this->taxonomy );
		if ( ! current_user_can( $taxonomy->cap->delete_terms ) ) {
			return new WP_Error( 'woocommerce_rest_cannot_delete', __( 'Sorry, you cannot delete resource.', 'woocommerce' ), array( 'status' => rest_authorization_required_code() ) );
		}

		return true;
	}

	/**
	 * Get terms associated with a taxonomy.
	 *
	 * @param WP_REST_Request $request Full details about the request
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_items( $request ) {
		$prepared_args = array(
			'exclude'    => $request['exclude'],
			'include'    => $request['include'],
			'order'      => $request['order'],
			'orderby'    => $request['orderby'],
			'product'    => $request['product'],
			'hide_empty' => $request['hide_empty'],
			'number'     => $request['per_page'],
			'search'     => $request['search'],
			'slug'       => $request['slug'],
		);

		if ( ! empty( $request['offset'] ) ) {
			$prepared_args['offset'] = $request['offset'];
		} else {
			$prepared_args['offset']  = ( $request['page'] - 1 ) * $prepared_args['number'];
		}

		$taxonomy = get_taxonomy( $this->taxonomy );

		if ( $taxonomy->hierarchical && isset( $request['parent'] ) ) {
			if ( 0 === $request['parent'] ) {
				// Only query top-level terms.
				$prepared_args['parent'] = 0;
			} else {
				if ( $request['parent'] ) {
					$prepared_args['parent'] = $request['parent'];
				}
			}
		}

		/**
		 * Filter the query arguments, before passing them to `get_terms()`.
		 *
		 * Enables adding extra arguments or setting defaults for a terms
		 * collection request.
		 *
		 * @see https://developer.wordpress.org/reference/functions/get_terms/
		 *
		 * @param array           $prepared_args Array of arguments to be
		 *                                       passed to get_terms.
		 * @param WP_REST_Request $request       The current request.
		 */
		$prepared_args = apply_filters( "woocommerce_rest_{$this->taxonomy}_query", $prepared_args, $request );

		if ( ! empty( $prepared_args['product'] )  ) {
			$query_result = $this->get_terms_for_product( $prepared_args );
			$total_terms = $this->total_terms;
		} else {
			$query_result = get_terms( $this->taxonomy, $prepared_args );

			$count_args = $prepared_args;
			unset( $count_args['number'] );
			unset( $count_args['offset'] );
			$total_terms = wp_count_terms( $this->taxonomy, $count_args );

			// Ensure we don't return results when offset is out of bounds.
			// See https://core.trac.wordpress.org/ticket/35935
			if ( $prepared_args['offset'] >= $total_terms ) {
				$query_result = array();
			}

			// wp_count_terms can return a falsy value when the term has no children.
			if ( ! $total_terms ) {
				$total_terms = 0;
			}
		}
		$response = array();
		foreach ( $query_result as $term ) {
			$data = $this->prepare_item_for_response( $term, $request );
			$response[] = $this->prepare_response_for_collection( $data );
		}

		$response = rest_ensure_response( $response );

		// Store pagation values for headers then unset for count query.
		$per_page = (int) $prepared_args['number'];
		$page = ceil( ( ( (int) $prepared_args['offset'] ) / $per_page ) + 1 );

		$response->header( 'X-WP-Total', (int) $total_terms );
		$max_pages = ceil( $total_terms / $per_page );
		$response->header( 'X-WP-TotalPages', (int) $max_pages );

		$base = add_query_arg( $request->get_query_params(), rest_url( '/' . WC_API::REST_API_NAMESPACE . '/' . $this->rest_base ) );
		if ( $page > 1 ) {
			$prev_page = $page - 1;
			if ( $prev_page > $max_pages ) {
				$prev_page = $max_pages;
			}
			$prev_link = add_query_arg( 'page', $prev_page, $base );
			$response->link_header( 'prev', $prev_link );
		}
		if ( $max_pages > $page ) {
			$next_page = $page + 1;
			$next_link = add_query_arg( 'page', $next_page, $base );
			$response->link_header( 'next', $next_link );
		}

		return $response;
	}

	/**
	 * Create a single term for a taxonomy.
	 *
	 * @param WP_REST_Request $request Full details about the request
	 * @return WP_REST_Request|WP_Error
	 */
	public function create_item( $request ) {
		$name = $request['name'];
		$args = array();

		if ( isset( $request['description'] ) ) {
			$args['description'] = $request['description'];
		}
		if ( isset( $request['slug'] ) ) {
			$args['slug'] = $request['slug'];
		}

		if ( isset( $request['parent'] ) ) {
			if ( ! is_taxonomy_hierarchical( $this->taxonomy ) ) {
				return new WP_Error( 'woocommerce_rest_taxonomy_not_hierarchical', __( 'Can not set resource parent, taxonomy is not hierarchical.', 'woocommerce' ), array( 'status' => 400 ) );
			}

			$parent = get_term( (int) $request['parent'], $this->taxonomy );

			if ( ! $parent ) {
				return new WP_Error( 'woocommerce_rest_term_invalid', __( "Parent resource doesn't exist.", 'woocommerce' ), array( 'status' => 404 ) );
			}

			$args['parent'] = $parent->term_id;
		}

		$term = wp_insert_term( $name, $this->taxonomy, $args );
		if ( is_wp_error( $term ) ) {

			// If we're going to inform the client that the term exists, give them the identifier
			// they can actually use.
			if ( ( $term_id = $term->get_error_data( 'term_exists' ) ) ) {
				$existing_term = get_term( $term_id, $this->taxonomy );
				$term->add_data( $existing_term->term_id, 'term_exists' );
			}

			return $term;
		}

		$term = get_term( $term['term_id'], $this->taxonomy );

		$this->update_additional_fields_for_object( $term, $request );

		// Add term data.
		$meta_fields = $this->update_term_meta_fields( $term, $request );
		if ( is_wp_error( $meta_fields ) ) {
			return $meta_fields;
		}

		/**
		 * Fires after a single term is created or updated via the REST API.
		 *
		 * @param WP_Term         $term      Inserted Term object.
		 * @param WP_REST_Request $request   Request object.
		 * @param boolean         $creating  True when creating term, false when updating.
		 */
		do_action( "woocommerce_rest_insert_{$this->taxonomy}", $term, $request, true );

		$request->set_param( 'context', 'view' );
		$response = $this->prepare_item_for_response( $term, $request );
		$response = rest_ensure_response( $response );
		$response->set_status( 201 );
		$response->header( 'Location', rest_url( '/' . WC_API::REST_API_NAMESPACE . '/' . $this->rest_base . '/' . $term->term_id ) );

		return $response;
	}

	/**
	 * Get a single term from a taxonomy.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Request|WP_Error
	 */
	public function get_item( $request ) {
		$term = get_term( (int) $request['id'], $this->taxonomy );

		if ( ! $term || $term->taxonomy !== $this->taxonomy ) {
			return new WP_Error( 'woocommerce_rest_term_invalid', __( "Resource doesn't exist.", 'woocommerce' ), array( 'status' => 404 ) );
		}

		if ( is_wp_error( $term ) ) {
			return $term;
		}

		$response = $this->prepare_item_for_response( $term, $request );

		return rest_ensure_response( $response );
	}

	/**
	 * Update a single term from a taxonomy
	 *
	 * @param WP_REST_Request $request Full details about the request
	 * @return WP_REST_Request|WP_Error
	 */
	public function update_item( $request ) {

		$prepared_args = array();
		if ( isset( $request['name'] ) ) {
			$prepared_args['name'] = $request['name'];
		}
		if ( isset( $request['description'] ) ) {
			$prepared_args['description'] = $request['description'];
		}
		if ( isset( $request['slug'] ) ) {
			$prepared_args['slug'] = $request['slug'];
		}

		if ( isset( $request['parent'] ) ) {
			if ( ! is_taxonomy_hierarchical( $this->taxonomy ) ) {
				return new WP_Error( 'woocommerce_rest_taxonomy_not_hierarchical', __( 'Can not set resource parent, taxonomy is not hierarchical.', 'woocommerce' ), array( 'status' => 400 ) );
			}

			$parent = get_term( (int) $request['parent'], $this->taxonomy );

			if ( ! $parent ) {
				return new WP_Error( 'woocommerce_rest_term_invalid', __( "Parent resource doesn't exist.", 'woocommerce' ), array( 'status' => 400 ) );
			}

			$prepared_args['parent'] = $parent->term_id;
		}

		$term = get_term( (int) $request['id'], $this->taxonomy );

		// Only update the term if we haz something to update.
		if ( ! empty( $prepared_args ) ) {
			$update = wp_update_term( $term->term_id, $term->taxonomy, $prepared_args );
			if ( is_wp_error( $update ) ) {
				return $update;
			}
		}

		$term = get_term( (int) $request['id'], $this->taxonomy );

		$this->update_additional_fields_for_object( $term, $request );

		// Update term data.
		$meta_fields = $this->update_term_meta_fields( $term, $request );
		if ( is_wp_error( $meta_fields ) ) {
			return $meta_fields;
		}

		/**
		 * Fires after a single term is created or updated via the REST API.
		 *
		 * @param WP_Term         $term      Inserted Term object.
		 * @param WP_REST_Request $request   Request object.
		 * @param boolean         $creating  True when creating term, false when updating.
		 */
		do_action( "woocommerce_rest_insert_{$this->taxonomy}", $term, $request, false );

		$request->set_param( 'context', 'view' );
		$response = $this->prepare_item_for_response( $term, $request );
		return rest_ensure_response( $response );
	}

	/**
	 * Delete a single term from a taxonomy.
	 *
	 * @param WP_REST_Request $request Full details about the request
	 * @return WP_REST_Response|WP_Error
	 */
	public function delete_item( $request ) {
		$force = isset( $request['force'] ) ? (bool) $request['force'] : false;

		// We don't support trashing for this type, error out.
		if ( ! $force ) {
			return new WP_Error( 'woocommerce_rest_trash_not_supported', __( 'Resource does not support trashing.', 'woocommerce' ), array( 'status' => 501 ) );
		}

		$term = get_term( (int) $request['id'], $this->taxonomy );
		$request->set_param( 'context', 'view' );
		$response = $this->prepare_item_for_response( $term, $request );

		$retval = wp_delete_term( $term->term_id, $term->taxonomy );
		if ( ! $retval ) {
			return new WP_Error( 'woocommerce_rest_cannot_delete', __( 'The resource cannot be deleted.', 'woocommerce' ), array( 'status' => 500 ) );
		}

		/**
		 * Fires after a single term is deleted via the REST API.
		 *
		 * @param WP_Term          $term     The deleted term.
		 * @param WP_REST_Response $response The response data.
		 * @param WP_REST_Request  $request  The request sent to the API.
		 */
		do_action( "woocommerce_rest_delete_{$this->taxonomy}", $term, $response, $request );

		return $response;
	}

	/**
	 * Prepare links for the request.
	 *
	 * @param object $term Term object.
	 * @return array Links for the given term.
	 */
	protected function prepare_links( $term ) {
		$base = '/' . WC_API::REST_API_NAMESPACE . '/' . $this->rest_base;
		$links = array(
			'self' => array(
				'href' => rest_url( trailingslashit( $base ) . $term->term_id ),
			),
			'collection' => array(
				'href' => rest_url( $base ),
			),
		);

		if ( $term->parent ) {
			$parent_term = get_term( (int) $term->parent, $term->taxonomy );
			if ( $parent_term ) {
				$links['up'] = array(
					'href' => rest_url( trailingslashit( $base ) . $parent_term->term_id ),
				);
			}
		}

		return $links;
	}

	/**
	 * Update term meta fields.
	 *
	 * @param WP_Term $term
	 * @param WP_REST_Request $request
	 * @return bool|WP_Error
	 */
	protected function update_term_meta_fields( $term, $request ) {
		return true;
	}

	/**
	 * Get the terms attached to a product.
	 *
	 * This is an alternative to `get_terms()` that uses `get_the_terms()`
	 * instead, which hits the object cache. There are a few things not
	 * supported, notably `include`, `exclude`. In `self::get_items()` these
	 * are instead treated as a full query.
	 *
	 * @param array $prepared_args Arguments for `get_terms()`.
	 * @return array List of term objects. (Total count in `$this->total_terms`).
	 */
	protected function get_terms_for_product( $prepared_args ) {
		$query_result = get_the_terms( $prepared_args['product'], $this->taxonomy );
		if ( empty( $query_result ) ) {
			$this->total_terms = 0;
			return array();
		}

		// get_items() verifies that we don't have `include` set, and default.
		// ordering is by `name`.
		if ( ! in_array( $prepared_args['orderby'], array( 'name', 'none', 'include' ) ) ) {
			switch ( $prepared_args['orderby'] ) {
				case 'id' :
					$this->sort_column = 'term_id';
					break;

				case 'slug' :
				case 'term_group' :
				case 'description' :
				case 'count' :
					$this->sort_column = $prepared_args['orderby'];
					break;
			}
			usort( $query_result, array( $this, 'compare_terms' ) );
		}
		if ( strtolower( $prepared_args['order'] ) !== 'asc' ) {
			$query_result = array_reverse( $query_result );
		}

		// Pagination.
		$this->total_terms = count( $query_result );
		$query_result = array_slice( $query_result, $prepared_args['offset'], $prepared_args['number'] );

		return $query_result;
	}

	/**
	 * Comparison function for sorting terms by a column.
	 *
	 * Uses `$this->sort_column` to determine field to sort by.
	 *
	 * @param stdClass $left Term object.
	 * @param stdClass $right Term object.
	 * @return int <0 if left is higher "priority" than right, 0 if equal, >0 if right is higher "priority" than left.
	 */
	protected function compare_terms( $left, $right ) {
		$col       = $this->sort_column;
		$left_val  = $left->$col;
		$right_val = $right->$col;

		if ( is_int( $left_val ) && is_int( $right_val ) ) {
			return $left_val - $right_val;
		}

		return strcmp( $left_val, $right_val );
	}

	/**
	 * Get the query params for collections
	 *
	 * @return array
	 */
	public function get_collection_params() {
		$query_params = parent::get_collection_params();
		$taxonomy = get_taxonomy( $this->taxonomy );

		$query_params['context']['default'] = 'view';

		$query_params['exclude'] = array(
			'description'        => __( 'Ensure result set excludes specific ids.', 'woocommerce' ),
			'type'               => 'array',
			'default'            => array(),
			'sanitize_callback'  => 'wp_parse_id_list',
		);
		$query_params['include'] = array(
			'description'        => __( 'Limit result set to specific ids.', 'woocommerce' ),
			'type'               => 'array',
			'default'            => array(),
			'sanitize_callback'  => 'wp_parse_id_list',
		);
		if ( ! $taxonomy->hierarchical ) {
			$query_params['offset'] = array(
				'description'        => __( 'Offset the result set by a specific number of items.', 'woocommerce' ),
				'type'               => 'integer',
				'sanitize_callback'  => 'absint',
				'validate_callback'  => 'rest_validate_request_arg',
			);
		}
		$query_params['order']      = array(
			'description'           => __( 'Order sort attribute ascending or descending.', 'woocommerce' ),
			'type'                  => 'string',
			'sanitize_callback'     => 'sanitize_key',
			'default'               => 'asc',
			'enum'                  => array(
				'asc',
				'desc',
			),
			'validate_callback'     => 'rest_validate_request_arg',
		);
		$query_params['orderby']    = array(
			'description'           => __( 'Sort collection by resource attribute.', 'woocommerce' ),
			'type'                  => 'string',
			'sanitize_callback'     => 'sanitize_key',
			'default'               => 'name',
			'enum'                  => array(
				'id',
				'include',
				'name',
				'slug',
				'term_group',
				'description',
				'count',
			),
			'validate_callback'     => 'rest_validate_request_arg',
		);
		$query_params['hide_empty'] = array(
			'description'           => __( 'Whether to hide resources not assigned to any products.', 'woocommerce' ),
			'type'                  => 'boolean',
			'default'               => false,
			'validate_callback'     => 'rest_validate_request_arg',
		);
		if ( $taxonomy->hierarchical ) {
			$query_params['parent'] = array(
				'description'        => __( 'Limit result set to resources assigned to a specific parent.', 'woocommerce' ),
				'type'               => 'integer',
				'sanitize_callback'  => 'absint',
				'validate_callback'  => 'rest_validate_request_arg',
			);
		}
		$query_params['product'] = array(
			'description'           => __( 'Limit result set to resources assigned to a specific product.', 'woocommerce' ),
			'type'                  => 'integer',
			'default'               => null,
			'validate_callback'     => 'rest_validate_request_arg',
		);
		$query_params['slug']    = array(
			'description'        => __( 'Limit result set to resources with a specific slug.', 'woocommerce' ),
			'type'               => 'string',
			'validate_callback'  => 'rest_validate_request_arg',
		);
		return $query_params;
	}
}
