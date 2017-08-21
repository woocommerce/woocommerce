<?php
/**
 * Shortcodes
 *
 * @version  3.2.0
 * @package  WooCommerce/Classes
 * @category Class
 * @author   Automattic
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Shortcodes class.
 */
class WC_Shortcodes {

	/**
	 * Init shortcodes.
	 */
	public static function init() {
		$shortcodes = array(
			'products'                   => __CLASS__ . '::products',
			'product_page'               => __CLASS__ . '::product_page',
			'product_categories'         => __CLASS__ . '::product_categories',
			'add_to_cart'                => __CLASS__ . '::product_add_to_cart',
			'add_to_cart_url'            => __CLASS__ . '::product_add_to_cart_url',
			'related_products'           => __CLASS__ . '::related_products',
			'shop_messages'              => __CLASS__ . '::shop_messages',
			'woocommerce_order_tracking' => __CLASS__ . '::order_tracking',
			'woocommerce_cart'           => __CLASS__ . '::cart',
			'woocommerce_checkout'       => __CLASS__ . '::checkout',
			'woocommerce_my_account'     => __CLASS__ . '::my_account',
			'product'                    => __CLASS__ . '::product',
			'product_category'           => __CLASS__ . '::product_category',
			'recent_products'            => __CLASS__ . '::recent_products',
			'sale_products'              => __CLASS__ . '::sale_products',
			'best_selling_products'      => __CLASS__ . '::best_selling_products',
			'top_rated_products'         => __CLASS__ . '::top_rated_products',
			'featured_products'          => __CLASS__ . '::featured_products',
			'product_attribute'          => __CLASS__ . '::product_attribute',
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
	 * @param array $wrapper
	 *
	 * @return string
	 */
	public static function shortcode_wrapper(
		$function,
		$atts    = array(),
		$wrapper = array(
			'class'  => 'woocommerce',
			'before' => null,
			'after'  => null,
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

		$columns                     = absint( $atts['columns'] );
		$woocommerce_loop['columns'] = $columns;
		$woocommerce_loop['name']    = $loop_name;
		$query_args                  = apply_filters( 'woocommerce_shortcode_products_query', $query_args, $atts, $loop_name );
		$transient_name              = 'wc_loop' . substr( md5( json_encode( $query_args ) . $loop_name ), 28 ) . WC_Cache_Helper::get_transient_version( 'product_query' );
		$products                    = get_transient( $transient_name );

		if ( false === $products || ! is_a( $products, 'WP_Query' ) ) {
			$products = new WP_Query( $query_args );
			set_transient( $transient_name, $products, DAY_IN_SECONDS * 30 );
		}

		ob_start();

		if ( $products->have_posts() ) {

			// Prime caches before grabbing objects.
			update_post_caches( $products->posts, array( 'product', 'product_variation' ) );
			?>

			<?php do_action( "woocommerce_shortcode_before_{$loop_name}_loop", $atts ); ?>

			<?php woocommerce_product_loop_start(); ?>

				<?php while ( $products->have_posts() ) : $products->the_post(); ?>

					<?php wc_get_template_part( 'content', 'product' ); ?>

				<?php endwhile; // end of the loop. ?>

			<?php woocommerce_product_loop_end(); ?>

			<?php do_action( "woocommerce_shortcode_after_{$loop_name}_loop", $atts ); ?>

			<?php
		} else {
			do_action( "woocommerce_shortcode_{$loop_name}_loop_no_results", $atts );
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
	 * Main product listing shortcode.
	 *
	 * @param array $atts
	 * @param str $loop_name
	 * @return string
	 */
	public static function products( $atts, $loop_name = 'products' ) {
		if ( empty( $atts ) ) {
			return '';
		}

		// Filterable with the shortcode_atts_{$shortcode} filter.
		$atts = shortcode_atts( array(
			'orderby'       => '',     // menu_order, title, date, rand, price, popularity, rating, or id
			'order'         => 'desc', // asc or desc
			'limit'         => '4',
			'per_page'      => '',     // overrides 'limit'
			'columns'       => '4',
			'ids'           => '',     // comma separated IDs
			'skus'          => '',     // comma separated SKUs
			'category'      => '',     // comma separated category slugs
			'cat_operator'  => 'IN',   // IN, NOT IN, or AND
			'attribute'     => '',     // single attribute slug
			'attr_terms'    => '',     // comma separated term slugs
			'attr_operator' => 'IN',   // IN, NOT IN, or AND
			'on_sale'       => false,
			'featured'      => false,
		), $atts, 'products' );

		$query_args = self::products_query_args( $atts, $loop_name );

		return self::product_loop( $query_args, $atts, $loop_name );
	}

	/**
	 * Take in the shortcode attributes and return query args.
	 *
	 * @param  array  $atts      Shortcode attributes.
	 * @param  string $loop_name Loop name.
	 * @return array
	 */
	public static function products_query_args( $atts, $loop_name ) {
		$query_args = apply_filters( "woocommerce_default_{$loop_name}_query_args", array(
			'post_type'           => 'product',
			'post_status'         => 'publish',
			'ignore_sticky_posts' => 1,
			'no_found_rows'       => 1,
			'orderby'             => empty( $atts['orderby'] ) ? '' : $atts['orderby'],
			'order'               => empty( $atts['order'] ) ? 'desc' : $atts['order'],
			'posts_per_page'      => empty( $atts['limit'] ) ? '1' : $atts['limit'],
			'tax_query'           => WC()->query->get_tax_query(),
			'meta_query'          => WC()->query->get_meta_query(),
		) );

		// Ordering.
		if ( 'popularity' === $query_args['orderby'] ) {
			$query_args['order'] = ( 'DESC' === strtoupper( $query_args['order'] ) ) ? 'DESC' : 'ASC';

			// can remove after get_catalog_ordering_args() is updated.
			$query_args['meta_key'] = 'total_sales';
			$query_args['orderby'] = array(
				'meta_value_num' => 'DESC',
				'post_date'      => 'DESC',
			);
		} else if ( is_string( $query_args['orderby'] ) && 'ID' === strtoupper( $query_args['orderby'] ) ) {
			$query_args['orderby'] = 'ID';
			$query_args['order'] = ( 'DESC' === strtoupper( $query_args['order'] ) ) ? 'DESC' : 'ASC';
		} else {
			$ordering_args = WC()->query->get_catalog_ordering_args( $query_args['orderby'], $query_args['order'] );
			$query_args['orderby'] = $ordering_args['orderby'];
			$query_args['order']   = $ordering_args['order'];

			// Add the meta key if ordering by rating.
			if ( isset( $ordering_args['meta_key'] ) ) {
				$query_args['meta_key'] = $ordering_args['meta_key'];
			}
		}

		// Taxonomy Queries.
		if ( ! empty( $atts['category'] ) ) {
			$query_args['tax_query'][] = array(
					'taxonomy'     => 'product_cat',
					'terms'        => array_map( 'sanitize_title', explode( ',', $atts['category'] ) ),
					'field'        => 'slug',
					'operator'     => empty( $atts['cat_operator'] ) ? 'IN' : $atts['cat_operator'],
			);
		}

		if ( ! empty( $atts['featured'] ) && $atts['featured'] ) {
			$query_args['tax_query'][] = array(
				'taxonomy' => 'product_visibility',
				'terms'    => 'featured',
				'field'    => 'name',
				'operator' => 'IN',
			);
		}

		if ( ! empty( $atts['attribute'] ) && ! empty( $atts['attr_terms'] ) ) {
			$query_args['tax_query'][] = array(
				'taxonomy' => strstr( $atts['attribute'], 'pa_' ) ? sanitize_title( $atts['attribute'] ) : 'pa_' . sanitize_title( $atts['attribute'] ),
				'terms'    => array_map( 'sanitize_title', explode( ',', $atts['attr_terms'] ) ),
				'field'    => 'slug',
				'operator' => empty( $atts['attr_operator'] ) ? 'IN' : $atts['attr_operator'],
			);
		}

		// Meta Queries & post__in.
		$min_per_page = 0;
		if ( ! empty( $atts['ids'] ) ) {
			$min_per_page += count( explode( ',', $atts['ids'] ) );
			$query_args['post__in'] = array_map( 'trim', explode( ',', $atts['ids'] ) );
		}

		if ( ! empty( $atts['skus'] ) ) {
			$min_per_page += count( explode( ',', $atts['skus'] ) );
			$query_args['meta_query'][] = array(
				'key'     => '_sku',
				'value'   => array_map( 'trim', explode( ',', $atts['skus'] ) ),
				'compare' => 'IN',
			);
		}

		// Allow 'per_page' to override 'limit' for backwards compatibility.
		if ( ! empty( $atts['per_page'] ) ) {
			$query_args['posts_per_page'] = $atts['per_page'];
		}

		// Ensure enough products are shown if IDs or SKUs were entered.
		if ( $query_args['posts_per_page'] < $min_per_page && '-1' != $query_args['posts_per_page'] ) {
			$query_args['posts_per_page'] = $min_per_page;
		}

		if ( ! empty( $atts['on_sale'] ) && $atts['on_sale'] ) {
			if ( isset( $query_args['post__in'] ) ) {
				$query_args['post__in'] = array_merge( $query_args['post__in'], wc_get_product_ids_on_sale() );
			} else {
				$query_args['post__in'] = array_merge( array( 0 ), wc_get_product_ids_on_sale() );
			}
		}

		return $query_args;
	}

	/**
	 * List products in a category shortcode.
	 *
	 * @param array $deprecated
	 * @return string
	 */
	public static function product_category( $deprecated ) {
		$args = shortcode_atts( array(
			'limit'    => '12',
			'columns'  => '4',
			'orderby'  => 'menu_order title',
			'order'    => 'asc',
			'category' => '',  // Slugs
			'operator' => 'IN', // Possible values are 'IN', 'NOT IN', 'AND'.
		), $deprecated, 'product_category' );

		if ( ! $deprecated['category'] ) {
			return '';
		}

		// Map to the new key.
		$args['cat_operator'] = $args['operator'];
		unset( $args['operator'] );

		return self::products( $args, 'product_category' );
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
			'ids'        => '',
		), $atts, 'product_categories' );

		$ids        = array_filter( array_map( 'trim', explode( ',', $atts['ids'] ) ) );
		$hide_empty = ( true === $atts['hide_empty'] || 'true' === $atts['hide_empty'] || 1 === $atts['hide_empty'] || '1' === $atts['hide_empty'] ) ? 1 : 0;

		// get terms and workaround WP bug with parents/pad counts
		$args = array(
			'orderby'    => $atts['orderby'],
			'order'      => $atts['order'],
			'hide_empty' => $hide_empty,
			'include'    => $ids,
			'pad_counts' => true,
			'child_of'   => $atts['parent'],
		);

		$product_categories = get_terms( 'product_cat', $args );

		if ( '' !== $atts['parent'] ) {
			$product_categories = wp_list_filter( $product_categories, array( 'parent' => $atts['parent'] ) );
		}

		if ( $hide_empty ) {
			foreach ( $product_categories as $key => $category ) {
				if ( 0 == $category->count ) {
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
					'category' => $category,
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
	 * @param array $deprecated
	 * @return string
	 */
	public static function recent_products( $deprecated ) {
		$args = shortcode_atts( array(
			'limit'    => '12',
			'columns'  => '4',
			'orderby'  => 'date',
			'order'    => 'desc',
			'category' => '',  // Slugs
			'operator' => 'IN', // Possible values are 'IN', 'NOT IN', 'AND'.
		), $deprecated, 'recent_products' );

		// Map to the new key.
		$args['cat_operator'] = $args['operator'];
		unset( $args['operator'] );

		return self::products( $args, 'recent_products' );
	}

	/**
	 * Display a single product.
	 *
	 * @param array $atts
	 * @return string
	 */
	public static function product( $deprecated ) {
		$args = array();

		if ( isset( $deprecated['sku'] ) ) {
			$args['skus'] = $deprecated['sku'];
		}

		if ( isset( $deprecated['id'] ) ) {
			$args['ids'] = $deprecated['id'];
		}

		return self::products( $args, 'product' );
	}

	/**
	 * Display a single product price + cart button.
	 *
	 * @param array $atts
	 * @return string
	 */
	public static function product_add_to_cart( $atts ) {
		global $post;

		if ( empty( $atts ) ) {
			return '';
		}

		$atts = shortcode_atts( array(
			'id'         => '',
			'class'      => '',
			'quantity'   => '1',
			'sku'        => '',
			'style'      => 'border:4px solid #ccc; padding: 12px;',
			'show_price' => 'true',
		), $atts, 'product_add_to_cart' );

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
	 * @param array $deprecated
	 * @return string
	 */
	public static function sale_products( $deprecated ) {
		$args = shortcode_atts( array(
			'limit'    => '12',
			'columns'  => '4',
			'orderby'  => 'title',
			'order'    => 'asc',
			'category' => '', // Slugs
			'operator' => 'IN', // Possible values are 'IN', 'NOT IN', 'AND'.
		), $deprecated, 'sale_products' );

		// Map to the new key.
		$args['cat_operator'] = $args['operator'];
		unset( $args['operator'] );

		$args['on_sale'] = true;

		return self::products( $args, 'sale_products' );
	}

	/**
	 * List best selling products on sale.
	 *
	 * @param array $deprecated
	 * @return string
	 */
	public static function best_selling_products( $deprecated ) {
		$args = shortcode_atts( array(
			'limit'    => '12',
			'columns'  => '4',
			'category' => '',  // Slugs
			'operator' => 'IN', // Possible values are 'IN', 'NOT IN', 'AND'.
		), $deprecated, 'best_selling_products' );

		// Map to the new key.
		$args['cat_operator'] = $args['operator'];
		unset( $args['operator'] );

		$args['orderby'] = 'popularity';

		return self::products( $args, 'best_selling_products' );
	}

	/**
	 * List top rated products on sale.
	 *
	 * @param array $deprecated
	 * @return string
	 */
	public static function top_rated_products( $deprecated ) {
		$args = shortcode_atts( array(
			'limit'    => '12',
			'columns'  => '4',
			'orderby'  => 'title',
			'order'    => 'asc',
			'category' => '',  // Slugs
			'operator' => 'IN', // Possible values are 'IN', 'NOT IN', 'AND'.
		), $deprecated, 'top_rated_products' );

		// Map to the new key.
		$args['cat_operator'] = $args['operator'];
		unset( $args['operator'] );

		$args['orderby'] = 'rating';

		return self::products( $args, 'top_rated_products' );
	}

	/**
	 * Output featured products.
	 *
	 * @param array $deprecated
	 * @return string
	 */
	public static function featured_products( $deprecated ) {
		$args = shortcode_atts( array(
			'limit'    => '12',
			'columns'  => '4',
			'orderby'  => 'date',
			'order'    => 'desc',
			'category' => '',  // Slugs
			'operator' => 'IN', // Possible values are 'IN', 'NOT IN', 'AND'.
		), $deprecated, 'featured_products' );

		// Map to the new key.
		$args['cat_operator'] = $args['operator'];
		unset( $args['operator'] );

		$args['featured'] = 'true';

		return self::products( $args, 'featured_products' );
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
			'no_found_rows'       => 1,
		);

		if ( isset( $atts['sku'] ) ) {
			$args['meta_query'][] = array(
				'key'     => '_sku',
				'value'   => sanitize_text_field( $atts['sku'] ),
				'compare' => '=',
			);

			$args['post_type'] = array( 'product', 'product_variation' );
		}

		if ( isset( $atts['id'] ) ) {
			$args['p'] = absint( $atts['id'] );
		}

		$single_product = new WP_Query( $args );

		$preselected_id = '0';

		// check if sku is a variation
		if ( isset( $atts['sku'] ) && $single_product->have_posts() && 'product_variation' === $single_product->post->post_type ) {

			$variation = new WC_Product_Variation( $single_product->post->ID );
			$attributes = $variation->get_attributes();

			// set preselected id to be used by JS to provide context
			$preselected_id = $single_product->post->ID;

			// get the parent product object
			$args = array(
				'posts_per_page'      => 1,
				'post_type'           => 'product',
				'post_status'         => 'publish',
				'ignore_sticky_posts' => 1,
				'no_found_rows'       => 1,
				'p'                   => $single_product->post->post_parent,
			);

			$single_product = new WP_Query( $args );
		?>
			<script type="text/javascript">
				jQuery( document ).ready( function( $ ) {
					var $variations_form = $( '[data-product-page-preselected-id="<?php echo esc_attr( $preselected_id ); ?>"]' ).find( 'form.variations_form' );

					<?php foreach ( $attributes as $attr => $value ) { ?>
						$variations_form.find( 'select[name="<?php echo esc_attr( $attr ); ?>"]' ).val( '<?php echo esc_js( $value ); ?>' );
					<?php } ?>
				});
			</script>
		<?php
		}

		ob_start();

		global $wp_query;

		// Backup query object so following loops think this is a product page.
		$previous_wp_query = $wp_query;
		$wp_query          = $single_product;

		wp_enqueue_script( 'wc-single-product' );

		while ( $single_product->have_posts() ) {
			$single_product->the_post()
			?>
			<div class="single-product" data-product-page-preselected-id="<?php echo esc_attr( $preselected_id ); ?>">
				<?php wc_get_template_part( 'content', 'single-product' ); ?>
			</div>
			<?php
		}

		// restore $previous_wp_query and reset post data.
		$wp_query = $previous_wp_query;
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
	 * List products with an attribute shortcode.
	 * Example [product_attribute attribute='color' filter='black'].
	 *
	 * @param array $deprecated
	 * @return string
	 */
	public static function product_attribute( $deprecated ) {
		$args = shortcode_atts( array(
			'limit'     => '12',
			'columns'   => '4',
			'orderby'   => 'title',
			'order'     => 'asc',
			'attribute' => '',
			'filter'    => '',
		), $deprecated, 'product_attribute' );

		// Map to the new keys.
		$args['attr_terms'] = $args['filter'];
		unset( $args['filter'] );

		return self::products( $args, 'product_attribute' );
	}

	/**
	 * List related products.
	 * @param array $atts
	 * @return string
	 */
	public static function related_products( $atts ) {
		$atts = shortcode_atts( array(
			'limit'    => '4',
			'columns'  => '4',
			'orderby'  => 'rand',
		), $atts, 'related_products' );

		ob_start();

		// Rename arg
		$atts['posts_per_page'] = isset( $atts['per_page'] ) ? absint( $atts['per_page'] ) : absint( $atts['limit'] );

		woocommerce_related_products( $atts );

		return ob_get_clean();
	}

}
