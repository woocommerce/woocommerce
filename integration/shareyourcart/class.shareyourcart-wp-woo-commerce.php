<?php

require_once "class.shareyourcart-wp.php";

class ShareYourCartWooCommerce extends ShareYourCartWordpressPlugin{

	public $_plugin_name  = "shareyourcart_woo_commerce";
	public $_post_user_id = 1;

	public function processInit() {
		if (isset($_REQUEST['action'])) {
			switch ($_REQUEST['action']) {
			case $this->_plugin_name:
				$this->buttonCallback();
				break;

			case $this->_plugin_name.'_coupon':
				$this->couponCallback();
				break;
			}
		}
	}

	public function isCartActive() {
		return true;
	}

	/*
	 *
	 * Extend the base class implementation
	 *
	 */
	public function pluginsLoadedHook() {
		parent::pluginsLoadedHook();

		if (!$this->isCartActive()) return;

		add_action('init', array(&$this, 'processInit'));

		$this->_hookButton();
	}

	private function _hookButton() {
		if ($this->isCartActive()) {
			if (isset($_GET['product']))
				add_filter('woocommerce_product_description_heading', array(&$this, '_getProductButton'));

			add_action('woocommerce_cart_contents', array(&$this, '_getCartButton'));
		}
	}

	public function _getProductButton() {
		$passed_arguments = func_get_args();
		$description = array_shift($passed_arguments);
		$ret = $description;
		$ret .= $this->getProductButton();
		return $ret;
	}

	public function _getCartButton() {
		$ret = "";
		$ret .= '<div id="shareyourcart_button" style="margin-top:20px;">';
		$ret .= $this->getCartButton();
		$ret .= '</div>';
		$ret .= '<script>';
		$ret .= 'jQuery(document).ready(function(){';
		$ret .=   'jQuery("#shareyourcart_button").appendTo(".coupon:first");';
		$ret .= '});';
		$ret .= '</script>';
		echo $ret;
	}

	public function getSecretKey() {
		return '2cfd496d-7812-44ba-91ce-e43c59f6c680';
	}

	public function isSingleProduct() {
		return isset($_GET['product']) ? true : false;
	}

	public function saveCoupon($token, $coupon_code, $coupon_value, $coupon_type) {
		$post_id = $this->_saveCouponPost($coupon_code);

		$this->_saveCouponMetaData($post_id, $this->_getCouponMetaData($coupon_type, $coupon_value));

		parent::saveCoupon($token, $coupon_code, $coupon_value, $coupon_type);
	}

	public function applyCoupon($coupon_code) {
		//$this->_loadWooCommerce();

		//global $woocommerce;
		//$woocommerce->cart->add_discount($coupon_code);

		return;
	}

	private function _saveCouponPost($coupon_code) {
		$new_post = array(
			'post_title'    => $coupon_code,
			'post_name'     => ereg_replace("[^A-Za-z0-9]", "", $coupon_code),
			'post_content'  => '',
			'post_status'   => 'publish',
			'comment_status'=> 'closed',
			'ping_status'   => 'closed',
			'post_date'     => date('Y-m-d H:i:s'),
			'post_author'   => $this->_post_user_id,
			'post_type'     => 'shop_coupon',
			'post_category' => array(0)
		);

		$post_id = wp_insert_post($new_post);

		$new_post['ID']  = $post_id;
		$new_post['guid']= get_bloginfo('url').'/?post_type=shop_coupon&p='.$post_id;

		wp_update_post($new_post);

		return $post_id;
	}

	private function _saveCouponMetaData($post_id, $metas) {
		foreach ($metas as $meta_key=>$meta_value) {
			$this->_saveSingleCouponMetaData($post_id, $meta_key, $meta_value);
		}
	}

	private function _saveSingleCouponMetaData($post_id, $meta_key, $meta_value) {
		$this->insertRow($this->getTableName('postmeta'),
			array('post_id'      =>  $post_id,
				'meta_key'     =>  $meta_key,
				'meta_value'   =>  $meta_value,
			));
	}

	private function _getCouponMetaData($coupon_type, $discount_value) {
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
			$discount_value = 0;
			$free_shipping  = 'yes';
			break;
		default :
			$discount_type = 'fixed_cart';
			$free_shipping  = 'no';
		}

		return array(
			'customer_email'              => 'a:0:{}',
			'minimum_amount'              => '',
			'exclude_product_categories'  => 'a:0:{}',
			'product_categories'          => 'a:0:{}',
			'free_shipping'               => $free_shipping,
			'apply_before_tax'            => 'yes',
			'expiry_date'                 => '',
			'usage_limit'                 => 1,
			'exclude_product_ids'         => '',
			'product_ids'                 => '',
			'individual_use'              => 'yes',
			'coupon_amount'               => $discount_value,
			'discount_type'               => $discount_type,
			'_edit_lock'                  => '1333727222:1',
		);
	}

	public function getButtonCallbackURL() {
		global $wp_query;

		$callback_url = get_bloginfo('wpurl').'/?action='.$this->_plugin_name;

		if ($this->isSingleProduct()) {
			$callback_url .= '&p='. $wp_query->post->ID;
		}

		return $callback_url;
	}

	public function buttonCallback() {
		if (!$this->isCartActive()) return;

		$this->_loadWooCommerce();

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

		$product->get_image();

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

	private function _loadWooCommerce() {
		// Sometimes the WooCommerce Class is not loaded...

		if (!class_exists('Woocommerce', false)) {
			require_once ABSPATH . 'wp-content/plugins/woocommerce/woocommerce.php';
		}

		// Important Classes Not included
		if (!function_exists('has_post_thumbnail')) {
			require_once ABSPATH . 'wp-includes/post-thumbnail-template.php';
		}
	}
}

$shareYourCartWooCommerce = new ShareYourCartWooCommerce();
