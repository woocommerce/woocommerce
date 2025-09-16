<?php
/**
 * Abstract REST Controller.
 *
 * Extends WP_REST_Controller. Implements functionality that applies to all route controllers.
 *
 * @package WooCommerce\RestApi
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\RestApi\Routes\V4;

use WP_Error;
use WP_Http;
use WP_REST_Controller;

defined( 'ABSPATH' ) || exit;

/**
 * Abstract REST Controller for WooCommerce REST API V4.
 *
 * Provides common functionality for all V4 route controllers including
 * schema generation, error handling, and hook management.
 *
 * @since 10.2.0
 */
abstract class AbstractController extends WP_REST_Controller {
	/**
	 * Route namespace.
	 *
	 * @var string
	 */
	protected $namespace = 'wc/v4';

	/**
	 * Route base.
	 *
	 * @var string
	 */
	protected $rest_base = '';

	/**
	 * Cache for the item schema populated after calling get_item_schema().
	 *
	 * @var array
	 */
	protected $schema;

	/**
	 * Get item schema, conforming to JSON Schema. Extended by routes.
	 *
	 * @return array The item schema.
	 * @since 10.2.0
	 */
	public function get_item_schema() {
		if ( null === $this->schema ) {
			$this->schema = array(
				'$schema'    => 'http://json-schema.org/draft-04/schema#',
				'type'       => 'object',
				'title'      => 'item',
				'properties' => array(),
			);
			/**
			 * Filter the item schema for this route.
			 *
			 * @param array $schema The item schema.
			 * @since 10.2.0
			 */
			$this->schema = apply_filters( $this->get_hook_prefix() . 'item_schema', $this->add_additional_fields_schema( $this->schema ) );
		}
		return $this->schema;
	}

	/**
	 * Get the hook prefix for actions and filters.
	 *
	 * Example: woocommerce_rest_api_v4_orders_
	 *
	 * @return string The hook prefix.
	 * @since 10.2.0
	 */
	protected function get_hook_prefix(): string {
		return 'woocommerce_rest_api_v4_' . $this->rest_base . '_';
	}

	/**
	 * Get the error prefix for errors.
	 *
	 * Example: woocommerce_rest_api_v4_orders_
	 *
	 * @return string The error prefix.
	 * @since 10.2.0
	 */
	protected function get_error_prefix(): string {
		return 'woocommerce_rest_api_v4_' . $this->rest_base . '_';
	}

	/**
	 * Filter schema properties to only return writable ones.
	 *
	 * @param array $schema The schema property to check.
	 * @return bool True if the property is writable, false otherwise.
	 * @since 10.2.0
	 */
	protected function filter_writable_props( array $schema ): bool {
		return empty( $schema['readonly'] );
	}

	/**
	 * Get route response when something went wrong.
	 *
	 * @param string $error_code String based error code.
	 * @param string $error_message User facing error message.
	 * @param int    $http_status_code HTTP status. Defaults to 400.
	 * @param array  $additional_data Extra data (key value pairs) to expose in the error response.
	 * @return WP_Error WP Error object.
	 * @since 10.2.0
	 */
	protected function get_route_error_response( string $error_code, string $error_message, int $http_status_code = WP_Http::BAD_REQUEST, array $additional_data = array() ): WP_Error {
		if ( empty( $error_code ) ) {
			$error_code = 'invalid_request';
		}

		if ( empty( $error_message ) ) {
			$error_message = __( 'An error occurred while processing your request.', 'woocommerce' );
		}

		return new WP_Error(
			$error_code,
			$error_message,
			array_merge(
				$additional_data,
				array( 'status' => $http_status_code )
			)
		);
	}

	/**
	 * Get route response when something went wrong and the supplied error is a WP_Error.
	 *
	 * @param WP_Error $error_object The WP_Error object containing the error.
	 * @param int      $http_status_code HTTP status. Defaults to 400.
	 * @param array    $additional_data Extra data (key value pairs) to expose in the error response.
	 * @return WP_Error WP Error object.
	 * @since 10.2.0
	 */
	protected function get_route_error_response_from_object( WP_Error $error_object, int $http_status_code = WP_Http::BAD_REQUEST, array $additional_data = array() ): WP_Error {
		if ( ! $error_object instanceof WP_Error ) {
			return $this->get_route_error_response( 'invalid_error_object', __( 'Invalid error object provided.', 'woocommerce' ), $http_status_code, $additional_data );
		}

		$error_object->add_data( array_merge( $additional_data, array( 'status' => $http_status_code ) ) );
		return $error_object;
	}

	/**
	 * Check permissions for REST API V4 requests.
	 *
	 * This function provides enhanced permission checking for V4 API endpoints,
	 * supporting both post types and other object types with proper object-level
	 * permission validation.
	 *
	 * @param string $object_type The type of object (e.g., 'order', 'product', 'customer').
	 * @param string $context The operation context ('read', 'create', 'edit', 'delete', 'batch').
	 * @param int    $object_id The object ID. Defaults to 0 for general permissions.
	 * @return bool True if permission is granted, false otherwise.
	 * @since 10.2.0
	 */
	protected function check_permissions( string $object_type, string $context = 'read', int $object_id = 0 ): bool {
		// Map object types to post types for permission checking.
		$post_type_mapping = array(
			'order'             => 'shop_order',
			'product'           => 'product',
			'product_variation' => 'product_variation',
			'coupon'            => 'shop_coupon',
			'order_note'        => 'shop_order', // Order notes belong to orders.
		);

		$post_type  = $post_type_mapping[ $object_type ] ?? null;
		$permission = false;

		// Handle unsupported object types.
		if ( null === $post_type ) {
			$permission = false;
		} else {
			$permission = $this->check_post_type_permissions( $post_type, $context, $object_id );
		}

		/**
		 * Provides an opportunity to override the permission check made before acting on an object in relation to
		 * REST API V4 requests.
		 *
		 * @since 10.2.0
		 *
		 * @param bool   $permission  If we have permission to act on this object.
		 * @param string $context     Describes the operation being performed: 'read', 'edit', 'delete', etc.
		 * @param int    $object_id   Object ID. This could be a user ID, order ID, post ID, etc.
		 * @param string $object_type Type of object ('order', 'product', 'customer', etc) for which checks are being made.
		 */
		return apply_filters( $this->get_hook_prefix() . 'check_permissions', $permission, $context, $object_id, $object_type );
	}

	/**
	 * Check permissions for post type objects.
	 *
	 * @param string $post_type The post type.
	 * @param string $context The operation context.
	 * @param int    $object_id The object ID.
	 * @return bool True if permission is granted, false otherwise.
	 * @since 10.2.0
	 */
	private function check_post_type_permissions( string $post_type, string $context, int $object_id ): bool {
		$contexts = array(
			'read'   => 'read_private_posts',
			'create' => 'publish_posts',
			'edit'   => 'edit_posts',
			'delete' => 'delete_posts',
			'batch'  => 'edit_others_posts',
		);

		$capability = $contexts[ $context ] ?? null;
		$permission = false;

		if ( $capability ) {
			$post_type_object = get_post_type_object( $post_type );

			if ( $post_type_object instanceof \WP_Post_Type ) {
				$permission = current_user_can( $post_type_object->cap->$capability );

				// Special handling when object ID is provided for object-level permissions.
				if ( $object_id && 'edit_posts' === $capability ) {
					$permission = $permission && current_user_can( $post_type_object->cap->edit_post, $object_id );
				} elseif ( $object_id && 'delete_posts' === $capability ) {
					$permission = $permission && current_user_can( $post_type_object->cap->delete_post, $object_id );
				} elseif ( $object_id && 'read_private_posts' === $capability ) {
					$permission = $permission && current_user_can( $post_type_object->cap->read_post, $object_id );
				}
			}
		}

		return $permission;
	}
}
