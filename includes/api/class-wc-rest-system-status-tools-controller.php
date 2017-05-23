<?php
/**
 * REST API WC System Status Tools Controller
 *
 * Handles requests to the /system_status/tools/* endpoints.
 *
 * @author   WooThemes
 * @category API
 * @package  WooCommerce/API
 * @since    3.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * @package WooCommerce/API
 * @extends WC_REST_Controller
 */
class WC_REST_System_Status_Tools_Controller extends WC_REST_Controller {

	/**
	 * Endpoint namespace.
	 *
	 * @var string
	 */
	protected $namespace = 'wc/v2';

	/**
	 * Route base.
	 *
	 * @var string
	 */
	protected $rest_base = 'system_status/tools';

	/**
	 * Register the routes for /system_status/tools/*.
	 */
	public function register_routes() {
		register_rest_route( $this->namespace, '/' . $this->rest_base, array(
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_items' ),
				'permission_callback' => array( $this, 'get_items_permissions_check' ),
				'args'                => $this->get_collection_params(),
			),
			'schema' => array( $this, 'get_public_item_schema' ),
		) );

		register_rest_route( $this->namespace, '/' . $this->rest_base . '/(?P<id>[\w-]+)', array(
			'args' => array(
				'id' => array(
					'description' => __( 'Unique identifier for the resource.', 'woocommerce' ),
					'type'        => 'string',
				),
			),
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_item' ),
				'permission_callback' => array( $this, 'get_item_permissions_check' ),
			),
			array(
				'methods'             => WP_REST_Server::EDITABLE,
				'callback'            => array( $this, 'update_item' ),
				'permission_callback' => array( $this, 'update_item_permissions_check' ),
				'args'                => $this->get_endpoint_args_for_item_schema( WP_REST_Server::EDITABLE ),
			),
			'schema' => array( $this, 'get_public_item_schema' ),
		) );
	}

	/**
	 * Check whether a given request has permission to view system status tools.
	 *
	 * @param  WP_REST_Request $request Full details about the request.
	 * @return WP_Error|boolean
	 */
	public function get_items_permissions_check( $request ) {
		if ( ! wc_rest_check_manager_permissions( 'system_status', 'read' ) ) {
			return new WP_Error( 'woocommerce_rest_cannot_view', __( 'Sorry, you cannot list resources.', 'woocommerce' ), array( 'status' => rest_authorization_required_code() ) );
		}
		return true;
	}

	/**
	 * Check whether a given request has permission to view a specific system status tool.
	 *
	 * @param  WP_REST_Request $request Full details about the request.
	 * @return WP_Error|boolean
	 */
	public function get_item_permissions_check( $request ) {
		if ( ! wc_rest_check_manager_permissions( 'system_status', 'read' ) ) {
			return new WP_Error( 'woocommerce_rest_cannot_view', __( 'Sorry, you cannot view this resource.', 'woocommerce' ), array( 'status' => rest_authorization_required_code() ) );
		}
		return true;
	}

	/**
	 * Check whether a given request has permission to execute a specific system status tool.
	 *
	 * @param  WP_REST_Request $request Full details about the request.
	 * @return WP_Error|boolean
	 */
	public function update_item_permissions_check( $request ) {
		if ( ! wc_rest_check_manager_permissions( 'system_status', 'edit' ) ) {
			return new WP_Error( 'woocommerce_rest_cannot_update', __( 'Sorry, you cannot update resource.', 'woocommerce' ), array( 'status' => rest_authorization_required_code() ) );
		}
		return true;
	}

	/**
	 * A list of avaiable tools for use in the system status section.
	 * 'button' becomes 'action' in the API.
	 *
	 * @return array
	 */
	public function get_tools() {
		$tools = array(
			'clear_transients' => array(
				'name'    => __( 'WC transients', 'woocommerce' ),
				'button'  => __( 'Clear transients', 'woocommerce' ),
				'desc'    => __( 'This tool will clear the product/shop transients cache.', 'woocommerce' ),
			),
			'clear_expired_transients' => array(
				'name'    => __( 'Expired transients', 'woocommerce' ),
				'button'  => __( 'Clear expired transients', 'woocommerce' ),
				'desc'    => __( 'This tool will clear ALL expired transients from WordPress.', 'woocommerce' ),
			),
			'delete_orphaned_variations' => array(
				'name'      => __( 'Orphaned variations', 'woocommerce' ),
				'button'    => __( 'Delete orphaned variations', 'woocommerce' ),
				'desc'      => __( 'This tool will delete all variations which have no parent.', 'woocommerce' ),
			),
			'recount_terms' => array(
				'name'    => __( 'Term counts', 'woocommerce' ),
				'button'  => __( 'Recount terms', 'woocommerce' ),
				'desc'    => __( 'This tool will recount product terms - useful when changing your settings in a way which hides products from the catalog.', 'woocommerce' ),
			),
			'reset_roles' => array(
				'name'    => __( 'Capabilities', 'woocommerce' ),
				'button'  => __( 'Reset capabilities', 'woocommerce' ),
				'desc'    => __( 'This tool will reset the admin, customer and shop_manager roles to default. Use this if your users cannot access all of the WooCommerce admin pages.', 'woocommerce' ),
			),
			'clear_sessions' => array(
				'name'    => __( 'Customer sessions', 'woocommerce' ),
				'button'  => __( 'Clear all sessions', 'woocommerce' ),
				'desc'    => sprintf(
					'<strong class="red">%1$s</strong> %2$s',
					__( 'Note:', 'woocommerce' ),
					__( 'This tool will delete all customer session data from the database, including any current live carts.', 'woocommerce' )
				),
			),
			'install_pages' => array(
				'name'    => __( 'Install WooCommerce pages', 'woocommerce' ),
				'button'  => __( 'Install pages', 'woocommerce' ),
				'desc'    => sprintf(
					'<strong class="red">%1$s</strong> %2$s',
					__( 'Note:', 'woocommerce' ),
					__( 'This tool will install all the missing WooCommerce pages. Pages already defined and set up will not be replaced.', 'woocommerce' )
				),
			),
			'delete_taxes' => array(
				'name'    => __( 'Delete all WooCommerce tax rates', 'woocommerce' ),
				'button'  => __( 'Delete ALL tax rates', 'woocommerce' ),
				'desc'    => sprintf(
					'<strong class="red">%1$s</strong> %2$s',
					__( 'Note:', 'woocommerce' ),
					__( 'This option will delete ALL of your tax rates, use with caution.', 'woocommerce' )
				),
			),
			'reset_tracking' => array(
				'name'    => __( 'Reset usage tracking settings', 'woocommerce' ),
				'button'  => __( 'Reset usage tracking settings', 'woocommerce' ),
				'desc'    => __( 'This will reset your usage tracking settings, causing it to show the opt-in banner again and not sending any data.', 'woocommerce' ),
			),
		);

		return apply_filters( 'woocommerce_debug_tools', $tools );
	}

	/**
	 * Get a list of system status tools.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_Error|WP_REST_Response
	 */
	public function get_items( $request ) {
		$tools = array();
		foreach ( $this->get_tools() as $id => $tool ) {
			$tools[] = $this->prepare_response_for_collection( $this->prepare_item_for_response( array(
				'id'          => $id,
				'name'        => $tool['name'],
				'action'      => $tool['button'],
				'description' => $tool['desc'],
			), $request ) );
		}

		$response = rest_ensure_response( $tools );
		return $response;
	}

	/**
	 * Return a single tool.
	 *
	 * @param  WP_REST_Request $request
	 * @return WP_Error|WP_REST_Response
	 */
	public function get_item( $request ) {
		$tools = $this->get_tools();
		if ( empty( $tools[ $request['id'] ] ) ) {
			return new WP_Error( 'woocommerce_rest_system_status_tool_invalid_id', __( 'Invalid tool ID.', 'woocommerce' ), array( 'status' => 404 ) );
		}
		$tool = $tools[ $request['id'] ];
		return rest_ensure_response( $this->prepare_item_for_response( array(
		   'id'          => $request['id'],
		   'name'        => $tool['name'],
		   'action'      => $tool['button'],
		   'description' => $tool['desc'],
	   ), $request ) );
	}

	/**
	 * Update (execute) a tool.
	 * @param  WP_REST_Request $request
	 * @return WP_Error|WP_REST_Response
	 */
	public function update_item( $request ) {
		$tools = $this->get_tools();
		if ( empty( $tools[ $request['id'] ] ) ) {
			return new WP_Error( 'woocommerce_rest_system_status_tool_invalid_id', __( 'Invalid tool ID.', 'woocommerce' ), array( 'status' => 404 ) );
		}

		$tool = $tools[ $request['id'] ];
		$tool = array(
		   'id'          => $request['id'],
		   'name'        => $tool['name'],
		   'action'      => $tool['button'],
		   'description' => $tool['desc'],
		);

		$execute_return = $this->execute_tool( $request['id'] );
		$tool = array_merge( $tool, $execute_return );

		$request->set_param( 'context', 'edit' );
		$response = $this->prepare_item_for_response( $tool, $request );
		return rest_ensure_response( $response );
	}

	/**
	 * Prepare a tool item for serialization.
	 *
	 * @param  array $item Object.
	 * @param  WP_REST_Request $request Request object.
	 * @return WP_REST_Response $response Response data.
	 */
	public function prepare_item_for_response( $item, $request ) {
		$context = empty( $request['context'] ) ? 'view' : $request['context'];
		$data    = $this->add_additional_fields_to_object( $item, $request );
		$data    = $this->filter_response_by_context( $data, $context );

		$response = rest_ensure_response( $data );

		$response->add_links( $this->prepare_links( $item['id'] ) );

		return $response;
	}

	/**
	 * Get the system status tools schema, conforming to JSON Schema.
	 *
	 * @return array
	 */
	public function get_item_schema() {
		$schema = array(
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => 'system_status_tool',
			'type'       => 'object',
			'properties' => array(
				'id'               => array(
					'description'  => __( 'A unique identifier for the tool.', 'woocommerce' ),
					'type'         => 'string',
					'context'      => array( 'view', 'edit' ),
					'arg_options'  => array(
						'sanitize_callback' => 'sanitize_title',
					),
				),
				'name'            => array(
					'description'  => __( 'Tool name.', 'woocommerce' ),
					'type'         => 'string',
					'context'      => array( 'view', 'edit' ),
					'arg_options'  => array(
						'sanitize_callback' => 'sanitize_text_field',
					),
				),
				'action'            => array(
					'description'  => __( 'What running the tool will do.', 'woocommerce' ),
					'type'         => 'string',
					'context'      => array( 'view', 'edit' ),
					'arg_options'  => array(
						'sanitize_callback' => 'sanitize_text_field',
					),
				),
				'description'      => array(
					'description'  => __( 'Tool description.', 'woocommerce' ),
					'type'         => 'string',
					'context'      => array( 'view', 'edit' ),
					'arg_options'  => array(
						'sanitize_callback' => 'sanitize_text_field',
					),
				),
				'success'      => array(
					'description'  => __( 'Did the tool run successfully?', 'woocommerce' ),
					'type'         => 'boolean',
					'context'      => array( 'edit' ),
				),
				'message'      => array(
					'description'  => __( 'Tool return message.', 'woocommerce' ),
					'type'         => 'string',
					'context'      => array( 'edit' ),
					'arg_options'  => array(
						'sanitize_callback' => 'sanitize_text_field',
					),
				),
			),
		);

		return $this->add_additional_fields_schema( $schema );
	}

	/**
	 * Prepare links for the request.
	 *
	 * @param string $id
	 * @return array
	 */
	protected function prepare_links( $id ) {
		$base  = '/' . $this->namespace . '/' . $this->rest_base;
		$links = array(
			'item' => array(
				'href'       => rest_url( trailingslashit( $base ) . $id ),
				'embeddable' => true,
			),
		);

		return $links;
	}

	/**
	 * Get any query params needed.
	 *
	 * @return array
	 */
	public function get_collection_params() {
		return array(
			'context' => $this->get_context_param( array( 'default' => 'view' ) ),
		);
	}

	/**
	 * Actually executes a a tool.
	 *
	 * @param  string $tool
	 * @return array
	 */
	public function execute_tool( $tool ) {
		global $wpdb;
		$ran = true;
		switch ( $tool ) {
			case 'clear_transients' :
				wc_delete_product_transients();
				wc_delete_shop_order_transients();
				WC_Cache_Helper::get_transient_version( 'shipping', true );
				$message = __( 'Product transients cleared', 'woocommerce' );
			break;
			case 'clear_expired_transients' :
				/*
				 * Deletes all expired transients. The multi-table delete syntax is used.
				 * to delete the transient record from table a, and the corresponding.
				 * transient_timeout record from table b.
				 *
				 * Based on code inside core's upgrade_network() function.
				 */
				$sql = "DELETE a, b FROM $wpdb->options a, $wpdb->options b
					WHERE a.option_name LIKE %s
					AND a.option_name NOT LIKE %s
					AND b.option_name = CONCAT( '_transient_timeout_', SUBSTRING( a.option_name, 12 ) )
					AND b.option_value < %d";
				$rows = $wpdb->query( $wpdb->prepare( $sql, $wpdb->esc_like( '_transient_' ) . '%', $wpdb->esc_like( '_transient_timeout_' ) . '%', time() ) );

				$sql = "DELETE a, b FROM $wpdb->options a, $wpdb->options b
					WHERE a.option_name LIKE %s
					AND a.option_name NOT LIKE %s
					AND b.option_name = CONCAT( '_site_transient_timeout_', SUBSTRING( a.option_name, 17 ) )
					AND b.option_value < %d";
				$rows2 = $wpdb->query( $wpdb->prepare( $sql, $wpdb->esc_like( '_site_transient_' ) . '%', $wpdb->esc_like( '_site_transient_timeout_' ) . '%', time() ) );

				$message = sprintf( __( '%d transients rows cleared', 'woocommerce' ), $rows + $rows2 );
			break;
			case 'delete_orphaned_variations' :
				/**
				 * Delete orphans
				 */
				$result = absint( $wpdb->query( "DELETE products
					FROM {$wpdb->posts} products
					LEFT JOIN {$wpdb->posts} wp ON wp.ID = products.post_parent
					WHERE wp.ID IS NULL AND products.post_type = 'product_variation';" ) );
				$message = sprintf( __( '%d orphaned variations deleted', 'woocommerce' ), $result );
			break;
			case 'reset_roles' :
				// Remove then re-add caps and roles
				WC_Install::remove_roles();
				WC_Install::create_roles();
				$message = __( 'Roles successfully reset', 'woocommerce' );
			break;
			case 'recount_terms' :
				$product_cats = get_terms( 'product_cat', array( 'hide_empty' => false, 'fields' => 'id=>parent' ) );
				_wc_term_recount( $product_cats, get_taxonomy( 'product_cat' ), true, false );
				$product_tags = get_terms( 'product_tag', array( 'hide_empty' => false, 'fields' => 'id=>parent' ) );
				_wc_term_recount( $product_tags, get_taxonomy( 'product_tag' ), true, false );
				$message = __( 'Terms successfully recounted', 'woocommerce' );
			break;
			case 'clear_sessions' :
				$wpdb->query( "TRUNCATE {$wpdb->prefix}woocommerce_sessions" );
				wp_cache_flush();
				$message = __( 'Sessions successfully cleared', 'woocommerce' );
			break;
			case 'install_pages' :
				WC_Install::create_pages();
				$message = __( 'All missing WooCommerce pages successfully installed', 'woocommerce' );
			break;
			case 'delete_taxes' :

				$wpdb->query( "TRUNCATE TABLE {$wpdb->prefix}woocommerce_tax_rates;" );
				$wpdb->query( "TRUNCATE TABLE {$wpdb->prefix}woocommerce_tax_rate_locations;" );
				WC_Cache_Helper::incr_cache_prefix( 'taxes' );
				$message = __( 'Tax rates successfully deleted', 'woocommerce' );
			break;
			case 'reset_tracking' :
				delete_option( 'woocommerce_allow_tracking' );
				WC_Admin_Notices::add_notice( 'tracking' );
				$message = __( 'Usage tracking settings successfully reset.', 'woocommerce' );
			break;
			default :
				$tools = $this->get_tools();
				if ( isset( $tools[ $tool ]['callback'] ) ) {
					$callback = $tools[ $tool ]['callback'];
					$return = call_user_func( $callback );
					if ( is_string( $return ) ) {
						$message = $return;
					} elseif ( false === $return ) {
						$callback_string = is_array( $callback ) ? get_class( $callback[0] ) . '::' . $callback[1] : $callback;
						$ran = false;
						$message = sprintf( __( 'There was an error calling %s', 'woocommerce' ), $callback_string );
					} else {
						$message = __( 'Tool ran.', 'woocommerce' );
					}
				} else {
					$ran     = false;
					$message = __( 'There was an error calling this tool. There is no callback present.', 'woocommerce' );
				}
			break;
		}

		return array( 'success' => $ran, 'message' => $message );
	}
}
