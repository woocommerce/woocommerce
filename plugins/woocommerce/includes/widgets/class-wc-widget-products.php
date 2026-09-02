<?php
/**
 * List products. One widget to rule them all.
 *
 * @package WooCommerce\Widgets
 * @version 3.3.0
 */

defined( 'ABSPATH' ) || exit;

use Automattic\WooCommerce\Enums\ProductStockStatus;

/**
 * Widget products.
 */
class WC_Widget_Products extends WC_Widget {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->widget_cssclass    = 'woocommerce widget_products';
		$this->widget_description = __( "A list of your store's products.", 'woocommerce' );
		$this->widget_id          = 'woocommerce_products';
		$this->widget_name        = __( 'Products list', 'woocommerce' );
		$this->settings           = array(
			'title'       => array(
				'type'  => 'text',
				'std'   => __( 'Products', 'woocommerce' ),
				'label' => __( 'Title', 'woocommerce' ),
			),
			'number'      => array(
				'type'  => 'number',
				'step'  => 1,
				'min'   => 1,
				'max'   => '',
				'std'   => 5,
				'label' => __( 'Number of products to show', 'woocommerce' ),
			),
			'show'        => array(
				'type'    => 'select',
				'std'     => '',
				'label'   => __( 'Show', 'woocommerce' ),
				'options' => array(
					''         => __( 'All products', 'woocommerce' ),
					'featured' => __( 'Featured products', 'woocommerce' ),
					'onsale'   => __( 'On-sale products', 'woocommerce' ),
				),
			),
			'orderby'     => array(
				'type'    => 'select',
				'std'     => 'date',
				'label'   => __( 'Order by', 'woocommerce' ),
				'options' => array(
					'menu_order' => __( 'Menu order', 'woocommerce' ),
					'date'       => __( 'Date', 'woocommerce' ),
					'price'      => __( 'Price', 'woocommerce' ),
					'rand'       => __( 'Random', 'woocommerce' ),
					'sales'      => __( 'Sales', 'woocommerce' ),
				),
			),
			'order'       => array(
				'type'    => 'select',
				'std'     => 'desc',
				'label'   => _x( 'Order', 'Sorting order', 'woocommerce' ),
				'options' => array(
					'asc'  => __( 'ASC', 'woocommerce' ),
					'desc' => __( 'DESC', 'woocommerce' ),
				),
			),
			'hide_free'   => array(
				'type'  => 'checkbox',
				'std'   => 0,
				'label' => __( 'Hide free products', 'woocommerce' ),
			),
			'show_hidden' => array(
				'type'  => 'checkbox',
				'std'   => 0,
				'label' => __( 'Show hidden products', 'woocommerce' ),
			),
		);

		parent::__construct();
	}

	/**
	 * Query the products and return them.
	 *
	 * @param array $args     Arguments.
	 * @param array $instance Widget instance.
	 *
	 * @return WP_Query
	 */
	public function get_products( $args, $instance ) {
		$number                      = ! empty( $instance['number'] ) ? absint( $instance['number'] ) : $this->settings['number']['std'];
		$show                        = ! empty( $instance['show'] ) ? sanitize_title( $instance['show'] ) : $this->settings['show']['std'];
		$orderby                     = ! empty( $instance['orderby'] ) ? sanitize_title( $instance['orderby'] ) : $this->settings['orderby']['std'];
		$order                       = ! empty( $instance['order'] ) ? sanitize_title( $instance['order'] ) : $this->settings['order']['std'];
		$product_visibility_term_ids = wc_get_product_visibility_term_ids();

		$query_args = array(
			'posts_per_page' => $number,
			'post_status'    => 'publish',
			'post_type'      => 'product',
			'no_found_rows'  => 1,
			'order'          => $order,
			'meta_query'     => array(), // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- The empty query container does not add a database join.
			'tax_query'      => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query -- The empty query container does not add a database join.
				'relation' => 'AND',
			),
		);

		if ( empty( $instance['show_hidden'] ) ) {
			$query_args['tax_query'][] = array(
				'taxonomy' => 'product_visibility',
				'field'    => 'term_taxonomy_id',
				'terms'    => is_search() ? $product_visibility_term_ids['exclude-from-search'] : $product_visibility_term_ids['exclude-from-catalog'],
				'operator' => 'NOT IN',
			);
			$query_args['post_parent'] = 0;
		}

		if ( ! empty( $instance['hide_free'] ) ) {
			$query_args['meta_query'][] = array(
				'key'     => '_price',
				'value'   => 0,
				'compare' => '>',
				'type'    => 'DECIMAL',
			);
		}

		if ( 'yes' === get_option( 'woocommerce_hide_out_of_stock_items' ) ) {
			$query_args['tax_query'][] = array(
				array(
					'taxonomy' => 'product_visibility',
					'field'    => 'term_taxonomy_id',
					'terms'    => $product_visibility_term_ids[ ProductStockStatus::OUT_OF_STOCK ],
					'operator' => 'NOT IN',
				),
			); // WPCS: slow query ok.
		}

		switch ( $show ) {
			case 'featured':
				$query_args['tax_query'][] = array(
					'taxonomy' => 'product_visibility',
					'field'    => 'term_taxonomy_id',
					'terms'    => $product_visibility_term_ids['featured'],
				);
				break;
			case 'onsale':
				$product_ids_on_sale    = wc_get_product_ids_on_sale();
				$product_ids_on_sale[]  = 0;
				$query_args['post__in'] = $product_ids_on_sale;
				break;
		}

		switch ( $orderby ) {
			case 'menu_order':
				$query_args['orderby'] = 'menu_order';
				break;
			case 'price':
				// Kept on post meta: the join also drops products that have no price at all, and
				// wc_product_meta_lookup stores 0 for those so it cannot reproduce that filter.
				$query_args['meta_key'] = '_price'; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- Ordering by the _price meta is what keeps priceless products out of the list; adding the lookup table join on top of it measured slower than the meta join alone.
				$query_args['orderby']  = 'meta_value_num';
				break;
			case 'rand':
				$query_args['orderby'] = 'rand';
				break;
			case 'sales':
				// Left in the args so that the query args filter below keeps seeing the documented
				// payload; the query itself is switched to wc_product_meta_lookup afterwards.
				$query_args['meta_key'] = 'total_sales'; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- Part of the filter payload only; query_products() orders through wc_product_meta_lookup instead of joining post meta.
				$query_args['orderby']  = 'meta_value_num';
				break;
			default:
				$query_args['orderby'] = 'date';
		}

		/**
		 * Filters the query arguments the Products widget uses to fetch its products.
		 *
		 * @since 2.4.0
		 * @param array $query_args Arguments passed to WP_Query.
		 */
		$query_args = apply_filters( 'woocommerce_products_widget_query_args', $query_args );

		return $this->query_products( $query_args );
	}

	/**
	 * Run the widget query, ordering through the product lookup table where that is equivalent to, and
	 * cheaper than, ordering through post meta.
	 *
	 * Only the sales ordering is redirected. `total_sales` post meta is written for every product, so the
	 * meta join it replaces filters nothing out, while `wc_product_meta_lookup` holds one narrow row per
	 * product instead of one row per product in a table that grows with every extension's meta. Price
	 * ordering deliberately stays on post meta; see the `price` case in get_products().
	 *
	 * The swap happens after `woocommerce_products_widget_query_args` has run, and only when nothing on
	 * that filter changed the ordering arguments, so the filter payload keeps its documented shape and a
	 * consumer that overrides the ordering still wins.
	 *
	 * @param array $query_args Query arguments, as returned by the widget query args filter.
	 *
	 * @return WP_Query
	 */
	private function query_products( $query_args ) {
		$orders_by_total_sales = isset( $query_args['meta_key'], $query_args['orderby'] )
			&& 'total_sales' === $query_args['meta_key']
			&& 'meta_value_num' === $query_args['orderby'];

		if ( ! $orders_by_total_sales ) {
			return new WP_Query( $query_args );
		}

		$order = isset( $query_args['order'] ) && 'asc' === strtolower( $query_args['order'] ) ? 'ASC' : 'DESC';

		unset( $query_args['meta_key'] );
		$query_args['orderby'] = 'none';

		$products = new WP_Query();

		$order_by_total_sales = static function ( $clauses, $query ) use ( $products, $order ) {
			global $wpdb;

			if ( $query !== $products ) {
				return $clauses;
			}

			if ( ! strstr( $clauses['join'], 'wc_product_meta_lookup' ) ) {
				$clauses['join'] .= " LEFT JOIN {$wpdb->wc_product_meta_lookup} wc_product_meta_lookup ON {$wpdb->posts}.ID = wc_product_meta_lookup.product_id ";
			}

			$clauses['orderby'] = " wc_product_meta_lookup.total_sales {$order}, wc_product_meta_lookup.product_id {$order} ";

			return $clauses;
		};

		add_filter( 'posts_clauses', $order_by_total_sales, 10, 2 );
		$products->query( $query_args );
		remove_filter( 'posts_clauses', $order_by_total_sales, 10 );

		return $products;
	}

	/**
	 * Output widget.
	 *
	 * @param array $args     Arguments.
	 * @param array $instance Widget instance.
	 *
	 * @see WP_Widget
	 */
	public function widget( $args, $instance ) {
		if ( $this->get_cached_widget( $args ) ) {
			return;
		}

		ob_start();

		wc_set_loop_prop( 'name', 'widget' );

		$products = $this->get_products( $args, $instance );
		if ( $products && $products->have_posts() ) {
			$this->widget_start( $args, $instance );

			echo wp_kses_post( apply_filters( 'woocommerce_before_widget_product_list', '<ul class="product_list_widget">' ) );

			$template_args = array(
				'widget_id'   => isset( $args['widget_id'] ) ? $args['widget_id'] : $this->widget_id,
				'show_rating' => true,
			);

			while ( $products->have_posts() ) {
				$products->the_post();
				wc_get_template( 'content-widget-product.php', $template_args );
			}

			echo wp_kses_post( apply_filters( 'woocommerce_after_widget_product_list', '</ul>' ) );

			$this->widget_end( $args );
		}

		wp_reset_postdata();

		echo $this->cache_widget( $args, ob_get_clean() ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Buffered widget markup; each dynamic value is escaped or annotated where it is rendered.
	}
}
