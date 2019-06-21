<?php
/**
 * Permissions.
 *
 * Handles permission checks for endpoints.
 *
 * @package Automattic/WooCommerce/RestApi
 */

namespace Automattic\WooCommerce\RestApi\Controllers\Version4\Utilities;

defined( 'ABSPATH' ) || exit;

/**
 * Permissions class.
 */
class Permissions {

	/**
	 * Items not defined here will default to manage_woocommerce permission.
	 *
	 * @var array
	 */
	protected static $capabilities = array(
		'shop_coupon'            => [
			'read'   => 'read_shop_coupon',
			'list'   => 'read_private_shop_coupons',
			'create' => 'publish_shop_coupons',
			'edit'   => 'edit_shop_coupon',
			'delete' => 'delete_shop_coupon',
			'batch'  => 'edit_others_shop_coupons',
		],
		'customer_download'      => [
			'read'   => 'read_shop_order',
			'list'   => 'read_private_shop_orders',
			'create' => 'publish_shop_orders',
			'edit'   => 'edit_shop_order',
			'delete' => 'delete_shop_order',
			'batch'  => 'edit_others_shop_orders',
		],
		'customer'               => [
			'read'   => 'list_users',
			'list'   => 'list_users',
			'create' => 'promote_users',
			'edit'   => 'edit_users',
			'delete' => 'delete_users',
			'batch'  => 'promote_users',
		],
		'shop_order'             => [
			'read'   => 'read_shop_order',
			'list'   => 'read_private_shop_orders',
			'create' => 'publish_shop_orders',
			'edit'   => 'edit_shop_order',
			'delete' => 'delete_shop_order',
			'batch'  => 'edit_others_shop_orders',
		],
		'product_attribute'      => 'edit_product_terms',
		'product_attribute_term' => [
			'read'   => 'manage_product_terms',
			'list'   => 'manage_product_terms',
			'create' => 'edit_product_terms',
			'edit'   => 'edit_product_terms',
			'delete' => 'delete_product_terms',
			'batch'  => 'edit_product_terms',
		],
		'product_cat'            => [
			'read'   => 'manage_product_terms',
			'list'   => 'manage_product_terms',
			'create' => 'edit_product_terms',
			'edit'   => 'edit_product_terms',
			'delete' => 'delete_product_terms',
			'batch'  => 'edit_product_terms',
		],
		'product_review'         => 'moderate_comments',
		'product'                => [
			'read'   => 'read_product',
			'list'   => 'read_private_products',
			'create' => 'publish_products',
			'edit'   => 'edit_product',
			'delete' => 'delete_product',
			'batch'  => 'edit_others_products',
		],
		'product_shipping_class' => [
			'read'   => 'manage_product_terms',
			'list'   => 'manage_product_terms',
			'create' => 'edit_product_terms',
			'edit'   => 'edit_product_terms',
			'delete' => 'delete_product_terms',
			'batch'  => 'edit_product_terms',
		],
		'product_tag'            => [
			'read'   => 'manage_product_terms',
			'list'   => 'manage_product_terms',
			'create' => 'edit_product_terms',
			'edit'   => 'edit_product_terms',
			'delete' => 'delete_product_terms',
			'batch'  => 'edit_product_terms',
		],
		'product_variation'      => [
			'read'   => 'read_product',
			'list'   => 'read_private_products',
			'create' => 'publish_products',
			'edit'   => 'edit_product',
			'delete' => 'delete_product',
			'batch'  => 'edit_others_products',
		],
	);

	/**
	 * Get capabilities required for a resource for a given context.
	 *
	 * @param string $type Item/resource type. Comes from schema title.
	 * @param string $context Read, edit, delete, batch, create.
	 * @return array List of caps to check. Defaults to manage_woocommerce.
	 */
	protected static function get_capabilities_for_type( $type, $context = 'read' ) {
		if ( isset( self::$capabilities[ $type ][ $context ] ) ) {
			$caps = self::$capabilities[ $type ][ $context ];
		} elseif ( isset( self::$capabilities[ $type ] ) ) {
			$caps = self::$capabilities[ $type ];
		} else {
			$caps = 'manage_woocommerce';
		}
		return is_array( $caps ) ? $caps : array( $caps );
	}

	/**
	 * Check if user has a list of caps.
	 *
	 * @param array $capabilities List of caps to check.
	 * @param int   $object_id Object ID to check. Optional.
	 * @return boolean
	 */
	protected static function has_required_capabilities( $capabilities, $object_id = null ) {
		$permission = true;

		foreach ( $capabilities as $capability ) {
			if ( ! current_user_can( $capability, $object_id ) ) {
				$permission = false;
			}
		}

		return $permission;
	}

	/**
	 * Check if user can list a collection of resources.
	 *
	 * @param string $type Item/resource type. Comes from schema title.
	 * @return bool True on success.
	 */
	public static function user_can_list( $type ) {
		$capabilities = self::get_capabilities_for_type( $type, 'list' );
		$permission   = self::has_required_capabilities( $capabilities );

		return apply_filters( 'woocommerce_rest_user_can_list', $permission, $type );
	}

	/**
	 * Check if user can read a resource.
	 *
	 * @param string $type Item/resource type. Comes from schema title.
	 * @param int    $object_id Resource ID. 0 to check access to read all.
	 * @return bool True on success.
	 */
	public static function user_can_read( $type, $object_id = 0 ) {
		if ( 0 === $object_id ) {
			return false;
		}

		$capabilities = self::get_capabilities_for_type( $type, 'read' );
		$permission   = self::has_required_capabilities( $capabilities, $object_id );

		return apply_filters( 'woocommerce_rest_user_can_read', $permission, $type, $object_id );
	}

	/**
	 * Check if user can read a resource.
	 *
	 * @param string $type Item/resource type. Comes from schema title.
	 * @param int    $object_id Resource ID.
	 * @return bool True on success.
	 */
	public static function user_can_edit( $type, $object_id ) {
		if ( 0 === $object_id ) {
			return false;
		}

		$capabilities = self::get_capabilities_for_type( $type, 'edit' );
		$permission   = self::has_required_capabilities( $capabilities, $object_id );

		return apply_filters( 'woocommerce_rest_user_can_edit', $permission, $type, $object_id );
	}

	/**
	 * Check if user can create a resource.
	 *
	 * @param string $type Item/resource type. Comes from schema title.
	 * @return bool True on success.
	 */
	public static function user_can_create( $type ) {
		$capabilities = self::get_capabilities_for_type( $type, 'create' );
		$permission   = self::has_required_capabilities( $capabilities );

		return apply_filters( 'woocommerce_rest_user_can_create', $permission, $type );
	}

	/**
	 * Check if user can delete a resource.
	 *
	 * @param string $type Item/resource type. Comes from schema title.
	 * @param int    $object_id Resource ID.
	 * @return bool True on success.
	 */
	public static function user_can_delete( $type, $object_id ) {
		if ( 0 === $object_id ) {
			return false;
		}

		$capabilities = self::get_capabilities_for_type( $type, 'delete' );
		$permission   = self::has_required_capabilities( $capabilities, $object_id );

		return apply_filters( 'woocommerce_rest_user_can_delete', $permission, $type, $object_id );
	}

	/**
	 * Check if user can batch update a resource.
	 *
	 * @param string $type Item/resource type. Comes from schema title.
	 * @return bool True on success.
	 */
	public static function user_can_batch( $type ) {
		$capabilities = self::get_capabilities_for_type( $type, 'batch' );
		$permission   = self::has_required_capabilities( $capabilities );

		return apply_filters( 'woocommerce_rest_user_can_batch', $permission, $type );
	}
}
