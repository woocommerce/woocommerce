<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * WC_Shortcodes class
 *
 * @class       WC_Shortcodes
 * @version     2.1.0
 * @package     WooCommerce/Classes
 * @category    Class
 * @author      WooThemes
 */
class WC_Shortcodes {

	/**
	 * Init shortcodes.
	 */
	public static function init() {
		$shortcodes = array(
			'product'                    => __CLASS__ . '::product',
			'product_page'               => __CLASS__ . '::product_page',
			'product_category'           => __CLASS__ . '::product_category',
			'product_categories'         => __CLASS__ . '::product_categories',
			'add_to_cart'                => __CLASS__ . '::product_add_to_cart',
			'add_to_cart_url'            => __CLASS__ . '::product_add_to_cart_url',
			'products'                   => __CLASS__ . '::products',
			'recent_products'            => __CLASS__ . '::recent_products',
			'sale_products'              => __CLASS__ . '::sale_products',
			'best_selling_products'      => __CLASS__ . '::best_selling_products',
			'top_rated_products'         => __CLASS__ . '::top_rated_products',
			'featured_products'          => __CLASS__ . '::featured_products',
			'product_attribute'          => __CLASS__ . '::product_attribute',
			'related_products'           => __CLASS__ . '::related_products',
			'shop_messages'              => __CLASS__ . '::shop_messages',
			'woocommerce_order_tracking' => __CLASS__ . '::order_tracking',
			'woocommerce_cart'           => __CLASS__ . '::cart',
			'woocommerce_checkout'       => __CLASS__ . '::checkout',
			'woocommerce_my_account'     => __CLASS__ . '::my_account',
		);

		foreach ( $shortcodes as $shortcode => $function ) {
			add_shortcode( apply_filters( "{$shortcode}_shortcode_tag", $shortcode ), $function );
		}

		// Alias for pre 2.1 compatibility
		add_shortcode( 'woocommerce_messages', __CLASS__ . '::shop_messages' );
	}

	/**
	 * Shortcode Wrapper.
	 *
	 * @param string[] $function
	 * @param array $atts (default: array())
	 * @return string
	 */
	public static function shortcode_wrapper(
		$function,
		$atts    = array(),
		$wrapper = array(
			'class'  => 'woocommerce',
			'before' => null,
			'after'  => null
		)
	) {
		ob_start();

		echo empty( $wrapper['before'] ) ? '<div class="' . esc_attr( $wrapper['class'] ) . '">' : $wrapper['before'];
		call_user_func( $function, $atts );
		echo empty( $wrapper['after'] ) ? '</div>' : $wrapper['after'];

		return ob_get_clean();
	}

	/**
	 * Loop over found products.
	 * @param  array $query_args
	 * @param  array $atts
	 * @param  string $loop_name
	 * @return string
	 */
	private static function product_loop( $query_args, $atts, $loop_name ) {
		global $woocommerce_loop;

		$products                    = new WP_Query( apply_filters( 'woocommerce_shortcode_products_query', $query_args, $atts, $loop_name ) );
		$columns                     = absint( $atts['columns'] );
		$woocommerce_loop['columns'] = $columns;
		$woocommerce_loop['name']    = $loop_name;

		ob_start();

		if ( $products->have_posts() ) {
			?>

			<?php do_action( "woocommerce_shortcode_before_{$loop_name}_loop" ); ?>

			<?php woocommerce_product_loop_start(); ?>

				<?php while ( $products->have_posts() ) : $products->the_post(); ?>

					<?php wc_get_template_part( 'content', 'product' ); ?>

				<?php endwhile; // end of the loop. ?>

			<?php woocommerce_product_loop_end(); ?>

			<?php do_action( "woocommerce_shortcode_after_{$loop_name}_loop" ); ?>

			<?php
		} else {
			do_action( "woocommerce_shortcode_{$loop_name}_loop_no_results" );
		}

		woocommerce_reset_loop();
		wp_reset_postdata();

		return '<div class="woocommerce columns-' . $columns . '">' . ob_get_clean() . '</div>';
	}

	/**
	 * Cart page shortcode.
	 *
	 * @return string
	 */
	public static function cart() {
		return is_null( WC()->cart ) ? '' : self::shortcode_wrapper( array( 'WC_Shortcode_Cart', 'output' ) );
	}

	/**
	 * Checkout page shortcode.
	 *
	 * @param mixed $atts
	 * @return string
	 */
	public static function checkout( $atts ) {
		return self::shortcode_wrapper( array( 'WC_Shortcode_Checkout', 'output' ), $atts );
	}

	/**
	 * Order tracking page shortcode.
	 *
	 * @param mixed $atts
	 * @return string
	 */
	public static function order_tracking( $atts ) {
		return self::shortcode_wrapper( array( 'WC_Shortcode_Order_Tracking', 'output' ), $atts );
	}

	/**
	 * My account page shortcode.
	 *
	 * @param mixed $atts
	 * @return string
	 */
	public static function my_account( $atts ) {
		return self::shortcode_wrapper( array( 'WC_Shortcode_My_Account', 'output' ), $atts );
	}

	/**
	 * List products in a category shortcode.
	 *
	 * @param array $atts
	 * @return string
	 */
	public static function product_category( $atts ) {
		$atts = shortcode_atts( array(
			'per_page' => '12',
			'columns'  => '4',
			'orderby'  => 'menu_order title',
			'order'    => 'asc',
			'category' => '',  // Slugs
			'operator' => 'IN' // Possible values are 'IN', 'NOT IN', 'AND'.
		), $atts );

		if ( ! $atts['category'] ) {
			return '';
		}

		// Default ordering args
		$ordering_args = WC()->query->get_catalog_ordering_args( $atts['orderby'], $atts['order'] );
		$meta_query    = WC()->query->get_meta_query();
		$query_args    = array(
			'post_type'           => 'product',
			'post_status'         => 'publish',
			'ignore_sticky_posts' => 1,
			'orderby'             => $ordering_args['orderby'],
			'order'               => $ordering_args['order'],
			'posts_per_page'      => $atts['per_page'],
			'meta_query'          => $meta_query
		);

		$query_args = self::_maybe_add_category_args( $query_args, $atts['category'], $atts['operator'] );

		if ( isset( $ordering_args['meta_key'] ) ) {
			$query_args['meta_key'] = $ordering_args['meta_key'];
		}

		$return = self::product_loop( $query_args, $atts, 'product_cat' );

		// Remove ordering query arguments
		WC()->query->remove_ordering_args();

		return $return;
	}


	/**
	 * List all (or limited) product categories.
	 *
	 * @param array $atts
	 * @return string
	 */
	public static function product_categories( $atts ) {
		global $woocommerce_loop;

		$atts = shortcode_atts( array(
			'number'     => null,
			'orderby'    => 'name',
			'order'      => 'ASC',
			'columns'    => '4',
			'hide_empty' => 1,
			'parent'     => '',
			'ids'        => ''
		), $atts );

		if ( isset( $atts['ids'] ) ) {
			$ids = explode( ',', $atts['ids'] );
			$ids = array_map( 'trim', $ids );
		} else {
			$ids = array();
		}

		$hide_empty = ( $atts['hide_empty'] == true || $atts['hide_empty'] == 1 ) ? 1 : 0;

		// get terms and workaround WP bug with parents/pad counts
		$args = array(
			'orderby'    => $atts['orderby'],
			'order'      => $atts['order'],
			'hide_empty' => $hide_empty,
			'include'    => $ids,
			'pad_counts' => true,
			'child_of'   => $atts['parent']
		);

		$product_categories = get_terms( 'product_cat', $args );

		if ( '' !== $atts['parent'] ) {
			$product_categories = wp_list_filter( $product_categories, array( 'parent' => $atts['parent'] ) );
		}

		if ( $hide_empty ) {
			foreach ( $product_categories as $key => $category ) {
				if ( $category->count == 0 ) {
					unset( $product_categories[ $key ] );
				}
			}
		}

		if ( $atts['number'] ) {
			$product_categories = array_slice( $product_categories, 0, $atts['number'] );
		}

		$columns = absint( $atts['columns'] );
		$woocommerce_loop['columns'] = $columns;

		ob_start();

		if ( $product_categories ) {
			woocommerce_product_loop_start();

			foreach ( $product_categories as $category ) {
				wc_get_template( 'content-product_cat.php', array(
					'category' => $category
				) );
			}

			woocommerce_product_loop_end();
		}

		woocommerce_reset_loop();

		return '<div class="woocommerce columns-' . $columns . '">' . ob_get_clean() . '</div>';
	}

	/**
	 * Recent Products shortcode.
	 *
	 * @param array $atts
	 * @return string
	 */
	public static function recent_products( $atts ) {
		$atts = shortcode_atts( array(
			'per_page' => '12',
			'columns'  => '4',
			'orderby'  => 'date',
			'order'    => 'desc',
			'category' => '',  // Slugs
			'operator' => 'IN' // Possible values are 'IN', 'NOT IN', 'AND'.
		), $atts );

		$query_args = array(
			'post_type'           => 'product',
			'post_status'         => 'publish',
			'ignore_sticky_posts' => 1,
			'posts_per_page'      => $atts['per_page'],
			'orderby'             => $atts['orderby'],
			'order'               => $atts['order'],
			'meta_query'          => WC()->query->get_meta_query()
		);

		$query_args = self::_maybe_add_category_args( $query_args, $atts['category'], $atts['operator'] );

		return self::product_loop( $query_args, $atts, 'recent_products' );
	}

	/**
	 * List multiple products shortcode.
	 *
	 * @param array $atts
	 * @return string
	 */
	public static function products( $atts ) {
		$atts = shortcode_atts( array(
			'columns' => '4',
			'orderby' => 'title',
			'order'   => 'asc',
			'ids'     => '',
			'skus'    => ''
		), $atts );

		$query_args = array(
			'post_type'           => 'product',
			'post_status'         => 'publish',
			'ignore_sticky_posts' => 1,
			'orderby'             => $atts['orderby'],
			'order'               => $atts['order'],
			'posts_per_page'      => -1,
			'meta_query'          => WC()->query->get_meta_query()
		);

		if ( ! empty( $atts['skus'] ) ) {
			$query_args['meta_query'][] = array(
				'key'     => '_sku',
				'value'   => array_map( 'trim', explode( ',', $atts['skus'] ) ),
				'compare' => 'IN'
			);

			// Ignore catalog visibility
			$query_args['meta_query'] = array_merge( $query_args['meta_query'], WC()->query->stock_status_meta_query() );
		}

		if ( ! empty( $atts['ids'] ) ) {
			$query_args['post__in'] = array_map( 'trim', explode( ',', $atts['ids'] ) );

			// Ignore catalog visibility
			$query_args['meta_query'] = array_merge( $query_args['meta_query'], WC()->query->stock_status_meta_query() );
		}

		return self::product_loop( $query_args, $atts, 'products' );
	}

	/**
	 * Display a single product.
	 *
	 * @param array $atts
	 * @return string
	 */
	public static function product( $atts ) {
		if ( empty( $atts ) ) {
			return '';
		}

		$meta_query = WC()->query->get_meta_query();

		$args = array(
			'post_type'      => 'product',
			'posts_per_page' => 1,
			'no_found_rows'  => 1,
			'post_status'    => 'publish',
			'meta_query'     => $meta_query
		);

		if ( isset( $atts['sku'] ) ) {
			$args['meta_query'][] = array(
				'key'     => '_sku',
				'value'   => $atts['sku'],
				'compare' => '='
			);
		}

		if ( isset( $atts['id'] ) ) {
			$args['p'] = $atts['id'];
		}

		ob_start();

		$products = new WP_Query( apply_filters( 'woocommerce_shortcode_products_query', $args, $atts ) );

		if ( $products->have_posts() ) : ?>

			<?php woocommerce_product_loop_start(); ?>

				<?php while ( $products->have_posts() ) : $products->the_post(); ?>

					<?php wc_get_template_part( 'content', 'product' ); ?>

				<?php endwhile; // end of the loop. ?>

			<?php woocommerce_product_loop_end(); ?>

		<?php endif;

		wp_reset_postdata();

		$css_class = 'woocommerce';

		if ( isset( $atts['class'] ) ) {
			$css_class .= ' ' . $atts['class'];
		}

		return '<div class="' . esc_attr( $css_class ) . '">' . ob_get_clean() . '</div>';
	}

	/**
	 * Display a single product price + cart button.
	 *
	 * @param array $atts
	 * @return string
	 */
	public static function product_add_to_cart( $atts ) {
		global $wpdb, $post;

		if ( empty( $atts ) ) {
			return '';
		}

		$atts = shortcode_atts( array(
			'id'         => '',
			'class'      => '',
			'quantity'   => '1',
			'sku'        => '',
			'style'      => 'border:4px solid #ccc; padding: 12px;',
			'show_price' => 'true'
		), $atts );

		if ( ! empty( $atts['id'] ) ) {
			$product_data = get_post( $atts['id'] );
		} elseif ( ! empty( $atts['sku'] ) ) {
			$product_id   = wc_get_product_id_by_sku( $atts['sku'] );
			$product_data = get_post( $product_id );
		} else {
			return '';
		}

		$product = is_object( $product_data ) && in_array( $product_data->post_type, array( 'product', 'product_variation' ) ) ? wc_setup_product_data( $product_data ) : false;

		if ( ! $product ) {
			return '';
		}

		$styles = empty( $atts['style'] ) ? '' : ' style="' . esc_attr( $atts['style'] ) . '"';

		ob_start();
		?>
		<p class="product woocommerce add_to_cart_inline <?php echo esc_attr( $atts['class'] ); ?>"<?php echo $styles; ?>>

			<?php if ( 'true' == $atts['show_price'] ) : ?>
				<?php echo $product->get_price_html(); ?>
			<?php endif; ?>

			<?php woocommerce_template_loop_add_to_cart( array( 'quantity' => $atts['quantity'] ) ); ?>

		</p><?php

		// Restore Product global in case this is shown inside a product post
		wc_setup_product_data( $post );

		return ob_get_clean();
	}

	/**
	 * Get the add to cart URL for a product.
	 *
	 * @param array $atts
	 * @return string
	 */
	public static function product_add_to_cart_url( $atts ) {
		global $wpdb;

		if ( empty( $atts ) ) {
			return '';
		}

		if ( isset( $atts['id'] ) ) {
			$product_data = get_post( $atts['id'] );
		} elseif ( isset( $atts['sku'] ) ) {
			$product_id   = wc_get_product_id_by_sku( $atts['sku'] );
			$product_data = get_post( $product_id );
		} else {
			return '';
		}

		$product = is_object( $product_data ) && in_array( $product_data->post_type, array( 'product', 'product_variation' ) ) ? wc_setup_product_data( $product_data ) : false;

		if ( ! $product ) {
			return '';
		}

		$_product = wc_get_product( $product_data );

		return esc_url( $_product->add_to_cart_url() );
	}

	/**
	 * List all products on sale.
	 *
	 * @param array $atts
	 * @return string
	 */
	public static function sale_products( $atts ) {
		$atts = shortcode_atts( array(
			'per_page' => '12',
			'columns'  => '4',
			'orderby'  => 'title',
			'order'    => 'asc',
			'category' => '', // Slugs
			'operator' => 'IN' // Possible values are 'IN', 'NOT IN', 'AND'.
		), $atts );

		$query_args = array(
			'posts_per_page' => $atts['per_page'],
			'orderby'        => $atts['orderby'],
			'order'          => $atts['order'],
			'no_found_rows'  => 1,
			'post_status'    => 'publish',
			'post_type'      => 'product',
			'meta_query'     => WC()->query->get_meta_query(),
			'post__in'       => array_merge( array( 0 ), wc_get_product_ids_on_sale() )
		);

		$query_args = self::_maybe_add_category_args( $query_args, $atts['category'], $atts['operator'] );

		return self::product_loop( $query_args, $atts, 'sale_products' );
	}

	/**
	 * List best selling products on sale.
	 *
	 * @param array $atts
	 * @return string
	 */
	public static function best_selling_products( $atts ) {
		$atts = shortcode_atts( array(
			'per_page' => '12',
			'columns'  => '4',
			'category' => '',  // Slugs
			'operator' => 'IN' // Possible values are 'IN', 'NOT IN', 'AND'.
		), $atts );

		$query_args = array(
			'post_type'           => 'product',
			'post_status'         => 'publish',
			'ignore_sticky_posts' => 1,
			'posts_per_page'      => $atts['per_page'],
			'meta_key'            => 'total_sales',
			'orderby'             => 'meta_value_num',
			'meta_query'          => WC()->query->get_meta_query()
		);

		$query_args = self::_maybe_add_category_args( $query_args, $atts['category'], $atts['operator'] );

		return self::product_loop( $query_args, $atts, 'best_selling_products' );
	}

	/**
	 * List top rated products on sale.
	 *
	 * @param array $atts
	 * @return string
	 */
	public static function top_rated_products( $atts ) {
		$atts = shortcode_atts( array(
			'per_page' => '12',
			'columns'  => '4',
			'orderby'  => 'title',
			'order'    => 'asc',
			'category' => '',  // Slugs
			'operator' => 'IN' // Possible values are 'IN', 'NOT IN', 'AND'.
		), $atts );

		$query_args = array(
			'post_type'           => 'product',
			'post_status'         => 'publish',
			'ignore_sticky_posts' => 1,
			'orderby'             => $atts['orderby'],
			'order'               => $atts['order'],
			'posts_per_page'      => $atts['per_page'],
			'meta_query'          => WC()->query->get_meta_query()
		);

		$query_args = self::_maybe_add_category_args( $query_args, $atts['category'], $atts['operator'] );

		add_filter( 'posts_clauses', array( __CLASS__, 'order_by_rating_post_clauses' ) );

		$return = self::product_loop( $query_args, $atts, 'top_rated_products' );

		remove_filter( 'posts_clauses', array( __CLASS__, 'order_by_rating_post_clauses' ) );

		return $return;
	}

	/**
	 * Output featured products.
	 *
	 * @param array $atts
	 * @return string
	 */
	public static function featured_products( $atts ) {
		$atts = shortcode_atts( array(
			'per_page' => '12',
			'columns'  => '4',
			'orderby'  => 'date',
			'order'    => 'desc',
			'category' => '',  // Slugs
			'operator' => 'IN' // Possible values are 'IN', 'NOT IN', 'AND'.
		), $atts );

		$meta_query   = WC()->query->get_meta_query();
		$meta_query[] = array(
			'key'   => '_featured',
			'value' => 'yes'
		);

		$query_args = array(
			'post_type'           => 'product',
			'post_status'         => 'publish',
			'ignore_sticky_posts' => 1,
			'posts_per_page'      => $atts['per_page'],
			'orderby'             => $atts['orderby'],
			'order'               => $atts['order'],
			'meta_query'          => $meta_query
		);

		$query_args = self::_maybe_add_category_args( $query_args, $atts['category'], $atts['operator'] );

		return self::product_loop( $query_args, $atts, 'featured_products' );
	}

	/**
	 * Show a single product page.
	 *
	 * @param array $atts
	 * @return string
	 */
	public static function product_page( $atts ) {
		if ( empty( $atts ) ) {
			return '';
		}

		if ( ! isset( $atts['id'] ) && ! isset( $atts['sku'] ) ) {
			return '';
		}

		$args = array(
			'posts_per_page'      => 1,
			'post_type'           => 'product',
			'post_status'         => 'publish',
			'ignore_sticky_posts' => 1,
			'no_found_rows'       => 1
		);

		if ( isset( $atts['sku'] ) ) {
			$args['meta_query'][] = array(
				'key'     => '_sku',
				'value'   => sanitize_text_field( $atts['sku'] ),
				'compare' => '='
			);

			$args['post_type'] = array( 'product', 'product_variation' );
		}

		if ( isset( $atts['id'] ) ) {
			$args['p'] = absint( $atts['id'] );
		}

		$single_product = new WP_Query( $args );

		$preselected_id = '0';

		// check if sku is a variation
		if ( isset( $atts['sku'] ) && $single_product->have_posts() && $single_product->post->post_type === 'product_variation' ) {

			$variation = new WC_Product_Variation( $single_product->post->ID );
			$attributes = $variation->get_variation_attributes();

			// set preselected id to be used by JS to provide context
			$preselected_id = $single_product->post->ID;

			// get the parent product object
			$args = array(
				'posts_per_page'      => 1,
				'post_type'           => 'product',
				'post_status'         => 'publish',
				'ignore_sticky_posts' => 1,
				'no_found_rows'       => 1,
				'p'                   => $single_product->post->post_parent
			);

			$single_product = new WP_Query( $args );
		?>
			<script type="text/javascript">
				jQuery( document ).ready( function( $ ) {
					var $variations_form = $( '[data-product-page-preselected-id="<?php echo esc_attr( $preselected_id ); ?>"]' ).find( 'form.variations_form' );

					<?php foreach( $attributes as $attr => $value ) { ?>
						$variations_form.find( 'select[name="<?php echo esc_attr( $attr ); ?>"]' ).val( '<?php echo $value; ?>' );
					<?php } ?>
				});
			</script>
		<?php
		}

		ob_start();

		while ( $single_product->have_posts() ) : $single_product->the_post(); wp_enqueue_script( 'wc-single-product' ); ?>

			<div class="single-product" data-product-page-preselected-id="<?php echo esc_attr( $preselected_id ); ?>">

				<?php wc_get_template_part( 'content', 'single-product' ); ?>

			</div>

		<?php endwhile; // end of the loop.

		wp_reset_postdata();

		return '<div class="woocommerce">' . ob_get_clean() . '</div>';
	}

	/**
	 * Show messages.
	 *
	 * @return string
	 */
	public static function shop_messages() {
		ob_start();
		wc_print_notices();
		return '<div class="woocommerce">' . ob_get_clean() . '</div>';
	}

	/**
	 * woocommerce_order_by_rating_post_clauses function.
	 *
	 * @param array $args
	 * @return array
	 */
	public static function order_by_rating_post_clauses( $args ) {
		global $wpdb;

		$args['where']   .= " AND $wpdb->commentmeta.meta_key = 'rating' ";
		$args['join']    .= "LEFT JOIN $wpdb->comments ON($wpdb->posts.ID               = $wpdb->comments.comment_post_ID) LEFT JOIN $wpdb->commentmeta ON($wpdb->comments.comment_ID = $wpdb->commentmeta.comment_id)";
		$args['orderby'] = "$wpdb->commentmeta.meta_value DESC";
		$args['groupby'] = "$wpdb->posts.ID";

		return $args;
	}

	/**
	 * List products with an attribute shortcode.
	 * Example [product_attribute attribute='color' filter='black'].
	 *
	 * @param array $atts
	 * @return string
	 */
	public static function product_attribute( $atts ) {
		$atts = shortcode_atts( array(
			'per_page'  => '12',
			'columns'   => '4',
			'orderby'   => 'title',
			'order'     => 'asc',
			'attribute' => '',
			'filter'    => ''
		), $atts );

		$query_args = array(
			'post_type'           => 'product',
			'post_status'         => 'publish',
			'ignore_sticky_posts' => 1,
			'posts_per_page'      => $atts['per_page'],
			'orderby'             => $atts['orderby'],
			'order'               => $atts['order'],
			'meta_query'          => WC()->query->get_meta_query(),
			'tax_query'           => array(
				array(
					'taxonomy' => strstr( $atts['attribute'], 'pa_' ) ? sanitize_title( $atts['attribute'] ) : 'pa_' . sanitize_title( $atts['attribute'] ),
					'terms'    => array_map( 'sanitize_title', explode( ',', $atts['filter'] ) ),
					'field'    => 'slug'
				)
			)
		);

		return self::product_loop( $query_args, $atts, 'product_attribute' );
	}

	/**
	 * List related products.
	 * @param array $atts
	 * @return string
	 */
	public static function related_products( $atts ) {
		$atts = shortcode_atts( array(
			'per_page' => '4',
			'columns'  => '4',
			'orderby'  => 'rand'
		), $atts );

		ob_start();

		// Rename arg
		$atts['posts_per_page'] = absint( $atts['per_page'] );

		woocommerce_related_products( $atts );

		return ob_get_clean();
	}

	/**
	 * Adds a tax_query index to the query to filter by category.
	 *
	 * @param array $args
	 * @param string $category
	 * @param string $operator
	 * @return array;
	 * @access private
	 */
	private static function _maybe_add_category_args( $args, $category, $operator ) {
		if ( ! empty( $category ) ) {
			$args['tax_query'] = array(
				array(
					'taxonomy' => 'product_cat',
					'terms'    => array_map( 'sanitize_title', explode( ',', $category ) ),
					'field'    => 'slug',
					'operator' => $operator
				)
			);
		}

		return $args;
	}
}
