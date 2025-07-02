<?php

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\StockNotifications\Frontend;

use Automattic\WooCommerce\Internal\StockNotifications\Config;
use WC_Product;

/**
 * Class for integrating with the product page.
 */
class ProductPageIntegration {

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'woocommerce_simple_add_to_cart', array( $this, 'handle_display_form' ), 30 );
		add_action( 'woocommerce_before_variations_form', array( $this, 'handle_display_form' ) );
		add_filter( 'woocommerce_get_stock_html', array( $this, 'handle_display_form_variation' ), 10, 2 );
	}

	/**
	 * Handle BIS form.
	 *
	 * @return void
	 */
	public function handle_display_form() {
		global $product;
		if ( ! is_product() || ! is_a( $product, 'WC_Product' ) ) {
			return;
		}

		if ( ! $this->is_product_eligible( $product ) ) {
			return;
		}

		// Enqueue the script.
		wp_enqueue_script( 'wc-back-in-stock-form' );

		// Hide the form for variable products if their out of stock variations are visible.
		if ( $product->is_type( 'variable' ) && ! $product->is_in_stock() && 'yes' !== get_option( 'woocommerce_hide_out_of_stock_items' ) ) {
			return;
		}

		$this->display_form( $product );
	}

	/**
	 * Display the form on variations.
	 *
	 * @param string     $html Existing HTML to append to.
	 * @param WC_Product $product Product object.
	 * @return string
	 */
	public function handle_display_form_variation( $html, $product ): string {

		if ( ! is_a( $product, 'WC_Product' ) || ! $product->is_type( 'variation' ) ) {
			return $html;
		}

		if ( ! $this->is_product_eligible( $product ) ) {
			return $html;
		}

		ob_start();
		$this->display_form( $product );
		$form = ob_get_clean();

		return $html . $form;
	}

	/**
	 * Display the form.
	 *
	 * @param WC_Product $product Product object.
	 * @return void
	 */
	public function display_form( WC_Product $product ) {


		// Show already registered?
		// $has_already_signed_up = false;
		// if ( ! wc_bis_is_using_html_caching_for_users() && is_user_logged_in() ) {

		// 	$has_already_signed_up = true;
		// 	// Check for existing sign-ups for varitions with 'any' attributes.
		// 	if ( $product->is_type( 'variation' ) ) {
		// 		foreach ( $product->get_variation_attributes() as $attribute => $value ) {
		// 			if ( '' === $value ) {
		// 				$has_already_signed_up = false;
		// 				break;
		// 			}
		// 		}
		// 	}

		// 	if ( $has_already_signed_up ) {
		// 		$user   = wp_get_current_user();
		// 		$args   = array(
		// 			'product_id' => $product->get_id(),
		// 			'user_id'    => $user->ID,
		// 		);
		// 		$exists = wc_bis_notification_exists( $args, array(), true );
		// 		if ( empty( $exists ) ) {
		// 			$has_already_signed_up = false;
		// 		}
		// 	}
		// }

		// if ( $has_already_signed_up ) {

		// 	$link_attributes          = array();
		// 	$link_attributes['href']  = wc_get_account_endpoint_url( 'backinstock' );
		// 	$link_attributes['class'] = 'wc_bis_signup_form_subscribed_link';
		// 	$header_signed_up_text    = wc_bis_build_shop_text( 'form_header_signed_up', '{manage_account_link}', $link_attributes );

		// 	wc_get_template(
		// 		'single-product/back-in-stock-registered.php',
		// 		array(
		// 			'product'                          => $product,
		// 			'header_signed_up_text'            => $header_signed_up_text,
		// 			'header_signed_up_link_attributes' => $link_attributes,
		// 			'has_already_signed_up'            => $has_already_signed_up,
		// 		),
		// 		false,
		// 		WC_BIS()->get_plugin_path() . '/templates/'
		// 	);

		// 	// Exit.
		// 	return;
		// }

		// Form texts.
		$button_text  = __( 'Notify me', 'woocommerce' );
		$button_class = implode(
			' ',
			array_filter(
				array(
					'button',
					\wc_wp_theme_get_element_class_name( 'button' ),
					'woocommerce_bis_form__button',
				)
			)
		);

		wc_get_template(
			'single-product/back-in-stock-form.php',
			array(
				'product'          => $product,
				'show_checkbox'    => is_user_logged_in() && Config::creates_account_on_signup() && ! Config::requires_account(),
				'show_email_field' => ! is_user_logged_in() && ! Config::requires_account(),
				'button_text'      => $button_text,
				'button_class'     => $button_class,
			)
		);
	}

	/**
	 * Whether a product is eligible for stock notification sign-ups.
	 *
	 * @param WC_Product $product Product object.
	 * @return bool
	 */
	private function is_product_eligible( WC_Product $product ): bool {

		if ( ! Config::allows_signups() ) {
			return false;
		}

		if ( ! $product->is_type( Config::get_supported_product_types() ) ) {
			return false;
		}

		if ( $product->is_in_stock() ) {
			return false;
		}

		if ( $this->is_product_disabled( $product ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Check if notification sign-ups are disabled for this product.
	 *
	 * @param WC_Product $product Product object.
	 * @return bool
	 */
	private function is_product_disabled( WC_Product $product ): bool {

		if ( 'no' === $product->get_meta( '_customer_stock_notifications_allow_signups', true ) ) {
			return true;
		}

		if ( $product->is_type( 'variation' ) ) {

			$parent_product = wc_get_product( $product->get_parent_id() );
			if ( is_a( $parent_product, 'WC_Product' ) && ( 'no' === $parent_product->get_meta( '_customer_stock_notifications_allow_signups', true ) ) ) {
				return true;
			}
		}

		return false;
	}
}
