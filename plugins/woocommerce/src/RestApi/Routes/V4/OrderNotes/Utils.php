<?php
/**
 * Helpers for order notes.
 *
 * @package WooCommerce\RestApi
 * @internal This file is for internal use only and should not be used by external code.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\RestApi\Routes\V4\OrderNotes;

defined( 'ABSPATH' ) || exit;

use WP_Comment;
use WC_Order;

/**
 * Utils class.
 *
 * @internal This class is for internal use only and should not be used by external code.
 */
final class Utils {
	/**
	 * Get an order by ID.
	 *
	 * @param int $order_id The order ID.
	 * @return WC_Order|null
	 */
	public static function get_order_by_id( int $order_id ) {
		if ( ! $order_id ) {
			return null;
		}
		$order = wc_get_order( $order_id );
		return $order && 'shop_order' === $order->get_type() ? $order : null;
	}
	/**
	 * Get the parent order of a note.
	 *
	 * @param int|WP_Comment $note The note ID or note object.
	 * @return WC_Order|null
	 */
	public static function get_order_by_note_id( $note ) {
		$note = $note instanceof WP_Comment ? $note : self::get_note_by_id( (int) $note );
		if ( ! $note ) {
			return null;
		}
		return self::get_order_by_id( (int) $note->comment_post_ID );
	}

	/**
	 * Get a note by ID.
	 *
	 * @param int $note_id The note ID.
	 * @return WP_Comment|null
	 */
	public static function get_note_by_id( int $note_id ) {
		if ( ! $note_id ) {
			return null;
		}
		$note = get_comment( $note_id );
		return $note && 'order_note' === $note->comment_type ? $note : null;
	}
}
