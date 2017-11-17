<?php
/**
 * Products shortcode
 *
 * @author   Automattic
 * @category Shortcodes
 * @package  WooCommerce/Shortcodes
 * @version  3.2.4
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Products shortcode class.
 */
class WC_Shortcode_Products {

	/**
	 * Shortcode type.
	 *
	 * @since 3.2.0
	 * @var   string
	 */
	protected $type = 'products';

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
	 * Set custom visibility.
	 *
	 * @since 3.2.0
	 * @var   bool
	 */
	protected $custom_visibility = false;

	/**
	 * Initialize shortcode.
	 *
	 * @since 3.2.0
	 * @param array  $attributes Shortcode attributes.
	 * @param string $type       Shortcode type.
	 */
	public function __construct( $attributes = array(), $type = 'products' ) {
		$this->type       = $type;
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
	 * Get shortcode type.
	 *
	 * @since  3.2.0
	 * @return array
	 */
	public function get_type() {
		return $this->type;
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
	 * @since  3.2.0
	 * @param  array $attributes Shortcode attributes.
	 * @return array
	 */
	protected function parse_attributes( $attributes ) {
		$attributes = $this->parse_legacy_attributes( $attributes );

		return shortcode_atts( array(
			'limit'          => '-1',      // Results limit.
			'columns'        => '4',       // Number of columns.
			'orderby'        => 'title',   // menu_order, title, date, rand, price, popularity, rating, or id.
			'order'          => 'ASC',     // ASC or DESC.
			'ids'            => '',        // Comma separated IDs.
			'skus'           => '',        // Comma separated SKUs.
			'category'       => '',        // Comma separated category slugs.
			'cat_operator'   => 'IN',      // Operator to compare categories. Possible values are 'IN', 'NOT IN', 'AND'.
			'attribute'      => '',        // Single attribute slug.
			'terms'          => '',        // Comma separated term slugs.
			'terms_operator' => 'IN',      // Operator to compare terms. Possible values are 'IN', 'NOT IN', 'AND'.
			'visibility'     => 'visible', // Possible values are 'visible', 'catalog', 'search', 'hidden'.
			'class'          => '',        // HTML class.
		), $attributes, $this->type );
	}

	/**
	 * Parse legacy attributes.
	 *
	 * @since  3.2.0
	 * @param  array $attributes Attributes.
	 * @return array
	 */
	protected function parse_legacy_attributes( $attributes ) {
		$mapping = array(
			'per_page' => 'limit',
			'operator' => 'cat_operator',
			'filter'   => 'terms',
		);

		foreach ( $mapping as $old => $new ) {
			if ( isset( $attributes[ $old ] ) ) {
				$attributes[ $new ] = $attributes[ $old ];
				unset( $attributes[ $old ] );
			}
		}

		return $attributes;
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
			'ignore_sticky_posts' => true,
			'no_found_rows'       => true,
			'orderby'             => $this->attributes['orderby'],
			'order'               => strtoupper( $this->attributes['order'] ),
		);

		// @codingStandardsIgnoreStart
		$query_args['posts_per_page'] = (int) $this->attributes['limit'];
		$query_args['meta_query']     = WC()->query->get_meta_query();
		$query_args['tax_query']      = array();
		// @codingStandardsIgnoreEnd

		// Visibility.
		$this->set_visibility_query_args( $query_args );

		// SKUs.
		$this->set_skus_query_args( $query_args );

		// IDs.
		$this->set_ids_query_args( $query_args );

		// Set specific types query args.
		if ( method_exists( $this, "set_{$this->type}_query_args" ) ) {
			$this->{"set_{$this->type}_query_args"}( $query_args );
		}

		// Attributes.
		$this->set_attributes_query_args( $query_args );

		// Categories.
		$this->set_categories_query_args( $query_args );

		$query_args = apply_filters( 'woocommerce_shortcode_products_query', $query_args, $this->attributes, $this->type );

		// Always query only IDs.
		$query_args['fields'] = 'ids';

		return $query_args;
	}

	/**
	 * Set skus query args.
	 *
	 * @since 3.2.0
	 * @param array $query_args Query args.
	 */
	protected function set_skus_query_args( &$query_args ) {
		if ( ! empty( $this->attributes['skus'] ) ) {
			$skus = array_map( 'trim', explode( ',', $this->attributes['skus'] ) );
			$query_args['meta_query'][] = array(
				'key'     => '_sku',
				'value'   => 1 === count( $skus ) ? $skus[0] : $skus,
				'compare' => 1 === count( $skus ) ? '=' : 'IN',
			);
		}
	}

	/**
	 * Set ids query args.
	 *
	 * @since 3.2.0
	 * @param array $query_args Query args.
	 */
	protected function set_ids_query_args( &$query_args ) {
		if ( ! empty( $this->attributes['ids'] ) ) {
			$ids = array_map( 'trim', explode( ',', $this->attributes['ids'] ) );

			if ( 1 === count( $ids ) ) {
				$query_args['p'] = $ids[0];
			} else {
				$query_args['post__in'] = $ids;
			}
		}
	}

	/**
	 * Set attributes query args.
	 *
	 * @since 3.2.0
	 * @param array $query_args Query args.
	 */
	protected function set_attributes_query_args( &$query_args ) {
		if ( ! empty( $this->attributes['attribute'] ) || ! empty( $this->attributes['terms'] ) ) {
			$query_args['tax_query'][] = array(
				'taxonomy' => strstr( $this->attributes['attribute'], 'pa_' ) ? sanitize_title( $this->attributes['attribute'] ) : 'pa_' . sanitize_title( $this->attributes['attribute'] ),
				'terms'    => array_map( 'sanitize_title', explode( ',', $this->attributes['terms'] ) ),
				'field'    => 'slug',
				'operator' => $this->attributes['terms_operator'],
			);
		}
	}

	/**
	 * Set categories query args.
	 *
	 * @since 3.2.0
	 * @param array $query_args Query args.
	 */
	protected function set_categories_query_args( &$query_args ) {
		if ( ! empty( $this->attributes['category'] ) ) {
			$ordering_args = WC()->query->get_catalog_ordering_args( $query_args['orderby'], $query_args['order'] );
			$query_args['orderby']  = $ordering_args['orderby'];
			$query_args['order']    = $ordering_args['order'];
 			// @codingStandardsIgnoreStart
			$query_args['meta_key'] = $ordering_args['meta_key'];
			// @codingStandardsIgnoreEnd

			$query_args['tax_query'][] = array(
				'taxonomy' => 'product_cat',
				'terms'    => array_map( 'sanitize_title', explode( ',', $this->attributes['category'] ) ),
				'field'    => 'slug',
				'operator' => $this->attributes['cat_operator'],
			);
		}
	}

	/**
	 * Set sale products query args.
	 *
	 * @since 3.2.0
	 * @param array $query_args Query args.
	 */
	protected function set_sale_products_query_args( &$query_args ) {
		$query_args['post__in'] = array_merge( array( 0 ), wc_get_product_ids_on_sale() );
	}

	/**
	 * Set best selling products query args.
	 *
	 * @since 3.2.0
	 * @param array $query_args Query args.
	 */
	protected function set_best_selling_products_query_args( &$query_args ) {
		// @codingStandardsIgnoreStart
		$query_args['meta_key'] = 'total_sales';
		// @codingStandardsIgnoreEnd
		$query_args['order']    = 'DESC';
		$query_args['orderby']  = 'meta_value_num';
	}

	/**
	 * Set visibility as hidden.
	 *
	 * @since 3.2.0
	 * @param array $query_args Query args.
	 */
	protected function set_visibility_hidden_query_args( &$query_args ) {
		$this->custom_visibility = true;
		$query_args['tax_query'][] = array(
			'taxonomy'         => 'product_visibility',
			'terms'            => array( 'exclude-from-catalog', 'exclude-from-search' ),
			'field'            => 'name',
			'operator'         => 'AND',
			'include_children' => false,
		);
	}

	/**
	 * Set visibility as catalog.
	 *
	 * @since 3.2.0
	 * @param array $query_args Query args.
	 */
	protected function set_visibility_catalog_query_args( &$query_args ) {
		$this->custom_visibility = true;
		$query_args['tax_query'][] = array(
			'taxonomy'         => 'product_visibility',
			'terms'            => 'exclude-from-search',
			'field'            => 'name',
			'operator'         => 'IN',
			'include_children' => false,
		);
		$query_args['tax_query'][] = array(
			'taxonomy'         => 'product_visibility',
			'terms'            => 'exclude-from-catalog',
			'field'            => 'name',
			'operator'         => 'NOT IN',
			'include_children' => false,
		);
	}

	/**
	 * Set visibility as search.
	 *
	 * @since 3.2.0
	 * @param array $query_args Query args.
	 */
	protected function set_visibility_search_query_args( &$query_args ) {
		$this->custom_visibility = true;
		$query_args['tax_query'][] = array(
			'taxonomy'         => 'product_visibility',
			'terms'            => 'exclude-from-catalog',
			'field'            => 'name',
			'operator'         => 'IN',
			'include_children' => false,
		);
		$query_args['tax_query'][] = array(
			'taxonomy'         => 'product_visibility',
			'terms'            => 'exclude-from-search',
			'field'            => 'name',
			'operator'         => 'NOT IN',
			'include_children' => false,
		);
	}

	/**
	 * Set visibility as featured.
	 *
	 * @since 3.2.0
	 * @param array $query_args Query args.
	 */
	protected function set_visibility_featured_query_args( &$query_args ) {
		// @codingStandardsIgnoreStart
		$query_args['tax_query'] = array_merge( $query_args['tax_query'], WC()->query->get_tax_query() );
		// @codingStandardsIgnoreEnd

		$query_args['tax_query'][] = array(
			'taxonomy'         => 'product_visibility',
			'terms'            => 'featured',
			'field'            => 'name',
			'operator'         => 'IN',
			'include_children' => false,
		);
	}

	/**
	 * Set visibility query args.
	 *
	 * @since 3.2.0
	 * @param array $query_args Query args.
	 */
	protected function set_visibility_query_args( &$query_args ) {
		if ( method_exists( $this, 'set_visibility_' . $this->attributes['visibility'] . '_query_args' ) ) {
			$this->{'set_visibility_' . $this->attributes['visibility'] . '_query_args'}( $query_args );
		} else {
			// @codingStandardsIgnoreStart
			$query_args['tax_query'] = array_merge( $query_args['tax_query'], WC()->query->get_tax_query() );
			// @codingStandardsIgnoreEnd
		}
	}

	/**
	 * Set product as visible when quering for hidden products.
	 *
	 * @since  3.2.0
	 * @param  bool $visibility Product visibility.
	 * @return bool
	 */
	public function set_product_as_visible( $visibility ) {
		return $this->custom_visibility ? true : $visibility;
	}

	/**
	 * Get wrapper classes.
	 *
	 * @since  3.2.0
	 * @param  array $columns Number of columns.
	 * @return array
	 */
	protected function get_wrapper_classes( $columns ) {
		$classes = array( 'woocommerce' );

		if ( 'product' !== $this->type ) {
			$classes[] = 'columns-' . $columns;
		}

		$classes[] = $this->attributes['class'];

		return $classes;
	}

	/**
	 * Get products.
	 *
	 * @since  3.2.4
	 * @return array
	 */
	protected function get_products_ids() {
		$transient_name = 'wc_loop' . substr( md5( wp_json_encode( $this->query_args ) . $this->type ), 28 ) . WC_Cache_Helper::get_transient_version( 'product_query' );
		$ids            = get_transient( $transient_name );

		if ( false === $ids ) {
			if ( 'top_rated_products' === $this->type ) {
				add_filter( 'posts_clauses', array( __CLASS__, 'order_by_rating_post_clauses' ) );
				$products = new WP_Query( $this->query_args );
				remove_filter( 'posts_clauses', array( __CLASS__, 'order_by_rating_post_clauses' ) );
			} else {
				$products = new WP_Query( $this->query_args );
			}

			$ids = wp_parse_id_list( $products->posts );

			set_transient( $transient_name, $ids, DAY_IN_SECONDS * 30 );
		}

		// Remove ordering query arguments.
		if ( ! empty( $this->attributes['category'] ) ) {
			WC()->query->remove_ordering_args();
		}

		return $ids;
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
		$classes                     = $this->get_wrapper_classes( $columns );
		$woocommerce_loop['columns'] = $columns;
		$woocommerce_loop['name']    = $this->type;
		$products_ids                = $this->get_products_ids();

		ob_start();

		if ( $products_ids ) {
			// Prime meta cache to reduce future queries.
			update_meta_cache( 'post', $products_ids );
			update_object_term_cache( $products_ids, 'product' );

			$original_post = $GLOBALS['post'];

			do_action( "woocommerce_shortcode_before_{$this->type}_loop", $this->attributes );

			woocommerce_product_loop_start();

			foreach ( $products_ids as $product_id ) {
				$post_object = get_post( $product_id );
				$GLOBALS['post'] =& $post_object; // WPCS: override ok.
				setup_postdata( $post_object );

				// Set custom product visibility when quering hidden products.
				add_action( 'woocommerce_product_is_visible', array( $this, 'set_product_as_visible' ) );

				// Render product template.
				wc_get_template_part( 'content', 'product' );

				// Restore product visibility.
				remove_action( 'woocommerce_product_is_visible', array( $this, 'set_product_as_visible' ) );
			}

			$GLOBALS['post'] = $original_post; // WPCS: override ok.
			woocommerce_product_loop_end();

			do_action( "woocommerce_shortcode_after_{$this->type}_loop", $this->attributes );

			wp_reset_postdata();
		} else {
			do_action( "woocommerce_shortcode_{$this->type}_loop_no_results", $this->attributes );
		}

		woocommerce_reset_loop();

		return '<div class="' . esc_attr( implode( ' ', $classes ) ) . '">' . ob_get_clean() . '</div>';
	}

	/**
	 * Order by rating.
	 *
	 * @since  3.2.0
	 * @param  array $args Query args.
	 * @return array
	 */
	public static function order_by_rating_post_clauses( $args ) {
		global $wpdb;

		$args['where']   .= " AND $wpdb->commentmeta.meta_key = 'rating' ";
		$args['join']    .= "LEFT JOIN $wpdb->comments ON($wpdb->posts.ID = $wpdb->comments.comment_post_ID) LEFT JOIN $wpdb->commentmeta ON($wpdb->comments.comment_ID = $wpdb->commentmeta.comment_id)";
		$args['orderby'] = "$wpdb->commentmeta.meta_value DESC";
		$args['groupby'] = "$wpdb->posts.ID";

		return $args;
	}
}
