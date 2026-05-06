<?php
/**
 * Order Submit
 *
 * Renders the redesigned Update/Create + Trash submit block for an order in
 * the side column, gated behind the `order-detail-redesign` feature flag.
 *
 * @package WooCommerce\Admin\Meta Boxes
 */

declare( strict_types = 1 );

use Automattic\WooCommerce\Admin\Features\OrderDetailRedesign\Init as OrderDetailRedesignInit;
use Automattic\WooCommerce\Enums\OrderStatus;
use Automattic\WooCommerce\Internal\Admin\Orders\PageController;
use Automattic\WooCommerce\Utilities\OrderUtil;

defined( 'ABSPATH' ) || exit;

/**
 * WC_Meta_Box_Order_Submit Class.
 *
 * @internal
 */
class WC_Meta_Box_Order_Submit {

	/**
	 * Output the metabox.
	 *
	 * @param WP_Post|WC_Order $post Post or order object.
	 */
	public static function output( $post ): void {
		// Render nothing when the redesign is not active. The empty meta box
		// is hidden entirely by CSS rules scoped to the redesign body class,
		// so it leaves no visible artifact when the flag is off.
		if ( ! OrderDetailRedesignInit::is_enabled() ) {
			return;
		}

		global $theorder;

		OrderUtil::init_theorder_object( $post );
		$order = $theorder;

		$order_id     = $order->get_id();
		$is_new_order = OrderStatus::AUTO_DRAFT === $order->get_status();
		$submit_label = $is_new_order
			? __( 'Create order', 'woocommerce' )
			: __( 'Update order', 'woocommerce' );

		?>
		<div class="wc-order-submit">
			<button
				type="submit"
				class="button save_order button-primary"
				name="save"
				value="<?php echo esc_attr( $submit_label ); ?>"
			><?php echo esc_html( $submit_label ); ?></button>

			<?php if ( current_user_can( 'delete_post', $order_id ) ) : ?>
				<?php
				$delete_text = ! EMPTY_TRASH_DAYS
					? __( 'Delete permanently', 'woocommerce' )
					: __( 'Move to trash', 'woocommerce' );
				?>
				<a
					class="submitdelete deletion"
					href="<?php echo esc_url( self::get_trash_or_delete_order_link( $order_id ) ); ?>"
				><?php echo esc_html( $delete_text ); ?></a>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Forms a trash/delete order URL.
	 *
	 * Mirrors WC_Meta_Box_Order_Actions::get_trash_or_delete_order_link() so
	 * this class doesn't depend on a private helper of another class. To be
	 * consolidated when the order detail redesign exits the feature flag.
	 *
	 * @param int $order_id The order ID for which we want a trash/delete URL.
	 * @return string
	 */
	private static function get_trash_or_delete_order_link( int $order_id ): string {
		if ( OrderUtil::custom_orders_table_usage_is_enabled() ) {
			$order = wc_get_order( $order_id );
			if ( ! $order ) {
				return '';
			}
			$order_list_url  = wc_get_container()->get( PageController::class )->get_base_page_url( $order->get_type() );
			$trash_order_url = add_query_arg(
				array(
					'action'           => 'trash',
					'id'               => array( $order_id ),
					'_wp_http_referer' => $order_list_url,
				),
				$order_list_url
			);

			return wp_nonce_url( $trash_order_url, 'bulk-orders' );
		}

		return get_delete_post_link( $order_id ) ?? '';
	}
}
