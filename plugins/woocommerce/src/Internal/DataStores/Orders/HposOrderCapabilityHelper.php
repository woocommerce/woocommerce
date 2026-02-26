<?php
/**
 * HposOrderCapabilityHelper class file.
 */

namespace Automattic\WooCommerce\Internal\DataStores\Orders;

use Automattic\WooCommerce\Utilities\OrderUtil;

defined( 'ABSPATH' ) || exit;

/**
 * Translates capabilities for HPOS orders when sync is not active.
 *
 * When HPOS is the authoritative source and sync is off, order rows in
 * wp_posts are either placeholders (shop_order_placehold) or may not
 * exist at all. WordPress's map_meta_cap resolves these to generic 'post'
 * capabilities (or 'do_not_allow' when the post is missing), which breaks
 * permission checks for roles like Shop Manager that have order-specific
 * caps but not generic post caps.
 *
 * This class is instantiated lazily by CustomOrdersTableController when
 * a capability check occurs with HPOS enabled and sync disabled.
 *
 * @since 10.7.0
 */
class HposOrderCapabilityHelper {

	/**
	 * Translate capabilities for HPOS orders.
	 *
	 * Handles the full map_meta_cap filter callback. The caller only needs
	 * to verify that HPOS is enabled and sync is disabled before delegating.
	 *
	 * @since 10.7.0
	 *
	 * @param string[] $caps    The resolved primitive capabilities.
	 * @param string   $cap     The meta capability being checked.
	 * @param int      $user_id The user ID.
	 * @param array    $args    Additional arguments (object ID).
	 * @return string[] Translated capabilities.
	 */
	public function translate_order_caps( $caps, $cap, $user_id, $args ) {
		if ( ! in_array( $cap, array( 'edit_post', 'delete_post', 'read_post' ), true ) || ! isset( $args[0] ) ) {
			return $caps;
		}

		// If it's a real (non-placeholder) post, let WordPress handle it normally.
		$post_type = get_post_type( $args[0] );
		if ( $post_type && DataSynchronizer::PLACEHOLDER_ORDER_POST_TYPE !== $post_type ) {
			return $caps;
		}

		// Check if the ID corresponds to an HPOS order whose post type uses map_meta_cap.
		$order_type    = OrderUtil::get_order_type( $args[0] );
		$order_type_ob = $order_type ? get_post_type_object( $order_type ) : null;
		if ( ! $order_type_ob || ! $order_type_ob->map_meta_cap ) {
			return $caps;
		}

		// Build a mapping from generic 'post' caps to the order type's caps.
		$default_post_type = get_post_type_object( 'post' );
		if ( ! $default_post_type ) {
			return $caps;
		}
		$default_caps   = $default_post_type->cap;
		$order_type_cap = $order_type_ob->cap;
		$cap_map        = array();
		foreach ( (array) $default_caps as $key => $generic_cap ) {
			if ( isset( $order_type_cap->$key ) ) {
				$cap_map[ $generic_cap ] = $order_type_cap->$key;
			}
		}

		$new_caps = array_map( fn( $c ) => $cap_map[ $c ] ?? $c, $caps );

		// If WordPress returned 'do_not_allow' (no post found), replace with
		// the appropriate "others" cap since we confirmed the order exists in HPOS.
		if ( in_array( 'do_not_allow', $new_caps, true ) ) {
			$others_cap = 'delete_post' === $cap
				? $order_type_ob->cap->delete_others_posts
				: $order_type_ob->cap->edit_others_posts;
			$new_caps   = array( $others_cap );
		}

		return $new_caps;
	}
}
