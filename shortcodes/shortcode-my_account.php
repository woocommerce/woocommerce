<?php
/**
 * My Account Shortcodes
 *
 * Shows the 'my account' section where the customer can view past orders and update their information.
 *
 * @author 		WooThemes
 * @category 	Shortcodes
 * @package 	WooCommerce/Shortcodes/Accounts
 * @version     1.6.4
 */

/**
 * Get the My Account shortcode content.
 *
 * @access public
 * @param array $atts
 * @return string
 */
function get_woocommerce_my_account( $atts ) {
	global $woocommerce;
	return $woocommerce->shortcode_wrapper('woocommerce_my_account', $atts);
}

/**
 * Get the Edit Address shortcode content.
 *
 * @access public
 * @param array $atts
 * @return string
 */
function get_woocommerce_edit_address() {
	global $woocommerce;
	return $woocommerce->shortcode_wrapper('woocommerce_edit_address');
}

/**
 * Get the Change Password shortcode content.
 *
 * @access public
 * @param array $atts
 * @return string
 */
function get_woocommerce_change_password() {
	global $woocommerce;
	return $woocommerce->shortcode_wrapper('woocommerce_change_password');
}

/**
 * Get the View Order shortcode content.
 *
 * @access public
 * @param array $atts
 * @return string
 */
function get_woocommerce_view_order() {
	global $woocommerce;
	return $woocommerce->shortcode_wrapper('woocommerce_view_order');
}


/**
 * My Account Shortcode.
 *
 * @access public
 * @param mixed $atts
 * @return void
 */
function woocommerce_my_account( $atts ) {
	global $woocommerce, $current_user;

	$woocommerce->nocache();

	if ( ! is_user_logged_in() ) :

		woocommerce_get_template( 'myaccount/form-login.php' );

	else :

		extract(shortcode_atts(array(
	    	'recent_orders' => 5
		), $atts));

	  	$recent_orders = ('all' == $recent_orders) ? -1 : $recent_orders;

		get_currentuserinfo();

		woocommerce_get_template( 'myaccount/my-account.php', array(
			'current_user' 	=> $current_user,
			'recent_orders' 	=> $recent_orders
		) );

	endif;

}

/**
 * Edit Address Shortcode.
 *
 * @todo Address fields should be loaded using the array defined in
 * the checkout class, and the fields should be built off of that.
 *
 * Adapted from spencerfinnell's pull request
 *
 * @access public
 */
function woocommerce_edit_address() {
	global $woocommerce;

	$woocommerce->nocache();

	if ( ! is_user_logged_in() ) return;

	$load_address = woocommerce_get_address_to_edit();

	$address = $woocommerce->countries->get_address_fields( get_user_meta( get_current_user_id(), $load_address . '_country', true ), $load_address . '_' );

	woocommerce_get_template( 'myaccount/form-edit-address.php', array(
		'load_address' 	=> $load_address,
		'address'		=> $address
	) );
}

/**
 * Save and and update a billing or shipping address if the
 * form was submitted through the user account page.
 *
 * @access public
 */
function woocommerce_save_address() {
	global $woocommerce;

	if ( 'POST' !== strtoupper( $_SERVER[ 'REQUEST_METHOD' ] ) )
		return;

	if ( empty( $_POST[ 'action' ] ) || ( 'edit_address' !== $_POST[ 'action' ] ) )
		return;

	$woocommerce->verify_nonce( 'edit_address' );

	$validation = $woocommerce->validation();

	$user_id = get_current_user_id();

	if ( $user_id <= 0 ) return;

	$load_address = woocommerce_get_address_to_edit();

	$address = $woocommerce->countries->get_address_fields( esc_attr($_POST[ $load_address . '_country' ]), $load_address . '_' );

	foreach ($address as $key => $field) :

		if (!isset($field['type'])) $field['type'] = 'text';

		// Get Value
		switch ($field['type']) :
			case "checkbox" :
				$_POST[$key] = isset($_POST[$key]) ? 1 : 0;
			break;
			default :
				$_POST[$key] = isset($_POST[$key]) ? woocommerce_clean($_POST[$key]) : '';
			break;
		endswitch;

		// Hook to allow modification of value
		$_POST[$key] = apply_filters('woocommerce_process_myaccount_field_' . $key, $_POST[$key]);

		// Validation: Required fields
		if ( isset($field['required']) && $field['required'] && empty($_POST[$key]) ) $woocommerce->add_error( $field['label'] . ' ' . __('is a required field.', 'woocommerce') );

		// Postcode
		if ($key=='billing_postcode' || $key=='shipping_postcode') :
			if ( ! $validation->is_postcode( $_POST[$key], $_POST[ $load_address . '_country' ] ) ) :
				$woocommerce->add_error( __( 'Please enter a valid postcode/ZIP.', 'woocommerce' ) );
			else :
				$_POST[$key] = $validation->format_postcode( $_POST[$key], $_POST[ $load_address . '_country' ] );
			endif;
		endif;

	endforeach;

	if ( $woocommerce->error_count() == 0 ) {

		foreach ($address as $key => $field) :
			update_user_meta( $user_id, $key, $_POST[$key] );
		endforeach;

		do_action( 'woocommerce_customer_save_address', $user_id );

		wp_safe_redirect( get_permalink( woocommerce_get_page_id('myaccount') ) );
		exit;
	}
}

add_action( 'template_redirect', 'woocommerce_save_address' );

/**
 * Figure out which address is being viewed/edited.
 *
 * @access public
 */
function woocommerce_get_address_to_edit() {

	$load_address = ( isset( $_GET[ 'address' ] ) ) ? esc_attr( $_GET[ 'address' ] ) : '';

	$load_address = ( $load_address == 'billing' || $load_address == 'shipping' ) ? $load_address : '';

	return $load_address;
}

/**
 * Change Password Shortcode
 *
 * @access public
 */
function woocommerce_change_password() {
	global $woocommerce;

	if ( ! is_user_logged_in() ) return;

	woocommerce_get_template( 'myaccount/form-change-password.php' );
}

/**
 * Save the password and redirect back to the my account page.
 *
 * @access public
 */
function woocommerce_save_password() {
	global $woocommerce;

	if ( 'POST' !== strtoupper( $_SERVER[ 'REQUEST_METHOD' ] ) )
		return;

	if ( empty( $_POST[ 'action' ] ) || ( 'change_password' !== $_POST[ 'action' ] ) )
		return;

	$woocommerce->verify_nonce( 'change_password' );

	$user_id = get_current_user_id();

	if ( $user_id <= 0 )
		return;

	$_POST = array_map( 'woocommerce_clean', $_POST );

	if ( empty( $_POST[ 'password_1' ] ) || empty( $_POST[ 'password_2' ] ) )
		$woocommerce->add_error( __( 'Please enter your password.', 'woocommerce' ) );

	if ( $_POST[ 'password_1' ] !== $_POST[ 'password_2' ] )
		$woocommerce->add_error( __('Passwords do not match.', 'woocommerce') );

	if ( $woocommerce->error_count() == 0 ) {

		wp_update_user( array ('ID' => $user_id, 'user_pass' => esc_attr( $_POST['password_1'] ) ) ) ;

		do_action( 'woocommerce_customer_change_password', $user_id );

		wp_safe_redirect( get_permalink( woocommerce_get_page_id('myaccount') ) );
		exit;
	}
}

add_action( 'template_redirect', 'woocommerce_save_password' );

/**
 * View Order Shortcode
 *
 * @access public
 */
function woocommerce_view_order() {
	global $woocommerce;

	$woocommerce->nocache();

	if ( ! is_user_logged_in() ) return;

	$user_id      	= get_current_user_id();
	$order_id		= ( isset( $_GET['order'] ) ) ? $_GET['order'] : 0;
	$order 			= new WC_Order( $order_id );

	if ( $order_id == 0 ) {
		woocommerce_get_template('myaccount/my-orders.php', array( 'recent_orders' => 10 ));
		return;
	}

	if ( $order->user_id != $user_id ) {
		echo '<div class="woocommerce_error">' . __('Invalid order.', 'woocommerce') . ' <a href="'.get_permalink( woocommerce_get_page_id('myaccount') ).'">'. __('My Account &rarr;', 'woocommerce') .'</a>' . '</div>';
		return;
	}

	$status = get_term_by('slug', $order->status, 'shop_order_status');

	echo '<p class="order-info">'
	. sprintf( __( 'Order <mark class="order-number">%s</mark> made on <mark class="order-date">%s</mark>', 'woocommerce'), $order->get_order_number(), date_i18n( get_option( 'date_format' ), strtotime( $order->order_date ) ) )
	. '. ' . sprintf( __( 'Order status: <mark class="order-status">%s</mark>', 'woocommerce' ), __( $status->name, 'woocommerce' ) )
	. '.</p>';

	$notes = $order->get_customer_order_notes();
	if ($notes) :
		?>
		<h2><?php _e('Order Updates', 'woocommerce'); ?></h2>
		<ol class="commentlist notes">
			<?php foreach ($notes as $note) : ?>
			<li class="comment note">
				<div class="comment_container">
					<div class="comment-text">
						<p class="meta"><?php echo date_i18n('l jS \of F Y, h:ia', strtotime($note->comment_date)); ?></p>
						<div class="description">
							<?php echo wpautop(wptexturize($note->comment_content)); ?>
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