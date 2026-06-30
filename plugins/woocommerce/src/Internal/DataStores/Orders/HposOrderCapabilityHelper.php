<?php
/**
 * HposOrderCapabilityHelper class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\DataStores\Orders;

use Automattic\WooCommerce\Utilities\OrderUtil;
use WC_Abstract_Order;
use WP_Post;
use WP_Post_Type;

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

		$order_id = absint( $args[0] );
		if ( ! $order_id ) {
			return $caps;
		}

		$post = get_post( $order_id );
		if ( $post instanceof WP_Post && DataSynchronizer::PLACEHOLDER_ORDER_POST_TYPE !== $post->post_type ) {
			return $caps;
		}

		$order_type    = OrderUtil::get_order_type( $order_id );
		$order_type_ob = $order_type ? get_post_type_object( $order_type ) : null;
		if ( ! ( $order_type_ob instanceof WP_Post_Type ) || ! $order_type_ob->map_meta_cap ) {
			return $caps;
		}

		$order = wc_get_order( $order_id );
		if ( ! $order instanceof WC_Abstract_Order ) {
			return $caps;
		}

		switch ( $cap ) {
			case 'edit_post':
				return $this->map_edit_order_caps( $order, $order_type_ob, (int) $user_id, $post );
			case 'delete_post':
				return $this->map_delete_order_caps( $order, $order_type_ob, (int) $user_id, $post );
			case 'read_post':
				return $this->map_read_order_caps( $order, $order_type_ob, (int) $user_id, $post );
		}
	}

	/**
	 * Map edit capabilities for an HPOS order without a real order post.
	 *
	 * @param WC_Abstract_Order $order         Order object.
	 * @param WP_Post_Type      $order_type_ob Order post type object.
	 * @param int               $user_id       User ID.
	 * @param WP_Post|null      $post          Placeholder post, if one exists.
	 * @return string[] Required primitive capabilities.
	 */
	private function map_edit_order_caps( WC_Abstract_Order $order, WP_Post_Type $order_type_ob, int $user_id, ?WP_Post $post ): array {
		$status  = $this->get_wp_status_for_order( $order );
		$is_mine = $user_id === $this->get_author_id( $post );

		if ( $is_mine ) {
			if ( $this->is_published_status( $status ) ) {
				return array( $order_type_ob->cap->edit_published_posts );
			}
			if ( 'trash' === $status && $this->is_published_status( $this->get_trashed_status( $order ) ) ) {
				return array( $order_type_ob->cap->edit_published_posts );
			}
			return array( $order_type_ob->cap->edit_posts );
		}

		$caps = array( $order_type_ob->cap->edit_others_posts );
		if ( $this->is_published_status( $status ) ) {
			$caps[] = $order_type_ob->cap->edit_published_posts;
		} elseif ( $this->is_private_status( $status ) ) {
			$caps[] = $order_type_ob->cap->edit_private_posts;
		}

		return $caps;
	}

	/**
	 * Map delete capabilities for an HPOS order without a real order post.
	 *
	 * @param WC_Abstract_Order $order         Order object.
	 * @param WP_Post_Type      $order_type_ob Order post type object.
	 * @param int               $user_id       User ID.
	 * @param WP_Post|null      $post          Placeholder post, if one exists.
	 * @return string[] Required primitive capabilities.
	 */
	private function map_delete_order_caps( WC_Abstract_Order $order, WP_Post_Type $order_type_ob, int $user_id, ?WP_Post $post ): array {
		$status  = $this->get_wp_status_for_order( $order );
		$is_mine = $user_id === $this->get_author_id( $post );

		if ( $is_mine ) {
			if ( $this->is_published_status( $status ) ) {
				return array( $order_type_ob->cap->delete_published_posts );
			}
			if ( 'trash' === $status && $this->is_published_status( $this->get_trashed_status( $order ) ) ) {
				return array( $order_type_ob->cap->delete_published_posts );
			}
			return array( $order_type_ob->cap->delete_posts );
		}

		$caps = array( $order_type_ob->cap->delete_others_posts );
		if ( $this->is_published_status( $status ) ) {
			$caps[] = $order_type_ob->cap->delete_published_posts;
		} elseif ( $this->is_private_status( $status ) ) {
			$caps[] = $order_type_ob->cap->delete_private_posts;
		}

		return $caps;
	}

	/**
	 * Map read capabilities for an HPOS order without a real order post.
	 *
	 * @param WC_Abstract_Order $order         Order object.
	 * @param WP_Post_Type      $order_type_ob Order post type object.
	 * @param int               $user_id       User ID.
	 * @param WP_Post|null      $post          Placeholder post, if one exists.
	 * @return string[] Required primitive capabilities.
	 */
	private function map_read_order_caps( WC_Abstract_Order $order, WP_Post_Type $order_type_ob, int $user_id, ?WP_Post $post ): array {
		$status_obj = get_post_status_object( $this->get_wp_status_for_order( $order ) );
		if ( ! $status_obj ) {
			return array( $order_type_ob->cap->edit_others_posts );
		}

		if ( $status_obj->public || $user_id === $this->get_author_id( $post ) ) {
			return array( $order_type_ob->cap->read );
		}

		if ( $status_obj->private ) {
			return array( $order_type_ob->cap->read_private_posts );
		}

		return $this->map_edit_order_caps( $order, $order_type_ob, $user_id, $post );
	}

	/**
	 * Get the WordPress post status equivalent for an order.
	 *
	 * @param WC_Abstract_Order $order Order object.
	 * @return string Post status.
	 */
	private function get_wp_status_for_order( WC_Abstract_Order $order ): string {
		$status = $order->get_status( 'edit' );
		return wc_is_order_status( 'wc-' . $status ) ? 'wc-' . $status : $status;
	}

	/**
	 * Check whether a post status should require published post caps.
	 *
	 * @param string $status Post status.
	 * @return bool True when published caps should be required.
	 */
	private function is_published_status( string $status ): bool {
		$status_obj = get_post_status_object( $status );
		return in_array( $status, array( 'publish', 'future' ), true ) || ( $status_obj && $status_obj->public );
	}

	/**
	 * Check whether a post status should require private post caps.
	 *
	 * @param string $status Post status.
	 * @return bool True when private caps should be required.
	 */
	private function is_private_status( string $status ): bool {
		$status_obj = get_post_status_object( $status );
		return 'private' === $status || ( $status_obj && $status_obj->private );
	}

	/**
	 * Get the previous status stored when an order is trashed.
	 *
	 * @param WC_Abstract_Order $order Order object.
	 * @return string Previous post status.
	 */
	private function get_trashed_status( WC_Abstract_Order $order ): string {
		$status = $order->get_meta( '_wp_trash_meta_status', true, 'edit' );
		return is_string( $status ) ? $status : '';
	}

	/**
	 * Get the author ID to use for WordPress-style capability checks.
	 *
	 * @param WP_Post|null $post Placeholder post, if one exists.
	 * @return int Author ID.
	 */
	private function get_author_id( ?WP_Post $post ): int {
		if ( $post instanceof WP_Post && 0 < (int) $post->post_author ) {
			return (int) $post->post_author;
		}

		return 1;
	}
}
