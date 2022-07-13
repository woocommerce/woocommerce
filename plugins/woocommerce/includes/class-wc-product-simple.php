<?php
/**
 * Simple Product Class.
 *
 * The default product type kinda product.
 *
 * @package WooCommerce\Classes\Products
 */

defined( 'ABSPATH' ) || exit;

/**
 * Simple product class.
 */
class WC_Product_Simple extends WC_Product {

	/**
	 * Track wehther post_upload_ui hook was run.
	 *
	 * @var boolean
	 */
	public static $post_upload_hook_done = false;

	/**
	 * Initialize simple product.
	 *
	 * @param WC_Product|int $product Product instance or ID.
	 */
	public function __construct( $product = 0 ) {
		$this->supports[] = 'ajax_add_to_cart';
		parent::__construct( $product );
		add_filter('admin_post_thumbnail_html', array( $this, 'add_product_photo_suggestions' ) );
	}

	/**
	 * Adding product photo suggestions in upload modal.
	 */
	public function add_product_photo_suggestions ( $content ) {
		error_log(print_r($content, true));

		$meta_content = '<p>';
		$meta_content .= esc_html__( 'For best results, upload JPEG files that are 1000 by 1000 pixels or larger. Maximum upload size: 2 GB', 'woocommerce' );
		$meta_content .= ' <a href="https://woocommerce.com/posts/fast-high-quality-product-photos/" target="_blank" rel="noopener noreferrer">' . esc_html__( 'How to prepare images?', 'woocommerce' ) . '</a>';
		$meta_content .= '</p>';

		return "$content $meta_content";
	}

	/**
	 * Get internal type.
	 *
	 * @return string
	 */
	public function get_type() {
		return 'simple';
	}

	/**
	 * Get the add to url used mainly in loops.
	 *
	 * @return string
	 */
	public function add_to_cart_url() {
		$url = $this->is_purchasable() && $this->is_in_stock() ? remove_query_arg(
			'added-to-cart',
			add_query_arg(
				array(
					'add-to-cart' => $this->get_id(),
				),
				( function_exists( 'is_feed' ) && is_feed() ) || ( function_exists( 'is_404' ) && is_404() ) ? $this->get_permalink() : ''
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
		$text = $this->is_purchasable() && $this->is_in_stock() ? __( 'Add &ldquo;%s&rdquo; to your cart', 'woocommerce' ) : __( 'Read more about &ldquo;%s&rdquo;', 'woocommerce' );

		return apply_filters( 'woocommerce_product_add_to_cart_description', sprintf( $text, $this->get_name() ), $this );
	}
}
