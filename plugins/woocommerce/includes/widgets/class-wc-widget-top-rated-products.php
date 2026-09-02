<?php
/**
 * Top Rated Products Widget.
 * Gets and displays top rated products in an unordered list.
 *
 * @package WooCommerce\Widgets
 * @version 3.3.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Widget top rated products class.
 */
class WC_Widget_Top_Rated_Products extends WC_Widget {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->widget_cssclass    = 'woocommerce widget_top_rated_products';
		$this->widget_description = __( "A list of your store's top-rated products.", 'woocommerce' );
		$this->widget_id          = 'woocommerce_top_rated_products';
		$this->widget_name        = __( 'Products by Rating list', 'woocommerce' );
		$this->settings           = array(
			'title'  => array(
				'type'  => 'text',
				'std'   => __( 'Top rated products', 'woocommerce' ),
				'label' => __( 'Title', 'woocommerce' ),
			),
			'number' => array(
				'type'  => 'number',
				'step'  => 1,
				'min'   => 1,
				'max'   => '',
				'std'   => 5,
				'label' => __( 'Number of products to show', 'woocommerce' ),
			),
		);

		parent::__construct();
	}

	/**
	 * Output widget.
	 *
	 * @see WP_Widget
	 * @param array $args     Arguments.
	 * @param array $instance Widget instance.
	 */
	public function widget( $args, $instance ) {

		if ( $this->get_cached_widget( $args ) ) {
			return;
		}

		ob_start();

		$number = ! empty( $instance['number'] ) ? absint( $instance['number'] ) : $this->settings['number']['std'];

		$query_args = apply_filters(
			'woocommerce_top_rated_products_widget_args',
			array(
				'posts_per_page' => $number,
				'no_found_rows'  => 1,
				'post_status'    => 'publish',
				'post_type'      => 'product',
				// Left in the args so that the filter above keeps seeing the documented payload; the
				// query itself is switched to wc_product_meta_lookup in query_top_rated_products().
				'meta_key'       => '_wc_average_rating', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- Part of the filter payload only; query_top_rated_products() orders through wc_product_meta_lookup instead of joining post meta.
				'orderby'        => 'meta_value_num',
				'order'          => 'DESC',
				'meta_query'     => WC()->query->get_meta_query(), // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- Empty unless an extension adds clauses through woocommerce_product_query_meta_query; the same container the shop catalog query uses.
				'tax_query'      => WC()->query->get_tax_query(), // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query -- Product visibility exclusions are indexed by term_taxonomy_id and are the only way to hide catalog-excluded products.
			)
		);

		$r = $this->query_top_rated_products( $query_args );

		if ( $r->have_posts() ) {

			$this->widget_start( $args, $instance );

			echo wp_kses_post( apply_filters( 'woocommerce_before_widget_product_list', '<ul class="product_list_widget">' ) );

			$template_args = array(
				'widget_id'   => isset( $args['widget_id'] ) ? $args['widget_id'] : $this->widget_id,
				'show_rating' => true,
			);

			while ( $r->have_posts() ) {
				$r->the_post();
				wc_get_template( 'content-widget-product.php', $template_args );
			}

			echo wp_kses_post( apply_filters( 'woocommerce_after_widget_product_list', '</ul>' ) );

			$this->widget_end( $args );
		}

		wp_reset_postdata();

		$content = ob_get_clean();

		echo $content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Buffered widget markup; each dynamic value is escaped or annotated where it is rendered.

		$this->cache_widget( $args, $content );
	}

	/**
	 * Run the widget query, ordering through the product lookup table instead of the rating post meta.
	 *
	 * `_wc_average_rating` post meta is written for every product, so the meta join this replaces filters
	 * nothing out, while `wc_product_meta_lookup` holds one narrow row per product instead of one row per
	 * product in a table that grows with every extension's meta. Ordering is delegated to the same clause
	 * callback the shop catalog uses for "sort by average rating", which also breaks ties on rating count
	 * and product ID rather than leaving equally rated products in an undefined order.
	 *
	 * The swap happens after `woocommerce_top_rated_products_widget_args` has run, and only when nothing on
	 * that filter changed the ordering arguments, so the filter payload keeps its documented shape and a
	 * consumer that overrides the ordering still wins.
	 *
	 * @param array $query_args Query arguments, as returned by the widget args filter.
	 *
	 * @return WP_Query
	 */
	private function query_top_rated_products( $query_args ) {
		$orders_by_average_rating = isset( $query_args['meta_key'], $query_args['orderby'], $query_args['order'] )
			&& '_wc_average_rating' === $query_args['meta_key']
			&& 'meta_value_num' === $query_args['orderby']
			&& 'DESC' === strtoupper( $query_args['order'] );

		if ( ! $orders_by_average_rating ) {
			return new WP_Query( $query_args );
		}

		unset( $query_args['meta_key'] );
		$query_args['orderby'] = 'none';

		$products = new WP_Query();

		$order_by_rating = static function ( $clauses, $query ) use ( $products ) {
			return $query === $products ? WC()->query->order_by_rating_post_clauses( $clauses ) : $clauses;
		};

		add_filter( 'posts_clauses', $order_by_rating, 10, 2 );
		$products->query( $query_args );
		remove_filter( 'posts_clauses', $order_by_rating, 10 );

		return $products;
	}
}
