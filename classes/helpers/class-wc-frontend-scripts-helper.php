<?php

return new WC_Frontend_Scripts_Helper();

class WC_Frontend_Scripts_Helper {
	/**
	 * Register/queue frontend scripts.
	 *
	 * @access public
	 * @return void
	 */
	public function load_scripts() {
		global $post, $wp, $woocommerce;

		$suffix               = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';
		$lightbox_en          = get_option( 'woocommerce_enable_lightbox' ) == 'yes' ? true : false;
		$chosen_en            = get_option( 'woocommerce_enable_chosen' ) == 'yes' ? true : false;
		$ajax_cart_en         = get_option( 'woocommerce_enable_ajax_add_to_cart' ) == 'yes' ? true : false;
		$assets_path          = str_replace( array( 'http:', 'https:' ), '', $woocommerce->plugin_url() ) . '/assets/';
		$frontend_script_path = $assets_path . 'js/frontend/';

		// Register any scripts for later use, or used as dependencies
		wp_register_script( 'chosen', $assets_path . 'js/chosen/chosen.jquery' . $suffix . '.js', array( 'jquery' ), '0.9.14', true );
		wp_register_script( 'jquery-blockui', $assets_path . 'js/jquery-blockui/jquery.blockUI' . $suffix . '.js', array( 'jquery' ), '2.60', true );
		wp_register_script( 'jquery-placeholder', $assets_path . 'js/jquery-placeholder/jquery.placeholder' . $suffix . '.js', array( 'jquery' ), $woocommerce->version, true );

		wp_register_script( 'wc-add-to-cart-variation', $frontend_script_path . 'add-to-cart-variation' . $suffix . '.js', array( 'jquery' ), $woocommerce->version, true );
		wp_register_script( 'wc-single-product', $frontend_script_path . 'single-product' . $suffix . '.js', array( 'jquery' ), $woocommerce->version, true );
		wp_register_script( 'jquery-cookie', $assets_path . 'js/jquery-cookie/jquery.cookie' . $suffix . '.js', array( 'jquery' ), '1.3.1', true );

		// Queue frontend scripts conditionally
		if ( $ajax_cart_en )
			wp_enqueue_script( 'wc-add-to-cart', $frontend_script_path . 'add-to-cart' . $suffix . '.js', array( 'jquery' ), $woocommerce->version, true );

		if ( is_cart() )
			wp_enqueue_script( 'wc-cart', $frontend_script_path . 'cart' . $suffix . '.js', array( 'jquery' ), $woocommerce->version, true );

		if ( is_checkout() ) {
			if ( $chosen_en ) {
				wp_enqueue_script( 'wc-chosen', $frontend_script_path . 'chosen-frontend' . $suffix . '.js', array( 'chosen' ), $woocommerce->version, true );
				wp_enqueue_style( 'woocommerce_chosen_styles', $assets_path . 'css/chosen.css' );
			}

			wp_enqueue_script( 'wc-checkout', $frontend_script_path . 'checkout' . $suffix . '.js', array( 'jquery', 'woocommerce' ), $woocommerce->version, true );
		}

		if ( $lightbox_en && ( is_product() || ( ! empty( $post->post_content ) && strstr( $post->post_content, '[product_page' ) ) ) ) {
			wp_enqueue_script( 'prettyPhoto', $assets_path . 'js/prettyPhoto/jquery.prettyPhoto' . $suffix . '.js', array( 'jquery' ), '3.1.5', true );
			wp_enqueue_script( 'prettyPhoto-init', $assets_path . 'js/prettyPhoto/jquery.prettyPhoto.init' . $suffix . '.js', array( 'jquery' ), $woocommerce->version, true );
			wp_enqueue_style( 'woocommerce_prettyPhoto_css', $assets_path . 'css/prettyPhoto.css' );
		}

		if ( is_product() )
			wp_enqueue_script( 'wc-single-product' );

		// Global frontend scripts
		wp_enqueue_script( 'woocommerce', $frontend_script_path . 'woocommerce' . $suffix . '.js', array( 'jquery', 'jquery-blockui' ), $woocommerce->version, true );
		wp_enqueue_script( 'wc-cart-fragments', $frontend_script_path . 'cart-fragments' . $suffix . '.js', array( 'jquery', 'jquery-cookie' ), $woocommerce->version, true );
		wp_enqueue_script( 'jquery-placeholder' );

		// Variables for JS scripts
		$woocommerce_params = array(
			'countries'                        => json_encode( $woocommerce->countries->get_allowed_country_states() ),
			'plugin_url'                       => $woocommerce->plugin_url(),
			'ajax_url'                         => $woocommerce->ajax_url(),
			'ajax_loader_url'                  => apply_filters( 'woocommerce_ajax_loader_url', $assets_path . 'images/ajax-loader@2x.gif' ),
			'i18n_select_state_text'           => esc_attr__( 'Select an option&hellip;', 'woocommerce' ),
			'i18n_required_rating_text'        => esc_attr__( 'Please select a rating', 'woocommerce' ),
			'i18n_no_matching_variations_text' => esc_attr__( 'Sorry, no products matched your selection. Please choose a different combination.', 'woocommerce' ),
			'i18n_required_text'               => esc_attr__( 'required', 'woocommerce' ),
			'i18n_view_cart'                   => esc_attr__( 'View Cart &rarr;', 'woocommerce' ),
			'review_rating_required'           => get_option( 'woocommerce_review_rating_required' ),
			'update_order_review_nonce'        => wp_create_nonce( "update-order-review" ),
			'apply_coupon_nonce'               => wp_create_nonce( "apply-coupon" ),
			'option_guest_checkout'            => get_option( 'woocommerce_enable_guest_checkout' ),
			'checkout_url'                     => add_query_arg( 'action', 'woocommerce-checkout', $woocommerce->ajax_url() ),
			'is_checkout'                      => is_page( woocommerce_get_page_id( 'checkout' ) ) && empty( $wp->query_vars['order-pay'] ) && ! isset( $wp->query_vars['order-received'] ) ? 1 : 0,
			'update_shipping_method_nonce'     => wp_create_nonce( "update-shipping-method" ),
			'cart_url'                         => get_permalink( woocommerce_get_page_id( 'cart' ) ),
			'cart_redirect_after_add'          => get_option( 'woocommerce_cart_redirect_after_add' )
		);

		if ( is_checkout() || is_cart() )
			$woocommerce_params['locale'] = json_encode( $woocommerce->countries->get_country_locale() );

		wp_localize_script( 'woocommerce', 'woocommerce_params', apply_filters( 'woocommerce_params', $woocommerce_params ) );

		// CSS Styles
		if ( ! defined( 'WOOCOMMERCE_USE_CSS' ) )
			define( 'WOOCOMMERCE_USE_CSS', true );

		if ( WOOCOMMERCE_USE_CSS ) {
			$css 				= file_exists( get_stylesheet_directory() . '/woocommerce/style.css' ) ? get_stylesheet_directory_uri() . '/woocommerce/style.css' : $assets_path . 'css/woocommerce.css';
			$css_layout 		= file_exists( get_stylesheet_directory() . '/woocommerce/style-layout.css' ) ? get_stylesheet_directory_uri() . '/woocommerce/style-layout.css' : $assets_path . 'css/woocommerce-layout.css';
			$css_smallscreen 	= file_exists( get_stylesheet_directory() . '/woocommerce/style-smallscreen.css' ) ? get_stylesheet_directory_uri() . '/woocommerce/style-smallscreen.css' : $assets_path . 'css/woocommerce-smallscreen.css';

			wp_enqueue_style( 'woocommerce_frontend_styles_layout', $css_layout );
			wp_enqueue_style( 'woocommerce_frontend_styles_smallscreen', $css_smallscreen, '','' ,'only screen and (max-width: ' . apply_filters( 'woocommerce_smallscreen_breakpoint', $breakpoint = '768px' ) . ' )' );
			wp_enqueue_style( 'woocommerce_frontend_styles', $css );
		}
	}

	/**
	 * WC requires jQuery 1.7 since it uses functions like .on() for events.
	 * If, by the time wp_print_scrips is called, jQuery is outdated (i.e not
	 * using the version in core) we need to deregister it and register the
	 * core version of the file.
	 *
	 * @access public
	 * @return void
	 */
	public function check_jquery() {
		global $wp_scripts;

		// Enforce minimum version of jQuery
		if ( ! empty( $wp_scripts->registered['jquery']->ver ) && ! empty( $wp_scripts->registered['jquery']->src ) && $wp_scripts->registered['jquery']->ver < '1.7' ) {
			wp_deregister_script( 'jquery' );
			wp_register_script( 'jquery', '/wp-includes/js/jquery/jquery.js', array(), '1.7' );
			wp_enqueue_script( 'jquery' );
		}
	}
}