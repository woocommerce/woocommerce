<?php
/**
 * Simple Product Class.
 *
 * The default product type kinda product.
 *
 * @package WooCommerce\Classes\Products
 */

use Automattic\WooCommerce\Enums\ProductType;

defined( 'ABSPATH' ) || exit;

/**
 * Simple product class.
 */
class WC_Product_Simple extends WC_Product {

	/**
	 * Initialize simple product.
	 *
	 * @param WC_Product|int $product Product instance or ID.
	 */
	public function __construct( $product = 0 ) {
		$this->supports[] = 'ajax_add_to_cart';
		parent::__construct( $product );
	}

	/**
	 * Get internal type.
	 *
	 * @return string
	 */
	public function get_type() {
		return ProductType::SIMPLE;
	}

	/**
	 * Get the add to url used mainly in loops.
	 *
	 * @return string
	 */
	public function add_to_cart_url() {
		$quantity   = $this->is_sold_individually() ? 1 : $this->get_min_purchase_quantity();
		$query_args = array(
			'add-to-cart' => $this->get_id(),
			'quantity'    => $quantity > 1 ? $quantity : false,
		);

		$url = $this->is_purchasable() && $this->is_in_stock() ? remove_query_arg(
			'added-to-cart',
			add_query_arg(
				$query_args,
				( function_exists( 'is_feed' ) && is_feed() ) || ( function_exists( 'is_404' ) && is_404() ) ? $this->get_permalink() : false
			)
		) : $this->get_permalink();
		return apply_filters( 'woocommerce_product_add_to_cart_url', $url, $this );
	}

	/**
	 * Get the add to cart button text.
	 *
	 * @return string
	 */
	public function add_to_cart_text() {
		$text = $this->is_purchasable() && $this->is_in_stock() ? __( 'Add to cart', 'woocommerce' ) : __( 'Read more', 'woocommerce' );

		if ( $this->is_purchasable() && $this->is_in_stock() && ! $this->is_sold_individually() ) {
			$quantity = $this->get_min_purchase_quantity();
			if ( $quantity > 1 ) {
				/* translators: %s: Quantity of the product to add to the cart. */
				$text = sprintf( __( 'Add %s to cart', 'woocommerce' ), $quantity );
			}
		}

		return apply_filters( 'woocommerce_product_add_to_cart_text', $text, $this );
	}

	/**
	 * Get the add to cart button text description - used in aria tags.
	 *
	 * @since 3.3.0
	 * @return string
	 */
	public function add_to_cart_description() {
		/* translators: %s: Product title */
		$text = $this->is_purchasable() && $this->is_in_stock() ? __( 'Add to cart: &ldquo;%s&rdquo;', 'woocommerce' ) : __( 'Read more about &ldquo;%s&rdquo;', 'woocommerce' );
		$text = sprintf( $text, $this->get_name() );

		if ( $this->is_purchasable() && $this->is_in_stock() && ! $this->is_sold_individually() ) {
			$quantity = $this->get_min_purchase_quantity();
			if ( $quantity > 1 ) {
				/* translators: 1: Quantity of the product to add to the cart, 2: Product title. */
				$text = sprintf( __( 'Add %1$s to cart: &ldquo;%2$s&rdquo;', 'woocommerce' ), $quantity, $this->get_name() );
			}
		}

		/**
		 * Filters the add to cart button description used in accessible labels.
		 *
		 * @param string            $text The button description.
		 * @param WC_Product_Simple $product The product object.
		 * @since 3.3.0
		 */
		return apply_filters( 'woocommerce_product_add_to_cart_description', $text, $this );
	}

	/**
	 * Get the add to cart button success message - used to update the mini cart live region.
	 *
	 * @return string
	 */
	public function add_to_cart_success_message() {
		$text = '';

		if ( $this->is_purchasable() && $this->is_in_stock() ) {
			/* translators: %s: Product title */
			$text = __( '&ldquo;%s&rdquo; has been added to your cart', 'woocommerce' );
			$text = sprintf( $text, $this->get_name() );
		}

		/**
		 * Filter product add to cart success message.
		 *
		 * @since 9.2.0
		 * @param string $text The success message when a product is added to the cart.
		 * @param WC_Product_Simple $this Reference to the current WC_Product_Simple instance.
		 */
		return apply_filters( 'woocommerce_product_add_to_cart_success_message', $text, $this );
	}
}
