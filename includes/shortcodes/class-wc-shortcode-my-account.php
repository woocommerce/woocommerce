<?php
/**
 * My Account Shortcodes
 *
 * Shows the 'my account' section where the customer can view past orders and update their information.
 *
 * @author 		WooThemes
 * @category 	Shortcodes
 * @package 	WooCommerce/Shortcodes/My_Account
 * @version     2.0.0
 */
class WC_Shortcode_My_Account {

	/**
	 * Get the shortcode content.
	 *
	 * @access public
	 * @param array $atts
	 * @return string
	 */
	public static function get( $atts ) {
		global $woocommerce;
		return $woocommerce->get_helper( 'shortcode' )->shortcode_wrapper( array( __CLASS__, 'output' ), $atts );
	}

	/**
	 * Output the shortcode.
	 *
	 * @access public
	 * @param array $atts
	 * @return void
	 */
	public static function output( $atts ) {
		global $woocommerce, $wp;

		if ( ! is_user_logged_in() ) {

			woocommerce_get_template( 'myaccount/form-login.php' );

		} else {

			if ( ! empty( $wp->query_vars['view-order'] ) ) {

				self::view_order( absint( $wp->query_vars['view-order'] ) );

			} elseif ( isset( $wp->query_vars['edit-account'] ) ) {

				self::edit_account();

			} else {

				self::my_account( $atts );

			}
		}
	}

	/**
	 * My account page
	 *
	 * @param  array $atts
	 */
	private function my_account( $atts ) {
		extract( shortcode_atts( array(
	    	'order_count' => 15
		), $atts ) );

		woocommerce_get_template( 'myaccount/my-account.php', array(
			'current_user' 	=> get_user_by( 'id', get_current_user_id() ),
			'order_count' 	=> 'all' == $order_count ? -1 : $order_count
		) );
	}

	/**
	 * View order page
	 *
	 * @param  int $order_id
	 */
	private function view_order( $order_id ) {
		global $woocommerce;

		$user_id      	= get_current_user_id();
		$order 			= new WC_Order( $order_id );

		if ( $order->user_id != $user_id ) {
			echo '<div class="woocommerce-error">' . __( 'Invalid order.', 'woocommerce' ) . ' <a href="' . get_permalink( woocommerce_get_page_id( 'myaccount' ) ).'">'. __( 'My Account &rarr;', 'woocommerce' ) .'</a>' . '</div>';
			return;
		}

		$status = get_term_by( 'slug', $order->status, 'shop_order_status' );

		echo '<p class="order-info">'
		. sprintf( __( 'Order <mark class="order-number">%s</mark> made on <mark class="order-date">%s</mark>', 'woocommerce'), $order->get_order_number(), date_i18n( get_option( 'date_format' ), strtotime( $order->order_date ) ) )
		. '. ' . sprintf( __( 'Order status: <mark class="order-status">%s</mark>', 'woocommerce' ), __( $status->name, 'woocommerce' ) )
		. '.</p>';

		if ( $notes = $order->get_customer_order_notes() ) :
			?>
			<h2><?php _e( 'Order Updates', 'woocommerce' ); ?></h2>
			<ol class="commentlist notes">
				<?php foreach ($notes as $note) : ?>
				<li class="comment note">
					<div class="comment_container">
						<div class="comment-text">
							<p class="meta"><?php echo date_i18n(__( 'l jS \of F Y, h:ia', 'woocommerce' ), strtotime($note->comment_date)); ?></p>
							<div class="description">
								<?php echo wpautop( wptexturize( $note->comment_content ) ); ?>
							</div>
			  				<div class="clear"></div>
			  			</div>
						<div class="clear"></div>
					</div>
				</li>
				<?php endforeach; ?>
			</ol>
			<?php
		endif;

		do_action( 'woocommerce_view_order', $order_id );
	}

	/**
	 * Edit account details page
	 */
	private function edit_account() {
		woocommerce_get_template( 'myaccount/form-edit-account.php', array( 'user' => get_user_by( 'id', get_current_user_id() ) ) );
	}
}