<?php
/**
 * Checkout_Flow
 *
 * @package automattic/woocommerce-analytics
 */

namespace Automattic\Woocommerce_Analytics;

use Automattic\Jetpack\Constants;
use WC_Product;

/**
 * Class that handles all page view events for the checkout flow (from product view to order confirmation view)
 */
class Checkout_Flow {

	use Woo_Analytics_Trait;

	/**
	 * Constructor.
	 */
	public function init_hooks() {
		$this->find_cart_checkout_content_sources();
		$this->additional_blocks_on_cart_page     = $this->get_additional_blocks_on_page( 'cart' );
		$this->additional_blocks_on_checkout_page = $this->get_additional_blocks_on_page( 'checkout' );

		// single product page view.
		add_action( 'woocommerce_after_single_product', array( $this, 'capture_product_view' ) );

		// order confirmed page view
		add_action( 'woocommerce_thankyou', array( $this, 'capture_order_confirmation_view' ), 10, 1 );

		// cart page view
		add_action( 'wp_footer', array( $this, 'capture_cart_view' ) );

		// checkout page view
		add_action( 'wp_footer', array( $this, 'capture_checkout_view' ) );
	}

	/**
	 * Track a product page view
	 */
	public function capture_product_view() {
		global $product;
		if ( ! $product instanceof WC_Product ) {
			return;
		}

		$this->record_event(
			'woocommerceanalytics_product_view',
			array(),
			$product->get_id()
		);
	}

	/**
	 * Track the order confirmation page view
	 */
	public function capture_order_confirmation_view() {
		$order_id = absint( get_query_var( 'order-received' ) );
		if ( ! $order_id ) {
			return;
		}

		if ( ! is_order_received_page() ) {
			return;
		}

		$order = wc_get_order( $order_id );

		$order_source                              = $order->get_created_via();
		$checkout_page_contains_checkout_block     = '0';
		$checkout_page_contains_checkout_shortcode = '0';

		if ( 'store-api' === $order_source ) {
			$checkout_page_contains_checkout_block     = '1';
			$checkout_page_contains_checkout_shortcode = '0';
		} elseif ( 'checkout' === $order_source ) {
			$checkout_page_contains_checkout_block     = '0';
			$checkout_page_contains_checkout_shortcode = '1';
		}

		$coupons     = $order->get_coupons();
		$coupon_used = 0;
		if ( is_countable( $coupons ) ) {
			$coupon_used = count( $coupons ) ? 1 : 0;
		}

		if ( is_object( WC()->session ) ) {
			$create_account     = true === WC()->session->get( 'wc_checkout_createaccount_used' ) ? 'Yes' : 'No';
			$checkout_page_used = true === WC()->session->get( 'checkout_page_used' ) ? 'Yes' : 'No';
		} else {
			$create_account     = 'No';
			$checkout_page_used = 'No';
		}

		$this->record_event(
			'woocommerceanalytics_order_confirmation_view',
			array(
				'coupon_used'                           => $coupon_used,
				'create_account'                        => $create_account,
				'express_checkout'                      => 'null', // TODO: not solved yet.
				'guest_checkout'                        => $order->get_customer_id() ? 'No' : 'Yes',
				'oi'                                    => $order->get_id(),
				'order_value'                           => $order->get_total(),
				'payment_option'                        => $order->get_payment_method(),
				'products_count'                        => $order->get_item_count(),
				'products'                              => $this->format_items_to_json( $order->get_items() ),
				'order_note'                            => $order->get_customer_note(),
				'shipping_option'                       => $order->get_shipping_method(),
				'from_checkout'                         => $checkout_page_used,
				'checkout_page_contains_checkout_block' => $checkout_page_contains_checkout_block,
				'checkout_page_contains_checkout_shortcode' => $checkout_page_contains_checkout_shortcode,
			)
		);
	}

	/**
	 * Track the cart page view
	 */
	public function capture_cart_view() {
		if ( ! is_cart() ) {
			return;
		}

		$this->record_event(
			'woocommerceanalytics_cart_view',
			array_merge(
				$this->get_cart_checkout_shared_data(),
				array()
			)
		);
	}

	/**
	 * Track the checkout page view
	 */
	public function capture_checkout_view() {
		global $post;
		$checkout_page_id = wc_get_page_id( 'checkout' );

		$is_checkout = $checkout_page_id && is_page( $checkout_page_id )
		|| wc_post_content_has_shortcode( 'woocommerce_checkout' )
		|| has_block( 'woocommerce/checkout', $post )
		|| has_block( 'woocommerce/classic-shortcode', $post )
		|| apply_filters( 'woocommerce_is_checkout', false )
		|| Constants::is_defined( 'WOOCOMMERCE_CHECKOUT' );

		if ( ! $is_checkout ) {
			return;
		}

		$is_in_checkout_page                       = $checkout_page_id === $post->ID ? 'Yes' : 'No';
		$checkout_page_contains_checkout_block     = '0';
		$checkout_page_contains_checkout_shortcode = '1';

		$session = WC()->session;
		if ( is_object( $session ) ) {
			$session->set( 'checkout_page_used', true );
			$session->save_data();
			$draft_order_id = $session->get( 'store_api_draft_order', 0 );
			if ( $draft_order_id ) {
				$checkout_page_contains_checkout_block     = '1';
				$checkout_page_contains_checkout_shortcode = '0';
			}
		}

		// Order received page is also a checkout page, so we need to bail out if we are on that page.
		if ( is_order_received_page() ) {
			return;
		}

		$this->record_event(
			'woocommerceanalytics_checkout_view',
			array_merge(
				$this->get_cart_checkout_shared_data(),
				array(
					'from_checkout' => $is_in_checkout_page,
					'checkout_page_contains_checkout_block' => $checkout_page_contains_checkout_block,
					'checkout_page_contains_checkout_shortcode' => $checkout_page_contains_checkout_shortcode,
				)
			)
		);
	}
}
