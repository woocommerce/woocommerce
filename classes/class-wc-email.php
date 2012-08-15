<?php
/**
 * Email
 *
 * WooCommerce Emails Class which handles the sending on transactional emails and email templates
 *
 * @class 		WC_Email
 * @version		1.6.4
 * @package		WooCommerce/Classes
 * @author 		WooThemes
 */
class WC_Email {

	/**
	 * @var string Stores the emailer's address.
	 * @access private
	 */
	private $_from_address;

	/**
	 * @var string Stores the emailer's name.
	 * @access private
	 */
	private $_from_name;

	/**
	 * Constructor for the email class hooks in all emails that can be sent.
	 *
	 * @access public
	 * @return void
	 */
	function __construct() {
		$this->_from_name 		= get_option('woocommerce_email_from_name');
		$this->_from_address	= get_option('woocommerce_email_from_address');

		/**
		 * Email Header + Footer
		 **/
		add_action('woocommerce_email_header', array(&$this, 'email_header'));
		add_action('woocommerce_email_footer', array(&$this, 'email_footer'));

		/**
		 * Add order meta to email templates
		 **/
		add_action('woocommerce_email_after_order_table', array(&$this, 'order_meta'), 10, 2);

		/**
		 * Hooks for sending emails during store events
		 **/
		add_action('woocommerce_low_stock_notification', array(&$this, 'low_stock'));
		add_action('woocommerce_no_stock_notification', array(&$this, 'no_stock'));
		add_action('woocommerce_product_on_backorder_notification', array(&$this, 'backorder'));

		add_action('woocommerce_order_status_pending_to_processing_notification', array(&$this, 'new_order'));
		add_action('woocommerce_order_status_pending_to_completed_notification', array(&$this, 'new_order'));
		add_action('woocommerce_order_status_pending_to_on-hold_notification', array(&$this, 'new_order'));
		add_action('woocommerce_order_status_failed_to_processing_notification', array(&$this, 'new_order'));
		add_action('woocommerce_order_status_failed_to_completed_notification', array(&$this, 'new_order'));

		add_action('woocommerce_order_status_pending_to_processing_notification', array(&$this, 'customer_processing_order'));
		add_action('woocommerce_order_status_pending_to_on-hold_notification', array(&$this, 'customer_processing_order'));

		add_action('woocommerce_order_status_completed_notification', array(&$this, 'customer_completed_order'));

		add_action('woocommerce_new_customer_note_notification', array(&$this, 'customer_note'));

		// Let 3rd parties unhook the above via this hook
		do_action( 'woocommerce_email', $this );
	}


	/**
	 * Get from name for email.
	 *
	 * @access public
	 * @return string
	 */
	function get_from_name() {
		return $this->_from_name;
	}


	/**
	 * Get from email address.
	 *
	 * @access public
	 * @return string
	 */
	function get_from_address() {
		return $this->_from_address;
	}


	/**
	 * Get the content type for the email.
	 *
	 * @access public
	 * @return string
	 */
	function get_content_type() {
		return 'text/html';
	}


	/**
	 * Get the email header.
	 *
	 * @access public
	 * @param mixed $email_heading heading for the email
	 * @return void
	 */
	function email_header( $email_heading ) {
		woocommerce_get_template('emails/email-header.php', array( 'email_heading' => $email_heading ));
	}


	/**
	 * Get the email footer.
	 *
	 * @access public
	 * @return void
	 */
	function email_footer() {
		woocommerce_get_template('emails/email-footer.php');
	}


	/**
	 * Wraps a message in the woocommerce mail template.
	 *
	 * @access public
	 * @param mixed $email_heading
	 * @param mixed $message
	 * @return string
	 */
	function wrap_message( $email_heading, $message ) {
		// Buffer
		ob_start();

		do_action('woocommerce_email_header', $email_heading);

		echo wpautop(wptexturize( $message ));

		do_action('woocommerce_email_footer');

		// Get contents
		$message = ob_get_clean();

		return $message;
	}


	/**
	 * Send the email.
	 *
	 * @access public
	 * @param mixed $to
	 * @param mixed $subject
	 * @param mixed $message
	 * @param string $headers (default: "Content-Type: text/html\r\n")
	 * @param string $attachments (default: "")
	 * @return void
	 */
	function send( $to, $subject, $message, $headers = "Content-Type: text/html\r\n", $attachments = "" ) {
		add_filter( 'wp_mail_from', array(&$this, 'get_from_address') );
		add_filter( 'wp_mail_from_name', array(&$this, 'get_from_name') );
		add_filter( 'wp_mail_content_type', array(&$this, 'get_content_type') );

		ob_start();

		wp_mail( $to, $subject, $message, $headers, $attachments );

		ob_end_clean();

		// Unhook
		remove_filter( 'wp_mail_from', array(&$this, 'get_from_address') );
		remove_filter( 'wp_mail_from_name', array(&$this, 'get_from_name') );
		remove_filter( 'wp_mail_content_type', array(&$this, 'get_content_type') );
	}


	/**
	 * Prepare and send the new order email.
	 *
	 * @access public
	 * @param mixed $order_id
	 * @return void
	 */
	function new_order( $order_id ) {

		$order = new WC_Order( $order_id );

		$email_heading = __('New Customer Order', 'woocommerce');

		$blogname = wp_specialchars_decode(get_option('blogname'), ENT_QUOTES);

		$subject = apply_filters( 'woocommerce_email_subject_new_order', sprintf( __( '[%s] New Customer Order (%s)', 'woocommerce' ), $blogname, $order->get_order_number() ), $order );

		// Buffer
		ob_start();

		// Get mail template
		woocommerce_get_template('emails/admin-new-order.php', array(
			'order' => $order,
			'email_heading' => $email_heading
		));

		// Get contents
		$message = ob_get_clean();

		//	CC, BCC, additional headers
		$headers = apply_filters('woocommerce_email_headers', '', 'new_order', $order);

		// Attachments
		$attachments = apply_filters('woocommerce_email_attachments', '', 'new_order', $order);

		// Send the mail
		$this->send( get_option('woocommerce_new_order_email_recipient'), $subject, $message, $headers, $attachments);
	}


	/**
	 * Prepare and send the customer processing order email.
	 *
	 * @access public
	 * @param mixed $order_id
	 * @return void
	 */
	function customer_processing_order( $order_id ) {

		$order = new WC_Order( $order_id );

		$email_heading = __('Order Received', 'woocommerce');

		$blogname = wp_specialchars_decode(get_option('blogname'), ENT_QUOTES);

		$subject = apply_filters( 'woocommerce_email_subject_customer_procesing_order', sprintf( __( '[%s] Order Received', 'woocommerce' ), $blogname ), $order );

		// Buffer
		ob_start();

		// Get mail template
		woocommerce_get_template('emails/customer-processing-order.php', array(
			'order' => $order,
			'email_heading' => $email_heading
		));

		// Get contents
		$message = ob_get_clean();

		//	CC, BCC, additional headers
		$headers = apply_filters('woocommerce_email_headers', '', 'customer_processing_order', $order);

		// Attachments
		$attachments = apply_filters('woocommerce_email_attachments', '', 'customer_processing_order', $order);

		// Send the mail
		$this->send( $order->billing_email, $subject, $message, $headers, $attachments );
	}


	/**
	 * Prepare and send the completed order email.
	 *
	 * @access public
	 * @param mixed $order_id
	 * @return void
	 */
	function customer_completed_order( $order_id ) {

		$order = new WC_Order( $order_id );

		if ($order->has_downloadable_item()) :
			$subject		= __('[%s] Order Complete/Download Links', 'woocommerce');
			$email_heading 	= __('Order Complete/Download Links', 'woocommerce');
		else :
			$subject		= __('[%s] Order Complete', 'woocommerce');
			$email_heading 	= __('Order Complete', 'woocommerce');
		endif;

		$email_heading = apply_filters( 'woocommerce_email_heading_customer_completed_order', $email_heading );

		$blogname = wp_specialchars_decode(get_option('blogname'), ENT_QUOTES);

		$subject = apply_filters( 'woocommerce_email_subject_customer_completed_order', sprintf( $subject, $blogname ), $order );

		// Buffer
		ob_start();

		// Get mail template
		woocommerce_get_template('emails/customer-completed-order.php', array(
			'order' => $order,
			'email_heading' => $email_heading
		));

		// Get contents
		$message = ob_get_clean();

		//	CC, BCC, additional headers
		$headers = apply_filters('woocommerce_email_headers', '', 'customer_completed_order', $order);

		// Attachments
		$attachments = apply_filters('woocommerce_email_attachments', '', 'customer_completed_order', $order);

		// Send the mail
		$this->send( $order->billing_email, $subject, $message, $headers, $attachments );
	}


	/**
	 * Prepare and send the customer invoice email.
	 *
	 * @access public
	 * @param mixed $pay_for_order
	 * @return void
	 */
	function customer_invoice( $pay_for_order ) {

		$order = $pay_for_order;

		$blogname = wp_specialchars_decode(get_option('blogname'), ENT_QUOTES);

		if ( $order->status == 'processing' || $order->status == 'completed' ) {
			$email_heading = sprintf( __('Your order on %s', 'woocommerce'), $blogname );
			$subject = apply_filters( 'woocommerce_email_subject_customer_invoice_paid', sprintf( __( '[%s] Your order', 'woocommerce' ), $blogname ), $order );
		} else {
			$email_heading = sprintf( __('Invoice for Order %s', 'woocommerce'), $order->get_order_number() );
			$subject = apply_filters( 'woocommerce_email_subject_customer_invoice', sprintf( __( '[%s] Pay for Order', 'woocommerce' ), $blogname ), $order );
		}

		// Buffer
		ob_start();

		// Get mail template
		woocommerce_get_template('emails/customer-invoice.php', array(
			'order' => $order,
			'email_heading' => $email_heading
		));

		// Get contents
		$message = ob_get_clean();

		//	CC, BCC, additional headers
		$headers = apply_filters('woocommerce_email_headers', '', 'customer_invoice', $order);

		// Attachments
		$attachments = apply_filters('woocommerce_email_attachments', '', 'customer_invoice', $order);

		// Send the mail
		$this->send( $order->billing_email, $subject, $message, $headers, $attachments );
	}


	/**
	 * Prepare and send a customer note email.
	 *
	 * @access public
	 * @param mixed $args
	 * @return void
	 */
	function customer_note( $args ) {

		$defaults = array(
			'order_id' => '',
			'customer_note'	=> ''
		);

		$args = wp_parse_args( $args, $defaults );

		extract( $args );

		if (!$order_id || !$customer_note) return;

		$order = new WC_Order( $order_id );

		$email_heading = __('A note has been added to your order', 'woocommerce');

		$blogname = wp_specialchars_decode(get_option('blogname'), ENT_QUOTES);

		$subject = apply_filters( 'woocommerce_email_subject_customer_note', sprintf( __( '[%s] A note has been added to your order', 'woocommerce' ), $blogname ), $order );

		// Buffer
		ob_start();

		// Get mail template
		woocommerce_get_template('emails/customer-note.php', array(
			'order' => $order,
			'email_heading' => $email_heading,
			'customer_note' => $customer_note
		));

		// Get contents
		$message = ob_get_clean();

		//	CC, BCC, additional headers
		$headers = apply_filters('woocommerce_email_headers', '', 'customer_note', $order);

		// Attachments
		$attachments = apply_filters('woocommerce_email_attachments', '', 'customer_note', $order);

		// Send the mail
		$this->send( $order->billing_email, $subject, $message, $headers, $attachments );
	}


	/**
	 * Low stock notification email.
	 *
	 * @access public
	 * @param mixed $product
	 * @return void
	 */
	function low_stock( $product ) {

		$blogname = wp_specialchars_decode(get_option('blogname'), ENT_QUOTES);

		$subject = apply_filters( 'woocommerce_email_subject_low_stock', sprintf( '[%s] %s', $blogname, __( 'Product low in stock', 'woocommerce' ) ), $product );

		$sku = ($product->sku) ? '(' . $product->sku . ') ' : '';

		if ( ! empty( $product->variation_id ) )
			$title = sprintf(__('Variation #%s of %s', 'woocommerce'), $product->variation_id, get_the_title($product->id)) . ' ' . $sku;
		else
			$title = sprintf(__('Product #%s - %s', 'woocommerce'), $product->id, get_the_title($product->id)) . ' ' . $sku;

		$message = $title . __('is low in stock.', 'woocommerce');

		//	CC, BCC, additional headers
		$headers = apply_filters('woocommerce_email_headers', '', 'low_stock', $product);

		// Attachments
		$attachments = apply_filters('woocommerce_email_attachments', '', 'low_stock', $product);

		// Send the mail
		wp_mail( get_option('woocommerce_stock_email_recipient'), $subject, $message, $headers, $attachments );
	}


	/**
	 * No stock notification email.
	 *
	 * @access public
	 * @param mixed $product
	 * @return void
	 */
	function no_stock( $product ) {

		$blogname = wp_specialchars_decode(get_option('blogname'), ENT_QUOTES);

		$subject = apply_filters( 'woocommerce_email_subject_no_stock', sprintf( '[%s] %s', $blogname, __( 'Product out of stock', 'woocommerce' ) ), $product );

		$sku = ($product->sku) ? '(' . $product->sku . ') ' : '';

		if ( ! empty( $product->variation_id ) )
			$title = sprintf(__('Variation #%s of %s', 'woocommerce'), $product->variation_id, get_the_title($product->id)) . ' ' . $sku;
		else
			$title = sprintf(__('Product #%s - %s', 'woocommerce'), $product->id, get_the_title($product->id)) . ' ' . $sku;

		$message = $title . __('is out of stock.', 'woocommerce');

		//	CC, BCC, additional headers
		$headers = apply_filters('woocommerce_email_headers', '', 'no_stock', $product);

		// Attachments
		$attachments = apply_filters('woocommerce_email_attachments', '', 'no_stock', $product);

		// Send the mail
		wp_mail( get_option('woocommerce_stock_email_recipient'), $subject, $message, $headers, $attachments );
	}


	/**
	 * Backorder notification email.
	 *
	 * @access public
	 * @param mixed $args
	 * @return void
	 */
	function backorder( $args ) {

		$defaults = array(
			'product' => '',
			'quantity' => '',
			'order_id' => ''
		);

		$args = wp_parse_args( $args, $defaults );

		extract( $args );

		if (!$product || !$quantity) return;

		$blogname = wp_specialchars_decode(get_option('blogname'), ENT_QUOTES);

		$subject = apply_filters( 'woocommerce_email_subject_backorder', sprintf( '[%s] %s', $blogname, __( 'Product Backorder', 'woocommerce' ) ), $product );

		$sku = ($product->sku) ? ' (' . $product->sku . ')' : '';

		if ( ! empty( $product->variation_id ) )
			$title = sprintf(__('Variation #%s of %s', 'woocommerce'), $product->variation_id, get_the_title($product->id)) . $sku;
		else
			$title = sprintf(__('Product #%s - %s', 'woocommerce'), $product->id, get_the_title($product->id)) . $sku;

		$message = sprintf(__('%s units of %s have been backordered in order #%s.', 'woocommerce'), $quantity, $title, $order_id );

		//	CC, BCC, additional headers
		$headers = apply_filters('woocommerce_email_headers', '', 'backorder', $args);

		// Attachments
		$attachments = apply_filters('woocommerce_email_attachments', '', 'backorder', $args);

		// Send the mail
		wp_mail( get_option('woocommerce_stock_email_recipient'), $subject, $message, $headers, $attachments );
	}


	/**
	 * Add order meta to email templates.
	 *
	 * @access public
	 * @param mixed $order
	 * @param mixed $sent_to_admin
	 * @return void
	 */
	function order_meta( $order, $sent_to_admin ) {

		$meta = array();
		$show_fields = apply_filters('woocommerce_email_order_meta_keys', array('coupons'), $sent_to_admin);

		if ($order->customer_note) :
			$meta[__('Note', 'woocommerce')] = wptexturize($order->customer_note);
		endif;

		if ($show_fields) foreach ($show_fields as $field) :

			$value = get_post_meta( $order->id, $field, true );
			if ($value) $meta[ucwords(esc_attr($field))] = wptexturize($value);

		endforeach;

		if (sizeof($meta)>0) :
			echo '<h2>'.__('Order information', 'woocommerce').'</h2>';
			foreach ($meta as $key=>$value) :
				echo '<p><strong>'.$key.':</strong> '.$value.'</p>';
			endforeach;
		endif;
	}


	/**
	 * Customer new account welcome email.
	 *
	 * @access public
	 * @param mixed $user_id
	 * @param mixed $plaintext_pass
	 * @return void
	 */
	function customer_new_account( $user_id, $plaintext_pass ) {

		if (!$user_id || !$plaintext_pass) return;

		$user = new WP_User($user_id);

		$user_login = stripslashes($user->user_login);
		$user_email = stripslashes($user->user_email);
		$user_pass 	= $plaintext_pass;

		$blogname = wp_specialchars_decode(get_option('blogname'), ENT_QUOTES);

		$subject		= apply_filters( 'woocommerce_email_subject_customer_new_account', sprintf( __( 'Your account on %s', 'woocommerce'), $blogname ), $user );
		$email_heading 	= sprintf( __( 'Welcome to %s', 'woocommerce'), $blogname );

		// Buffer
		ob_start();

		// Get mail template
		woocommerce_get_template('emails/customer-new-account.php', array(
			'user_login' 	=> $user_login,
			'user_pass'		=> $user_pass,
			'blogname'		=> $blogname,
			'email_heading'	=> $email_heading
		));

		// Get contents
		$message = ob_get_clean();

		//	CC, BCC, additional headers
		$headers = apply_filters('woocommerce_email_headers', '', 'customer_new_account', $user);

		// Attachments
		$attachments = apply_filters('woocommerce_email_attachments', '', 'customer_new_account', $user);

		// Send the mail
		$this->send( $user_email, $subject, $message, $headers, $attachments );
	}

}

/**
 * woocommerce_email class.
 *
 * @extends 	WC_Email
 * @deprecated 	1.4
 * @package		WooCommerce/Classes
 */
class woocommerce_email extends WC_Email {
	public function __construct() {
		_deprecated_function( 'woocommerce_email', '1.4', 'WC_Email()' );
		parent::__construct();
	}
}