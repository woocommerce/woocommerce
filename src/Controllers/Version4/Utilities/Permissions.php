<?php
/**
 * Permissions.
 *
 * Handles permission checks for endpoints.
 *
 * @package WooCommerce/RestApi
 */

namespace WooCommerce\RestApi\Controllers\Version4\Utilities;

defined( 'ABSPATH' ) || exit;

/**
 * Permissions class.
 */
class Permissions {

	/**
	 * Resource permissions required.
	 *
	 * @var array
	 */
	protected static $resource_permissions = [
		'settings'         => 'manage_woocommerce',
		'system_status'    => 'manage_woocommerce',
		'attributes'       => 'manage_product_terms',
		'shipping_methods' => 'manage_woocommerce',
		'payment_gateways' => 'manage_woocommerce',
		'webhooks'         => 'manage_woocommerce',
		'product_reviews'  => 'moderate_comments',
		'customers'        => [
			'read'   => 'list_users',
			'create' => 'promote_users', // Check if current user can create users, shop managers are not allowed to create users.
			'edit'   => 'edit_users',
			'delete' => 'delete_users',
			'batch'  => 'promote_users',
		],
	];

	/**
	 * See if the current user can do something to a resource.
	 *
	 * @param string $resource Type of permission required.
	 * @param string $context Context. One of read, edit, create, update, delete, batch.
	 * @param int    $resource_id Optional resource ID.
	 * @return boolean
	 */
	public static function check_resource( $resource, $context = 'read', $resource_id = 0 ) {
		if ( ! isset( self::$resource_permissions[ $resource ] ) ) {
			return false;
		}
		$permissions = self::$resource_permissions[ $resource ];
		$capability  = is_array( $permissions ) ? $permissions[ $context ] : $permissions;
		$permission  = current_user_can( $capability );

		return apply_filters( 'woocommerce_rest_check_permissions', $permission, $context, $resource_id, $resource );
	}

	/**
	 * See if the current user can do something to a resource.
	 *
	 * @param string $taxonomy Taxonomy name.
	 * @param string $context Context. One of read, edit, create, update, delete, batch.
	 * @param int    $object_id Optional object ID.
	 * @return boolean
	 */
	public static function check_taxonomy( $taxonomy, $context = 'read', $object_id = 0 ) {
		$contexts        = array(
			'read'   => 'manage_terms',
			'create' => 'edit_terms',
			'edit'   => 'edit_terms',
			'delete' => 'delete_terms',
			'batch'  => 'edit_terms',
		);
		$cap             = $contexts[ $context ];
		$taxonomy_object = get_taxonomy( $taxonomy );
		$permission      = current_user_can( $taxonomy_object->cap->$cap, $object_id );

		return apply_filters( 'woocommerce_rest_check_permissions', $permission, $context, $object_id, $taxonomy );
	}

	/**
	 * Check permissions of posts on REST API.
	 *
	 * @param string $post_type Post type.
	 * @param string $context   Request context.
	 * @param int    $object_id Post ID.
	 * @return bool
	 */
	public static function check_post_object( $post_type, $context = 'read', $object_id = 0 ) {
		$contexts = array(
			'read'   => 'read_private_posts',
			'create' => 'publish_posts',
			'edit'   => 'edit_post',
			'delete' => 'delete_post',
			'batch'  => 'edit_others_posts',
		);

		if ( 'revision' === $post_type ) {
			$permission = false;
		} else {
			$cap              = $contexts[ $context ];
			$post_type_object = get_post_type_object( $post_type );
			$permission       = current_user_can( $post_type_object->cap->$cap, $object_id );
		}

		return apply_filters( 'woocommerce_rest_check_permissions', $permission, $context, $object_id, $post_type );
	}
}
