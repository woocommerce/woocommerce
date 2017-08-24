<?php
/**
 * Products shortcode
 *
 * @author   Automattic
 * @category Shortcodes
 * @package  WooCommerce/Shortcodes
 * @version  3.2.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Products shortcode class.
 */
class WC_Shortcode_Products {

	/**
	 * Loop name.
	 *
	 * @since 3.2.0
	 * @var   string
	 */
	protected $loop_name = 'products';

	/**
	 * Attributes.
	 *
	 * @since 3.2.0
	 * @var   array
	 */
	protected $attributes = array();

	/**
	 * Query args.
	 *
	 * @since 3.2.0
	 * @var   array
	 */
	protected $query_args = array();

	/**
	 * Initialize shortcode.
	 *
	 * @since 3.2.0
	 * @param array $attributes Shortcode attributes.
	 */
	public function __construct( $attributes ) {
		$this->attributes = shortcode_atts( array(
			'columns' => '4',
			'orderby' => 'title',
			'order'   => 'asc',
			'ids'     => '',
			'skus'    => '',
		), $attributes, $this->loop_name );

		$this->query_args = $this->parse_query_args();
	}

	/**
	 * Get query args.
	 *
	 * @since  3.2.0
	 * @return array
	 */
	public function get_query_args() {
		return $this->query_args;
	}

	/**
	 * Get shortcode attributes.
	 *
	 * @since  3.2.0
	 * @return array
	 */
	public function get_attributes() {
		return $this->attributes;
	}

	/**
	 * Get the shortcode.
	 *
	 * @since  3.2.0
	 * @return string
	 */
	public static function get_shortcode() {
		return $this->product_loop();
	}

	/**
	 * Parse query args.
	 *
	 * @since  3.2.0
	 * @return array
	 */
	protected function parse_query_args() {
		$this->query_args = array(
			'post_type'           => 'product',
			'post_status'         => 'publish',
			'ignore_sticky_posts' => 1,
			'orderby'             => $this->attributes['orderby'],
			'order'               => $this->attributes['order'],
		);

		// @codingStandardsIgnoreStart
		$this->query_args['posts_per_page'] = -1;
		$this->query_args['meta_query']     = WC()->query->get_meta_query();
		$this->query_args['tax_query']      = WC()->query->get_tax_query();
		// @codingStandardsIgnoreEnd

		if ( ! empty( $this->attributes['skus'] ) ) {
			$this->query_args['meta_query'][] = array(
				'key'     => '_sku',
				'value'   => array_map( 'trim', explode( ',', $this->attributes['skus'] ) ),
				'compare' => 'IN',
			);
		}

		if ( ! empty( $this->attributes['ids'] ) ) {
			$this->query_args['post__in'] = array_map( 'trim', explode( ',', $this->attributes['ids'] ) );
		}

		return $this->query_args;
	}

	/**
	 * Loop over found products.
	 *
	 * @since  3.2.0
	 * @return string
	 */
	protected function product_loop() {
		global $woocommerce_loop;

		$columns                     = absint( $this->attributes['columns'] );
		$woocommerce_loop['columns'] = $columns;
		$woocommerce_loop['name']    = $this->loop_name;
		$this->query_args            = apply_filters( 'woocommerce_shortcode_products_query', $this->query_args, $this->attributes, $this->loop_name );
		$transient_name              = 'wc_loop' . substr( md5( wp_json_encode( $this->query_args ) . $this->loop_name ), 28 ) . WC_Cache_Helper::get_transient_version( 'product_query' );
		$products                    = get_transient( $transient_name );

		if ( false === $products || ! is_a( $products, 'WP_Query' ) ) {
			$products = new WP_Query( $this->query_args );
			set_transient( $transient_name, $products, DAY_IN_SECONDS * 30 );
		}

		ob_start();

		if ( $products->have_posts() ) {
			// Prime caches before grabbing objects.
			update_post_caches( $products->posts, array( 'product', 'product_variation' ) );

			do_action( "woocommerce_shortcode_before_{$this->loop_name}_loop", $this->attributes );

			woocommerce_product_loop_start();

			while ( $products->have_posts() ) {
				$products->the_post();
				wc_get_template_part( 'content', 'product' );
			}

			woocommerce_product_loop_end();

			do_action( "woocommerce_shortcode_after_{$this->loop_name}_loop", $this->attributes );
		} else {
			do_action( "woocommerce_shortcode_{$this->loop_name}_loop_no_results", $this->attributes );
		}

		woocommerce_reset_loop();
		wp_reset_postdata();

		return '<div class="woocommerce columns-' . $columns . '">' . ob_get_clean() . '</div>';
	}
}
