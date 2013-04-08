<?php

require_once("class.shareyourcart-wp.php");

if ( ! class_exists( 'ShareYourCartWooCommerce', false ) ) {

	/**
	 * ShareYourCartWooCommerce class.
	 *
	 * @extends ShareYourCartWordpressPlugin
	 */
	class ShareYourCartWooCommerce extends ShareYourCartWordpressPlugin{

	    public $_plugin_name  = "shareyourcart_woo_commerce";
	    public $_post_user_id = 1;

		/**
		 * processInit function.
		 *
		 * @access public
		 * @return void
		 */
		public function processInit() {
			if ( isset( $_REQUEST['action'] ) ) {
				switch( $_REQUEST['action'] ) {
					case $this->_plugin_name:
						$this->buttonCallback20();
					break;
					case $this->_plugin_name.'_coupon':
						$this->couponCallback();
					break;
				}
			}
		}

	    /**
	     * isCartActive function.
	     *
	     * @access public
	     * @return bool
	     */
	    public function isCartActive() {
		    return in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) );
	    }

		/**
		 * Extend the base class implementation
		 *
		 * @access public
		 * @return void
		 */
		public function pluginsLoadedHook() {
			parent::pluginsLoadedHook();

			if ( ! $this->isCartActive() )
				return;

			add_action( 'init', array( $this, 'processInit' ) );
			add_action( 'woocommerce_before_single_product', array( $this, 'showProductButton' ) );
			add_action( 'woocommerce_cart_contents', array( $this, 'showCartButton' ) );
			add_action( 'woocommerce_checkout_before_customer_details', array( $this, 'showCheckoutButton' ) );
		}

		/**
		 * Return the jQuery sibling selector for the product button
		 *
		 * @access protected
		 * @return string
		 */
		protected function getProductButtonPosition() {
			$selector = parent::getProductButtonPosition();
			return ( ! empty( $selector ) ? $selector : ".summary .price .amount" );
		}

		/**
		 * Return the jQuery sibling selector for the cart button
		 *
		 * @access protected
		 * @return string
		 */
		protected function getCartButtonPosition(){
			$selector = parent::getCartButtonPosition();
			return ( ! empty( $selector ) ? $selector : ".cart-subtotal .amount" );
		}

	    /**
	     * getSecretKey function.
	     *
	     * @access public
	     * @return string
	     */
	    public function getSecretKey() {
		    return 'd3ce6c18-7e45-495d-aa4c-8f63edee03a5';
	    }

	    /**
	     * isSingleProduct function.
	     *
	     * @access public
	     * @return bool
	     */
	    public function isSingleProduct() {
	    	return is_singular( 'product' );
	    }

	    /**
	     * saveCoupon function.
	     *
	     * @access protected
	     * @param mixed $token
	     * @param mixed $coupon_code
	     * @param mixed $coupon_value
	     * @param mixed $coupon_type
	     * @param array $product_unique_ids (default: array())
	     * @return void
	     */
	    protected function saveCoupon( $token, $coupon_code, $coupon_value, $coupon_type, $product_unique_ids = array() ) {

			// Create coupon
			$post_id = $this->_saveCouponPost( $coupon_code );

			// Set coupon meta
			switch ( $coupon_type ) {
				case 'amount':
					$discount_type = 'fixed_product';
					$free_shipping = 'no';
				break;
				case 'percent':
					$discount_type = 'percent_product';
					$free_shipping = 'no';
				break;
				case 'free_shipping':
					$discount_type = 'fixed_product';
					$coupon_value  = 0;
					$free_shipping = 'yes';
				break;
				default :
					$discount_type = 'fixed_cart';
					$free_shipping = 'no';
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
			update_post_meta( $post_id, 'product_ids', implode(',', $product_unique_ids));
			update_post_meta( $post_id, 'individual_use', 'yes' );
			update_post_meta( $post_id, 'coupon_amount', $coupon_value );
			update_post_meta( $post_id, 'discount_type', $discount_type );

			// parent
			parent::saveCoupon( $token, $coupon_code, $coupon_value, $coupon_type );
	    }

	    /**
	     * applyCoupon function.
	     *
	     * @access public
	     * @param mixed $coupon_code
	     * @return void
	     */
	    public function applyCoupon( $coupon_code ) {}

	    /**
	     * _saveCouponPost function.
	     *
	     * @access private
	     * @param mixed $coupon_code
	     * @return void
	     */
	    private function _saveCouponPost( $coupon_code ){
			$new_post = array(
				'post_title'    => $coupon_code,
				'post_name'     => ereg_replace( "[^A-Za-z0-9]", "", $coupon_code ),
				'post_content'  => '',
				'post_status'   => 'publish',
				'comment_status'=> 'closed',
				'ping_status'   => 'closed',
				'post_author'   => $this->_post_user_id,
				'post_type'     => 'shop_coupon'
			);

			$post_id = wp_insert_post( $new_post );

			return $post_id;
	    }

        public function showCheckoutButton() {
            echo $this->getButton();
        }

		/**
		 * getButtonCallbackURL function.
		 *
		 * @access public
		 * @return void
		 */
		public function getButtonCallbackURL() {
			global $wp_query;

			$callback_url = add_query_arg( 'action', $this->_plugin_name, trailingslashit( home_url() ) );

			if ( $this->isSingleProduct() ) {
				$callback_url .= '&p='. $wp_query->post->ID;
			}

			return $callback_url;
		}

	    /**
	     * This function is to be used by WooCommerce from version 2.0
	     *
	     * @access public
	     * @return void
	     */
	    public function buttonCallback20(){
			if ( ! $this->isCartActive() )
				return;

			global $woocommerce;

			$this->_loadWooCommerce();

			$params = array(
				'callback_url' => get_bloginfo('wpurl').'/?action='.$this->_plugin_name.'_coupon'.(isset($_REQUEST['p']) ? '&product='.$_REQUEST['p'] : '' ),
				'success_url'  => get_bloginfo('wpurl').'/?page_id='.get_option('woocommerce_cart_page_id'),
				'cancel_url'   => get_bloginfo('wpurl').'/?page_id='.get_option('woocommerce_cart_page_id'),
			);

			//there is no product set, thus send the products from the shopping cart
			if ( ! isset( $_REQUEST['p'] ) ) {
				if ( ! isset( $woocommerce->session->cart ) /*&& !is_array( $woocommerce->session->cart )*/ )
					exit("Cart is empty");

				foreach( $woocommerce->session->cart as $cart_details ) {
					$params['cart'][] = $this->_getProductDetails20( $cart_details['product_id'] );
				}
			} else {
				$params['cart'][] = $this->_getProductDetails20( $_REQUEST['p'] );
			}

			try {
				$this->startSession( $params );
			} catch( Exception $e ) {
				//display the error to the user
				echo $e->getMessage();
			}
			exit;
		}

		/**
		 * This function is to be used by WooCommerce from version 2.0
		 *
		 * @access private
		 * @param mixed $product_id
		 * @return void
		 */
		private function _getProductDetails20( $product_id ) {
			global $woocommerce;

			$product = get_product( $product_id );

			//WooCommerce actually echoes the image
			ob_start();
			echo $product->get_image(); // older WooCommerce versions might allready echo, but newer versions don't, so force it anyway
			$image = ob_get_clean();

			//check is image actually a HTML img entity
			if ( ( $doc = @DomDocument::loadHTML( $image ) ) !== FALSE ) {
				$imageTags =  $doc->getElementsByTagName('img');

				if ( $imageTags->length > 0 )
					$src = $imageTags->item(0)->getAttribute('src');

				//replace image only if src has been set
				if ( ! empty( $src ) )
					$image = $src;
			}

			return array(
				"item_name"        => $product->get_title(), //$product->post->post_title,
				"item_url"         => get_permalink( $product_id ),
				"item_price"       => $product->get_price(),
				"item_picture_url" => $image,
				"item_unique_id"   => $product_id,
			);
		}

	    /**
	     * loadSessionData function.
	     *
	     * @access public
	     * @return void
	     */
	    public function loadSessionData() {}

		/**
		 * _loadWooCommerce function.
		 *
		 * @access private
		 * @return void
		 */
		private function _loadWooCommerce(){
			// Sometimes the WooCommerce Class is not loaded...
			if ( ! class_exists( 'Woocommerce', false ) ) {
				require_once( ABSPATH . 'wp-content/plugins/woocommerce/woocommerce.php' );
			}

			// Important Classes Not included
			if ( ! function_exists( 'has_post_thumbnail' ) ) {
				require_once( ABSPATH . 'wp-includes/post-thumbnail-template.php' );
			}
		}
	}

	//TODO: see why this is not used
	add_action( ShareYourCartWordpressPlugin::getPluginFile(), array( 'ShareYourCartWooCommerce','uninstallHook' ) );
}
