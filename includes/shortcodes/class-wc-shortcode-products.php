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
	 * @param array $loop_name  Loop name.
	 */
	public function __construct( $attributes = array(), $loop_name = 'products' ) {
		$this->loop_name  = $loop_name;
		$this->attributes = $this->parse_attributes( $attributes );
		$this->query_args = $this->parse_query_args();
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
	 * Get query args.
	 *
	 * @since  3.2.0
	 * @return array
	 */
	public function get_query_args() {
		return $this->query_args;
	}

	/**
	 * Get loop name.
	 *
	 * @since  3.2.0
	 * @return array
	 */
	public function get_loop_name() {
		return $this->loop_name;
	}

	/**
	 * Get shortcode content.
	 *
	 * @since  3.2.0
	 * @return string
	 */
	public function get_content() {
		return $this->product_loop();
	}

	/**
	 * Parse attributes.
	 *
	 * @param  array $attributes Shortcode attributes.
	 * @return array
	 */
	protected function parse_attributes( $attributes ) {
		return shortcode_atts( array(
			'per_page' => '-1',
			'columns'  => '4',
			'orderby'  => 'title',
			'order'    => 'ASC',
			'ids'      => '',
			'skus'     => '',
			'category' => '',   // Slugs.
			'operator' => 'IN', // Category operator. Possible values are 'IN', 'NOT IN', 'AND'.
		), $attributes, $this->loop_name );
	}

	/**
	 * Parse query args.
	 *
	 * @since  3.2.0
	 * @return array
	 */
	protected function parse_query_args() {
		$query_args = array(
			'post_type'           => 'product',
			'post_status'         => 'publish',
			'ignore_sticky_posts' => 1,
			'orderby'             => $this->attributes['orderby'],
			'order'               => strtoupper( $this->attributes['order'] ),
		);

		// @codingStandardsIgnoreStart
		$query_args['posts_per_page'] = (int) $this->attributes['per_page'];
		$query_args['meta_query']     = WC()->query->get_meta_query();
		$query_args['tax_query']      = WC()->query->get_tax_query();
		// @codingStandardsIgnoreEnd

		// Categories.
		if ( ! empty( $this->attributes['category'] ) ) {
			$ordering_args = WC()->query->get_catalog_ordering_args( $query_args['orderby'], $query_args['order'] );
			$query_args['orderby'] = $ordering_args['orderby'];
			$query_args['order']   = $ordering_args['order'];

			if ( isset( $ordering_args['meta_key'] ) ) {
				$query_args['meta_key'] = $ordering_args['meta_key'];
			}

			$query_args['tax_query'][] = array(
				'taxonomy' => 'product_cat',
				'terms'    => array_map( 'sanitize_title', explode( ',', $this->attributes['category'] ) ),
				'field'    => 'slug',
				'operator' => $this->attributes['operator'],
			);
		}

		// SKUs.
		if ( ! empty( $this->attributes['skus'] ) ) {
			$query_args['meta_query'][] = array(
				'key'     => '_sku',
				'value'   => array_map( 'trim', explode( ',', $this->attributes['skus'] ) ),
				'compare' => 'IN',
			);
		}

		// IDs.
		if ( ! empty( $this->attributes['ids'] ) ) {
			$query_args['post__in'] = array_map( 'trim', explode( ',', $this->attributes['ids'] ) );
		}

		return apply_filters( 'woocommerce_shortcode_products_query', $query_args, $this->attributes, $this->loop_name );
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
		$transient_name              = 'wc_loop' . substr( md5( wp_json_encode( $this->query_args ) . $this->loop_name ), 28 ) . WC_Cache_Helper::get_transient_version( 'product_query' );
		$products                    = get_transient( $transient_name );

		if ( false === $products || ! is_a( $products, 'WP_Query' ) ) {
			$products = new WP_Query( $this->query_args );
			set_transient( $transient_name, $products, DAY_IN_SECONDS * 30 );
		}

		// Remove ordering query arguments.
		if ( ! empty( $this->attributes['category'] ) ) {
			WC()->query->remove_ordering_args();
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
