<?php
/**
 * Order Actions
 *
 * Functions for displaying the order actions meta box.
 *
 * @package     WooCommerce\Admin\Meta Boxes
 * @version     2.1.0
 */

use Automattic\WooCommerce\Enums\OrderStatus;
use Automattic\WooCommerce\Internal\Admin\Orders\PageController;
use Automattic\WooCommerce\Internal\Orders\OrderNoteGroup;
use Automattic\WooCommerce\Utilities\OrderUtil;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * WC_Meta_Box_Order_Actions Class.
 */
class WC_Meta_Box_Order_Actions {

	/**
	 * Output the metabox.
	 *
	 * @param WP_Post|WC_Order $post Post or order object.
	 */
	public static function output( $post ) {
		global $theorder;

		OrderUtil::init_theorder_object( $post );
		$order = $theorder;

		$order_id         = $order->get_id();
		$order_actions    = self::get_available_order_actions_for_order( $order );
		$order_menu_items = self::get_order_actions_menu_items( $order );
		?>
		<ul class="order_actions submitbox">

			<?php
			/**
			 * Fires at the start of order actions meta box rendering.
			 *
			 * @since 2.1.0
			 */
			do_action( 'woocommerce_order_actions_start', $order_id );
			?>


			<li class="wide" id="actions">
				<select name="wc_order_action">
					<option value=""><?php esc_html_e( 'Choose an action...', 'woocommerce' ); ?></option>
					<?php foreach ( $order_actions as $action => $title ) { ?>
						<option value="<?php echo esc_attr( $action ); ?>"><?php echo esc_html( $title ); ?></option>
					<?php } ?>
				</select>
				<button class="button wc-reload"><span><?php esc_html_e( 'Apply', 'woocommerce' ); ?></span></button>
			</li>

			<li class="wide wc-order-save-action">
				<button type="submit" class="button save_order button-primary" name="save" value="<?php echo OrderStatus::AUTO_DRAFT === $order->get_status() ? esc_attr__( 'Create', 'woocommerce' ) : esc_attr__( 'Update', 'woocommerce' ); ?>"><?php echo OrderStatus::AUTO_DRAFT === $order->get_status() ? esc_html__( 'Create', 'woocommerce' ) : esc_html__( 'Update', 'woocommerce' ); ?></button>

				<?php if ( ! empty( $order_menu_items ) ) : ?>
					<div class="wc-order-actions-menu">
						<button type="button" class="button wc-order-actions-menu__toggle" aria-haspopup="menu" aria-expanded="false" aria-controls="wc-order-actions-menu" aria-label="<?php esc_attr_e( 'More order actions', 'woocommerce' ); ?>">
							<svg class="wc-order-actions-menu__icon" viewBox="0 0 24 24" width="24" height="24" aria-hidden="true" focusable="false"><path d="M13 19h-2v-2h2v2zm0-6h-2v-2h2v2zm0-6h-2V5h2v2z" /></svg>
						</button>
						<ul id="wc-order-actions-menu" class="wc-order-actions-menu__list" role="menu" aria-label="<?php esc_attr_e( 'More order actions', 'woocommerce' ); ?>" hidden>
							<?php foreach ( $order_menu_items as $menu_item ) : ?>
								<?php
								if ( empty( $menu_item['label'] ) || empty( $menu_item['url'] ) ) {
									continue;
								}
								?>
								<li role="none">
									<a class="<?php echo esc_attr( isset( $menu_item['class'] ) ? $menu_item['class'] : '' ); ?>" role="menuitem" tabindex="-1" href="<?php echo esc_url( $menu_item['url'] ); ?>"><?php echo esc_html( $menu_item['label'] ); ?></a>
								</li>
							<?php endforeach; ?>
						</ul>
					</div>
				<?php endif; ?>
			</li>

			<?php
			/**
			 * Fires at the end of order actions meta box rendering.
			 *
			 * @since 2.1.0
			 */
			do_action( 'woocommerce_order_actions_end', $order_id );
			?>

		</ul>
		<?php
	}

	/**
	 * Forms a trash/delete order URL.
	 *
	 * @param int $order_id The order ID for which we want a trash/delete URL.
	 *
	 * @return string
	 */
	private static function get_trash_or_delete_order_link( int $order_id ): string {
		if ( OrderUtil::custom_orders_table_usage_is_enabled() ) {
			$order_type      = wc_get_order( $order_id )->get_type();
			$order_list_url  = wc_get_container()->get( PageController::class )->get_base_page_url( $order_type );
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

		return get_delete_post_link( $order_id );
	}

	/**
	 * Get the items for the order "more actions" menu.
	 *
	 * The destructive trash/delete action is included by default when the current
	 * user can delete the order. Each item is rendered as a menu item that inherits
	 * the menu's styling and keyboard accessibility.
	 *
	 * @since 11.0.0
	 *
	 * @param WC_Order $order The order object.
	 * @return array Array of menu items keyed by a unique slug. Each item is an array with:
	 *               'label' (string, required), 'url' (string, required) and 'class' (string, optional).
	 */
	private static function get_order_actions_menu_items( $order ) {
		$menu_items = array();

		if ( current_user_can( 'delete_post', $order->get_id() ) ) {
			$menu_items['trash'] = array(
				'label' => EMPTY_TRASH_DAYS ? __( 'Move to trash', 'woocommerce' ) : __( 'Delete permanently', 'woocommerce' ),
				'url'   => self::get_trash_or_delete_order_link( $order->get_id() ),
				'class' => 'submitdelete deletion',
			);
		}

		/**
		 * Filters the items shown in the order "more actions" menu on the order edit screen.
		 *
		 * Extensions can add their own items; each is rendered as a menu item with the
		 * menu's shared styling and keyboard accessibility.
		 *
		 * @since 11.0.0
		 *
		 * @param array    $menu_items Array of menu items keyed by a unique slug. Each item is an
		 *                             array with 'label' and 'url' keys and an optional 'class' key.
		 * @param WC_Order $order      The order object.
		 */
		return apply_filters( 'woocommerce_order_actions_menu_items', $menu_items, $order );
	}

	/**
	 * Save meta box data.
	 *
	 * @param int     $post_id Post ID.
	 * @param WP_Post $post Post Object.
	 */
	public static function save( $post_id, $post ) {
		// Order data saved, now get it so we can manipulate status.
		$order = wc_get_order( $post_id );

		// Handle button actions.
		if ( ! empty( $_POST['wc_order_action'] ) ) { // @codingStandardsIgnoreLine

			$action = wc_clean( wp_unslash( $_POST['wc_order_action'] ) ); // @codingStandardsIgnoreLine

			if ( 'send_order_details' === $action ) {
				/**
				 * Fires before an order email is resent.
				 *
				 * @since 1.0.0
				 */
				do_action( 'woocommerce_before_resend_order_emails', $order, 'customer_invoice' );

				// Send the customer invoice email.
				WC()->payment_gateways();
				WC()->shipping();
				WC()->mailer()->customer_invoice( $order );

				// Note the event.
				$order->add_order_note( __( 'Order details manually sent to customer.', 'woocommerce' ), false, true, array( 'note_group' => OrderNoteGroup::EMAIL_NOTIFICATION ) );

				/**
				 * Fires after an order email has been resent.
				 *
				 * @since 1.0.0
				 */
				do_action( 'woocommerce_after_resend_order_email', $order, 'customer_invoice' );

				// Change the post saved message.
				add_filter( 'redirect_post_location', array( __CLASS__, 'set_email_sent_message' ) );

			} elseif ( 'send_order_details_admin' === $action ) {

				do_action( 'woocommerce_before_resend_order_emails', $order, 'new_order' );

				WC()->payment_gateways();
				WC()->shipping();
				add_filter( 'woocommerce_new_order_email_allows_resend', '__return_true' );
				WC()->mailer()->emails['WC_Email_New_Order']->trigger( $order->get_id(), $order, true );
				remove_filter( 'woocommerce_new_order_email_allows_resend', '__return_true' );

				do_action( 'woocommerce_after_resend_order_email', $order, 'new_order' );

				// Change the post saved message.
				add_filter( 'redirect_post_location', array( __CLASS__, 'set_email_sent_message' ) );

			} elseif ( 'regenerate_download_permissions' === $action ) {

				$data_store = WC_Data_Store::load( 'customer-download' );
				$data_store->delete_by_order_id( $post_id );
				wc_downloadable_product_permissions( $post_id, true );

			} elseif ( ! did_action( 'woocommerce_order_action_' . sanitize_title( $action ) ) ) {

					do_action( 'woocommerce_order_action_' . sanitize_title( $action ), $order );
			}
		}
	}

	/**
	 * Set the correct message ID.
	 *
	 * @param string $location Location.
	 * @since  2.3.0
	 * @static
	 * @return string
	 */
	public static function set_email_sent_message( $location ) {
		return add_query_arg( 'message', 11, $location );
	}

	/**
	 * Get the available order actions for a given order.
	 *
	 * @since 5.8.0
	 *
	 * @param WC_Order|null $order The order object or null if no order is available.
	 *
	 * @return array
	 */
	private static function get_available_order_actions_for_order( $order ) {
		$actions = array(
			'send_order_details'              => __( 'Send order details to customer', 'woocommerce' ),
			'send_order_details_admin'        => __( 'Resend new order notification', 'woocommerce' ),
			'regenerate_download_permissions' => __( 'Regenerate download permissions', 'woocommerce' ),
		);

		/**
		 * Filter: woocommerce_order_actions
		 * Allows filtering of the available order actions for an order.
		 *
		 * @since 2.1.0 Filter was added.
		 * @since 5.8.0 The $order param was added.
		 *
		 * @param array         $actions The available order actions for the order.
		 * @param WC_Order|null $order   The order object or null if no order is available.
		 */
		return apply_filters( 'woocommerce_order_actions', $actions, $order );
	}
}
