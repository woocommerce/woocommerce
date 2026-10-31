<?php
/**
 * Order Data
 *
 * Functions for displaying the order items meta box.
 *
 * @author      WooThemes
 * @category    Admin
 * @package     WooCommerce\Admin\Meta Boxes
 * @version     2.1.0
 */

use Automattic\WooCommerce\Utilities\OrderUtil;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * WC_Meta_Box_Order_Items Class.
 */
class WC_Meta_Box_Order_Items {

	/**
	 * Pad editable prices to the currency decimals without rounding away stored precision.
	 *
	 * @internal
	 * @since 11.3.0
	 * @param string $value Decimal amount from an order item.
	 * @return string
	 */
	public static function format_price_for_editing( string $value ): string {
		$decimals = wc_get_price_decimals();

		if ( preg_match( '/^([+-]?)(\d+\.?\d*|\.\d+)[eE]([+-]?\d+)$/D', $value, $matches ) ) {
			$parts    = explode( '.', $matches[2], 2 );
			$digits   = implode( '', $parts );
			$position = strlen( $parts[0] ) + (int) $matches[3];

			if ( $position <= 0 ) {
				$value = '0.' . str_repeat( '0', -$position ) . $digits;
			} elseif ( $position >= strlen( $digits ) ) {
				$value = str_pad( $digits, $position, '0' );
			} else {
				$value = substr( $digits, 0, $position ) . '.' . substr( $digits, $position );
			}
			$value = $matches[1] . $value;
		}

		if ( '' !== $value && $decimals > 0 ) {
			$parts = explode( '.', $value, 2 );
			$value = $parts[0] . '.' . str_pad( $parts[1] ?? '', $decimals, '0' );
		}

		return wc_format_localized_price( $value );
	}

	/**
	 * Output the metabox.
	 *
	 * @param WP_Post|WC_Order $post Post or order object.
	 */
	public static function output( $post ) {
		global $post, $thepostid, $theorder;

		OrderUtil::init_theorder_object( $post );
		if ( ! is_int( $thepostid ) && ( $post instanceof WP_Post ) ) {
			$thepostid = $post->ID;
		}

		$order = $theorder;
		$data  = ( $post instanceof WP_Post ) ? get_post_meta( $post->ID ) : array();

		include __DIR__ . '/views/html-order-items.php';
	}

	/**
	 * Save meta box data.
	 *
	 * @param int $post_id
	 */
	public static function save( $post_id ) {
		/**
		 * This $_POST variable's data has been validated and escaped
		 * inside `wc_save_order_items()` function.
		 */
		wc_save_order_items( $post_id, $_POST );
	}
}
