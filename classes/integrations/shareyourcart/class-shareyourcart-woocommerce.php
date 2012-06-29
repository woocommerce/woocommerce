<?php

require_once "sdk/class.shareyourcart-base.php";

class ShareYourCartWooCommerce extends ShareYourCartBase {

	public $settings;
	public $_plugin_name  = "shareyourcart_woo_commerce";
	public $_post_user_id = 1;
	protected static $_VERSION = 1;

	/**
	 * Constructor
	 * @param null
	 */
	function __construct( $settings ) {

		$this->settings = $settings;

		parent::__construct();

		//if there is installed another plugin with a never version
		//do not load this one further
		if (!$this->canLoad()) return;

		// Shortcodes
		add_shortcode( 'shareyourcart', array(&$this, 'getButton') );
		add_shortcode( 'shareyourcart_button', array(&$this, 'getButton') );

		// Actions
		add_action( 'init', array(&$this, 'init') );
		add_action( 'wp', array(&$this, 'hook_buttons') );
		add_action( 'wp_head', array(&$this, 'wp_head') );
	}

	function admin_settings_page() {
		$this->checkSDKStatus(true);
	}

	/**
	 * processInit function.
	 *
	 * @access public
	 */
	public function init() {
		if (isset($_REQUEST['action'])) {
			switch ($_REQUEST['action']) {
				case $this->_plugin_name:
					$this->buttonCallback();
					break;

				case $this->_plugin_name . '_coupon':
					$this->couponCallback();
					break;
			}
		}
	}

	/**
	 * hook_buttons function.
	 *
	 * @access public
	 */
	public function hook_buttons() {
		if ( $this->isSingleProduct() )
			add_filter('woocommerce_product_description_heading', array(&$this, '_getProductButton'));

		add_action('woocommerce_cart_coupon', array(&$this, '_getCartButton'));
	}

	public function wp_head() {
		echo '<meta property="syc:client_id" content="' . $this->getClientId() . '" />';
	}

	public function _getProductButton( $title ) {
		$title .= $this->getProductButton();
		return $title;
	}

	public function _getCartButton() {
		echo '<div id="shareyourcart_button">' . $this->getCartButton() . '</div>';
	}

	public function getSecretKey() {
		return '2cfd496d-7812-44ba-91ce-e43c59f6c680';
	}

	public function isSingleProduct() {
		return is_singular('product');
	}

	public function saveCoupon( $token, $coupon_code, $coupon_value, $coupon_type ) {

		// Create coupon
		$post_id = $this->_saveCouponPost($coupon_code);

		// Set coupon meta
		switch ($coupon_type) {
		case 'amount':
			$discount_type  = 'fixed_product';
			$free_shipping  = 'no';
			break;
		case 'percent':
			$discount_type = 'percent_product';
			$free_shipping  = 'no';
			break;
		case 'free_shipping':
			$discount_type  = 'fixed_product';
			$coupon_value = 0;
			$free_shipping  = 'yes';
			break;
		default :
			$discount_type = 'fixed_cart';
			$free_shipping  = 'no';
		}

		update_post_meta( $post_id, 'customer_email', array() );
		update_post_meta( $post_id, 'minimum_amount', '' );
		update_post_meta( $post_id, 'exclude_product_categories', array() );
		update_post_meta( $post_id, 'product_categories', array() );
		update_post_meta( $post_id, 'free_shipping', $free_shipping );
		update_post_meta( $post_id, 'apply_before_tax', 'yes' );
		update_post_meta( $post_id, 'expiry_date', '' );
		update_post_meta( $post_id, 'usage_limit', 1 );
		update_post_meta( $post_id, 'exclude_product_ids', '' );
		update_post_meta( $post_id, 'product_ids', '' );
		update_post_meta( $post_id, 'individual_use', 'yes' );
		update_post_meta( $post_id, 'coupon_amount', $coupon_value );
		update_post_meta( $post_id, 'discount_type', $discount_type );

		// parent
		parent::saveCoupon( $token, $coupon_code, $coupon_value, $coupon_type );
	}

	public function applyCoupon( $coupon_code ) {}

	private function _saveCouponPost($coupon_code) {
		$new_post = array(
			'post_title'    => $coupon_code,
			'post_name'     => sanitize_title( $coupon_code ),
			'post_content'  => '',
			'post_status'   => 'publish',
			'comment_status'=> 'closed',
			'ping_status'   => 'closed',
			'post_author'   => $this->_post_user_id,
			'post_type'     => 'shop_coupon'
		);

		$post_id = wp_insert_post($new_post);

		return $post_id;
	}

	public function getButtonCallbackURL() {
		global $wp_query;

		$callback_url = add_query_arg( 'action', $this->_plugin_name, trailingslashit( home_url() ) );

		if ($this->isSingleProduct()) {
			$callback_url .= '&p='. $wp_query->post->ID;
		}

		return $callback_url;
	}

	public function buttonCallback() {

		//specify the parameters
		$params = array(
			'callback_url' => get_bloginfo('wpurl').'/?action='.$this->_plugin_name.'_coupon'.(isset($_REQUEST['p']) ? '&p='.$_REQUEST['p'] : '' ),
			'success_url'  => get_option('shopping_cart_url'),
			'cancel_url'   => get_option('shopping_cart_url'),
		);

		//there is no product set, thus send the products from the shopping cart
		if (!isset($_REQUEST['p'])) {
			if (empty($_SESSION['cart']))
				exit("Cart is empty");

			foreach ($_SESSION['cart'] as $cart_details) {
				$params['cart'][] = $this->_getProductDetails($cart_details['product_id']);
			}
		}
		else {
			$params['cart'][] = $this->_getProductDetails($_GET['p']);
		}

		try
		{
			$this->startSession($params);
		}
		catch(Exception $e) {
			//display the error to the user
			echo $e->getMessage();
		}
		exit;
	}

	private function _getProductDetails($product_id) {
		$product = new WC_Product($product_id);

		ob_start();

		echo $product->get_image();

		$image = ob_get_clean();

		return array(
			"item_name"        => $product->get_title(),
			"item_description" => $product->post->post_content,
			"item_url"         => $product->post->guid,
			"item_price"       => $product->price,
			"item_picture_url" => $image,
		);
	}

	public function loadSessionData() {
		return;
	}

	/**
	 *
	 * Get the plugin version.
	 * @return an integer
	 *
	 */
	protected function getPluginVersion() {

		return self::$_VERSION;
	}

	/**
	 *
	 * Return the project's URL
	 *
	 */
	protected function getDomain() {

		return get_bloginfo('url');
	}

	/**
	 *
	 * Return the admin's email
	 *
	 */
	protected function getAdminEmail() {

		return get_settings('admin_email');
	}

	/**
	 *
	 * Set the field value
	 *
	 */
	public function setConfigValue($field, $value) {
		$this->settings[$field] = $value;
		update_option( 'woocommerce_shareyourcart_settings', $this->settings );
	}

	/**
	 *
	 * Get the field value
	 *
	 */
	protected function getConfigValue( $field ) {

		switch ( $field ) {
		case "clientId" :
			return $this->settings['client_id'];
		case "hide_on_checkout" :
			return ( $this->settings['show_on_cart'] == 'yes' ) ? false : true;
		case "hide_on_product" :
			return ( $this->settings['show_on_product'] == 'yes' ) ? false : true;
		case "appKey" :
			return $this->settings['app_key'];
		case "account_status" :
			return 'active';
		case "button_type" :
			
			if ( $this->settings['button_style'] == 'image_button' ) return 2;
			if ( $this->settings['button_style'] == 'custom_html' ) return 3;
			
			return 1; // Standard
			
			break;
		default :
			return ( isset( $this->settings[$field] ) ) ? $this->settings[$field] : '';
		}

	}

	/**
	 *
	 * Execute the SQL statement
	 *
	 */
	protected function executeNonQuery($sql) {

		if (substr($sql, 0, 12) == "CREATE TABLE") {

			require_once ABSPATH . 'wp-admin/includes/upgrade.php';

			//if this is a create table command, use the special function which compares tables
			dbDelta($sql);

		} else {

			global $wpdb;

			//use the normal query
			$wpdb->query($sql);
		}
	}

	/**
	 *
	 * Get the row returned from the SQL
	 *
	 * @return an associative array containing the data of the row OR NULL
	 *         if there is none
	 */
	protected function getRow($sql) {

		global $wpdb;

		//get the row as an associative array
		return $wpdb->get_row($sql, ARRAY_A);
	}

	/**
	 *
	 * Get the table name based on the key
	 *
	 */
	protected function getTableName($key) {
		global $wpdb;

		return $wpdb->base_prefix . $key;
	}

	/**
	 *
	 * Insert the row into the specified table
	 *
	 */
	protected function insertRow($tableName, $data) {
		global $wpdb;

		$wpdb->insert($tableName, $data);
	}

	/**
	 *
	 * Create url for the specified file. The file must be specified in relative path
	 * to the base of the plugin
	 */
	protected function createUrl($file) {
		//get the real file path
		$file = realpath($file);

		//calculate the relative path from this file
		$file = SyC::relativepath(dirname(__FILE__), $file);

		//append the relative path to the current file's URL
		return WP_PLUGIN_URL.'/'.str_replace(basename( __FILE__), "", plugin_basename(__FILE__)).$file;
	}

	/*
	*
	* Called when a new coupon is generated
	*
	*/
	public function couponCallback() {

		parent::couponCallback();

		//since this is actually an API, exit
		exit;
	}

}