<?php
/**
 * WooCommerce Template Functions
 *
 * Functions used in the template files to output content - in most cases hooked in via the template actions. All functions are pluggable.
 *
 * @package WooCommerce
 * @category Core
 * @author WooThemes
 */

/** Template pages ********************************************************/

if ( ! function_exists( 'woocommerce_content' ) ) {
	// This function is only used in the optional 'woocommerce.php' template
	// people can add to their themes to add basic woocommerce support without
	// using hooks or modifying core templates.
	function woocommerce_content() {

		if ( is_singular( 'product' ) ) {

			while ( have_posts() ) : the_post();

				woocommerce_get_template_part( 'content', 'single-product' );

			endwhile;

		} else {

			?><h1 class="page-title">
				<?php if ( is_search() ) : ?>
					<?php printf( __( 'Search Results: &ldquo;%s&rdquo;', 'woocommerce' ), get_search_query() ); ?>
				<?php elseif ( is_tax() ) : ?>
					<?php echo single_term_title( "", false ); ?>
				<?php else : ?>
					<?php
						$shop_page = get_post( woocommerce_get_page_id( 'shop' ) );

						echo apply_filters( 'the_title', ( $shop_page_title = get_option( 'woocommerce_shop_page_title' ) ) ? $shop_page_title : $shop_page->post_title );
					?>
				<?php endif; ?>

				<?php if ( get_query_var( 'paged' ) ) : ?>
					<?php printf( __( '&nbsp;&ndash; Page %s', 'woocommerce' ), get_query_var( 'paged' ) ); ?>
				<?php endif; ?>
			</h1>

			<?php do_action( 'woocommerce_archive_description' ); ?>

			<?php if ( is_tax() ) : ?>
				<?php do_action( 'woocommerce_taxonomy_archive_description' ); ?>
			<?php elseif ( ! empty( $shop_page ) && is_object( $shop_page ) ) : ?>
				<?php do_action( 'woocommerce_product_archive_description', $shop_page ); ?>
			<?php endif; ?>

			<?php if ( have_posts() ) : ?>

				<?php do_action('woocommerce_before_shop_loop'); ?>

				<ul class="products">

					<?php woocommerce_product_subcategories(); ?>

					<?php while ( have_posts() ) : the_post(); ?>

						<?php woocommerce_get_template_part( 'content', 'product' ); ?>

					<?php endwhile; // end of the loop. ?>

				</ul>

				<?php do_action('woocommerce_after_shop_loop'); ?>

			<?php else : ?>

				<?php if ( ! woocommerce_product_subcategories( array( 'before' => '<ul class="products">', 'after' => '</ul>' ) ) ) : ?>

					<p><?php _e( 'No products found which match your selection.', 'woocommerce' ); ?></p>

				<?php endif; ?>

			<?php endif; ?>

			<div class="clear"></div>

			<?php do_action( 'woocommerce_pagination' );

		}
	}
}

/**
 * deprecated template functions from < 1.6
 **/
if ( ! function_exists( 'woocommerce_single_product_content' ) ) {
	function woocommerce_single_product_content() {
		_deprecated_function( __FUNCTION__, '1.6' );
		woocommerce_content();
	}
}
if ( ! function_exists( 'woocommerce_archive_product_content' ) ) {
	function woocommerce_archive_product_content() {
		_deprecated_function( __FUNCTION__, '1.6' );
		woocommerce_content();
	}
}
if ( ! function_exists( 'woocommerce_product_taxonomy_content' ) ) {
	function woocommerce_product_taxonomy_content() {
		_deprecated_function( __FUNCTION__, '1.6' );
		woocommerce_content();
	}
}

/** Global ****************************************************************/

if ( ! function_exists( 'woocommerce_output_content_wrapper' ) ) {
	function woocommerce_output_content_wrapper() {
		woocommerce_get_template( 'shop/wrapper-start.php' );
	}
}
if ( ! function_exists( 'woocommerce_output_content_wrapper_end' ) ) {
	function woocommerce_output_content_wrapper_end() {
		woocommerce_get_template( 'shop/wrapper-end.php' );
	}
}

/**
 * Messages
 **/
if ( ! function_exists( 'woocommerce_show_messages' ) ) {
	function woocommerce_show_messages() {
		global $woocommerce;

		if ( $woocommerce->error_count() > 0  ) {

			woocommerce_get_template( 'shop/errors.php', array(
					'errors' => $woocommerce->get_errors()
				) );

		}

		if ( $woocommerce->message_count() > 0  ) {

			woocommerce_get_template( 'shop/messages.php', array(
					'messages' => $woocommerce->get_messages()
				) );

		}

		$woocommerce->clear_messages();
	}
}

/**
 * Sidebar
 **/
if ( ! function_exists( 'woocommerce_get_sidebar' ) ) {
	function woocommerce_get_sidebar() {
		woocommerce_get_template( 'shop/sidebar.php' );
	}
}

/**
 * Demo Banner
 *
 * Adds a demo store banner to the site if enabled
 **/
if ( ! function_exists( 'woocommerce_demo_store' ) ) {
	function woocommerce_demo_store() {
		if ( get_option( 'woocommerce_demo_store' ) == 'no' )
			return;

		$notice = get_option( 'woocommerce_demo_store_notice' );
		if ( empty( $notice ) )
			$notice = __( 'This is a demo store for testing purposes &mdash; no orders shall be fulfilled.', 'woocommerce' );

		echo apply_filters( 'woocommerce_demo_store', '<p class="demo_store">' . $notice . '</p>'  );
	}
}

/** Loop ******************************************************************/

/**
 * Archive descriptions
 **/
if ( ! function_exists( 'woocommerce_taxonomy_archive_description' ) ) {
	function woocommerce_taxonomy_archive_description() {
		if ( is_tax( array( 'product_cat', 'product_tag' ) ) && get_query_var( 'paged' ) == 0 )
			echo '<div class="term-description">' . wpautop( wptexturize( term_description() ) ) . '</div>';
	}
}
if ( ! function_exists( 'woocommerce_product_archive_description' ) ) {
	function woocommerce_product_archive_description( $shop_page ) {
		if ( get_query_var( 'paged' ) == 0 )
			echo '<div class="page-description">' . apply_filters( 'the_content', $shop_page->post_content ) . '</div>';
	}
}

/**
 * Products Loop
 **/
if ( ! function_exists( 'woocommerce_template_loop_add_to_cart' ) ) {
	function woocommerce_template_loop_add_to_cart() {
		woocommerce_get_template( 'loop/add-to-cart.php' );
	}
}
if ( ! function_exists( 'woocommerce_template_loop_product_thumbnail' ) ) {
	function woocommerce_template_loop_product_thumbnail() {
		echo woocommerce_get_product_thumbnail();
	}
}
if ( ! function_exists( 'woocommerce_template_loop_price' ) ) {
	function woocommerce_template_loop_price() {
		woocommerce_get_template( 'loop/price.php' );
	}
}
if ( ! function_exists( 'woocommerce_show_product_loop_sale_flash' ) ) {
	function woocommerce_show_product_loop_sale_flash() {
		woocommerce_get_template( 'loop/sale-flash.php' );
	}
}
if ( ! function_exists( 'woocommerce_reset_loop' ) ) {
	function woocommerce_reset_loop() {
		global $woocommerce_loop;
		// Reset loop/columns globals when starting a new loop
		$woocommerce_loop['loop'] = $woocommerce_loop['column'] = '';
	}
}

/**
 * WooCommerce Product Thumbnail
 **/
if ( ! function_exists( 'woocommerce_get_product_thumbnail' ) ) {
	function woocommerce_get_product_thumbnail( $size = 'shop_catalog', $placeholder_width = 0, $placeholder_height = 0  ) {
		global $post, $woocommerce;

		if ( ! $placeholder_width )
			$placeholder_width = $woocommerce->get_image_size( 'shop_catalog_image_width' );
		if ( ! $placeholder_height )
			$placeholder_height = $woocommerce->get_image_size( 'shop_catalog_image_height' );

		if ( has_post_thumbnail() )
			return get_the_post_thumbnail( $post->ID, $size );
		elseif ( woocommerce_placeholder_img_src() )
			return '<img src="'. woocommerce_placeholder_img_src() .'" alt="Placeholder" width="' . $placeholder_width . '" height="' . $placeholder_height . '" />';
	}
}

/**
 * Pagination
 **/
if ( ! function_exists( 'woocommerce_pagination' ) ) {
	function woocommerce_pagination() {
		woocommerce_get_template( 'loop/pagination.php' );
	}
}

/**
 * Sorting
 **/
if ( ! function_exists( 'woocommerce_catalog_ordering' ) ) {
	function woocommerce_catalog_ordering() {
		if ( ! isset( $_SESSION['orderby'] ) )
			$_SESSION['orderby'] = apply_filters( 'woocommerce_default_catalog_orderby', get_option( 'woocommerce_default_catalog_orderby' ) );
		woocommerce_get_template( 'loop/sorting.php' );
	}
}

/** Single Product ********************************************************/

/**
 * Before Single Products Summary Div
 **/
if ( ! function_exists( 'woocommerce_show_product_images' ) ) {
	function woocommerce_show_product_images() {
		woocommerce_get_template( 'single-product/product-image.php' );
	}
}
if ( ! function_exists( 'woocommerce_show_product_thumbnails' ) ) {
	function woocommerce_show_product_thumbnails() {
		woocommerce_get_template( 'single-product/product-thumbnails.php' );
	}
}
if ( ! function_exists( 'woocommerce_output_product_data_tabs' ) ) {
	function woocommerce_output_product_data_tabs() {
		woocommerce_get_template( 'single-product/tabs.php' );
	}
}
if ( ! function_exists( 'woocommerce_template_single_title' ) ) {
	function woocommerce_template_single_title() {
		woocommerce_get_template( 'single-product/title.php' );
	}
}
if ( ! function_exists( 'woocommerce_template_single_price' ) ) {
	function woocommerce_template_single_price() {
		woocommerce_get_template( 'single-product/price.php' );
	}
}
if ( ! function_exists( 'woocommerce_template_single_excerpt' ) ) {
	function woocommerce_template_single_excerpt() {
		woocommerce_get_template( 'single-product/short-description.php' );
	}
}
if ( ! function_exists( 'woocommerce_template_single_meta' ) ) {
	function woocommerce_template_single_meta() {
		woocommerce_get_template( 'single-product/meta.php' );
	}
}
if ( ! function_exists( 'woocommerce_template_single_sharing' ) ) {
	function woocommerce_template_single_sharing() {
		woocommerce_get_template( 'single-product/share.php' );
	}
}
if ( ! function_exists( 'woocommerce_show_product_sale_flash' ) ) {
	function woocommerce_show_product_sale_flash() {
		woocommerce_get_template( 'single-product/sale-flash.php' );
	}
}

/**
 * Product Add to cart buttons
 **/
if ( ! function_exists( 'woocommerce_template_single_add_to_cart' ) ) {
	function woocommerce_template_single_add_to_cart() {
		global $product;
		do_action( 'woocommerce_' . $product->product_type . '_add_to_cart'  );
	}
}
if ( ! function_exists( 'woocommerce_simple_add_to_cart' ) ) {
	function woocommerce_simple_add_to_cart() {
		woocommerce_get_template( 'single-product/add-to-cart/simple.php' );
	}
}
if ( ! function_exists( 'woocommerce_grouped_add_to_cart' ) ) {
	function woocommerce_grouped_add_to_cart() {
		woocommerce_get_template( 'single-product/add-to-cart/grouped.php' );
	}
}
if ( ! function_exists( 'woocommerce_variable_add_to_cart' ) ) {
	function woocommerce_variable_add_to_cart() {
		global $product;

		// Enqueue variation scripts
		wp_enqueue_script( 'wc-add-to-cart-variation' );

		// Load the template
		woocommerce_get_template( 'single-product/add-to-cart/variable.php', array(
				'available_variations'  => $product->get_available_variations(),
				'attributes'   			=> $product->get_variation_attributes(),
				'selected_attributes' 	=> $product->get_variation_default_attributes()
			) );
	}
}
if ( ! function_exists( 'woocommerce_external_add_to_cart' ) ) {
	function woocommerce_external_add_to_cart() {
		global $product;

		$product_url = get_post_meta( $product->id, '_product_url', true  );
		$button_text = get_post_meta( $product->id, '_button_text', true  );

		if ( ! $product_url ) return;

		woocommerce_get_template( 'single-product/add-to-cart/external.php', array(
				'product_url' => $product_url,
				'button_text' => ( $button_text ) ? $button_text : __( 'Buy product', 'woocommerce' ) ,
			) );
	}
}

/**
 * Quantity inputs
 **/
if ( ! function_exists( 'woocommerce_quantity_input' ) ) {
	function woocommerce_quantity_input( $args = array() ) {
		$defaults = array(
			'input_name'  => 'quantity',
			'input_value'  => '1',
			'max_value'  => '',
			'min_value'  => '0'
		);

		$args = apply_filters( 'woocommerce_quantity_input_args', wp_parse_args( $args, $defaults  ) );

		woocommerce_get_template( 'single-product/add-to-cart/quantity.php', $args );
	}
}

/**
 * Product page tabs
 **/
if ( ! function_exists( 'woocommerce_product_description_tab' ) ) {
	function woocommerce_product_description_tab() {
		woocommerce_get_template( 'single-product/tabs/tab-description.php' );
	}
}
if ( ! function_exists( 'woocommerce_product_attributes_tab' ) ) {
	function woocommerce_product_attributes_tab() {
		woocommerce_get_template( 'single-product/tabs/tab-attributes.php' );
	}
}
if ( ! function_exists( 'woocommerce_product_reviews_tab' ) ) {
	function woocommerce_product_reviews_tab() {
		woocommerce_get_template( 'single-product/tabs/tab-reviews.php' );
	}
}

/**
 * Product page tab panels
 **/
if ( ! function_exists( 'woocommerce_product_description_panel' ) ) {
	function woocommerce_product_description_panel() {
		woocommerce_get_template( 'single-product/tabs/description.php' );
	}
}
if ( ! function_exists( 'woocommerce_product_attributes_panel' ) ) {
	function woocommerce_product_attributes_panel() {
		woocommerce_get_template( 'single-product/tabs/attributes.php' );
	}
}
if ( ! function_exists( 'woocommerce_product_reviews_panel' ) ) {
	function woocommerce_product_reviews_panel() {
		woocommerce_get_template( 'single-product/tabs/reviews.php' );
	}
}

/**
 * Review comments template
 **/
if ( ! function_exists( 'woocommerce_comments' ) ) {
	function woocommerce_comments( $comment, $args, $depth ) {
		$GLOBALS['comment'] = $comment;
		woocommerce_get_template( 'single-product/review.php' );
	}
}

/**
 * WooCommerce Related Products
 **/
if ( ! function_exists( 'woocommerce_output_related_products' ) ) {
	function woocommerce_output_related_products() {
		woocommerce_related_products( 2, 2  );
	}
}

if ( ! function_exists( 'woocommerce_related_products' ) ) {
	function woocommerce_related_products( $posts_per_page = 4, $columns = 4, $orderby = 'rand'  ) {
		woocommerce_get_template( 'single-product/related.php', array(
				'posts_per_page'  => $posts_per_page,
				'orderby'    => $orderby,
				'columns'    => $columns
			) );
	}
}

/**
 * Display Up Sells
 **/
if ( ! function_exists( 'woocommerce_upsell_display' ) ) {
	function woocommerce_upsell_display() {
		woocommerce_get_template( 'single-product/up-sells.php' );
	}
}

/** Cart ******************************************************************/

/**
 * WooCommerce Shipping Calculator
 **/
if ( ! function_exists( 'woocommerce_shipping_calculator' ) ) {
	function woocommerce_shipping_calculator() {
		woocommerce_get_template( 'cart/shipping-calculator.php' );
	}
}

/**
 * WooCommerce Cart totals
 **/
if ( ! function_exists( 'woocommerce_cart_totals' ) ) {
	function woocommerce_cart_totals() {
		woocommerce_get_template( 'cart/totals.php' );
	}
}

/**
 * Display Cross Sells
 **/
if ( ! function_exists( 'woocommerce_cross_sell_display' ) ) {
	function woocommerce_cross_sell_display() {
		woocommerce_get_template( 'cart/cross-sells.php' );
	}
}

/** Mini-Cart *************************************************************/

/**
 * Mini-cart template - used by cart widget
 **/
if ( ! function_exists( 'woocommerce_mini_cart' ) ) {
	function woocommerce_mini_cart( $args = array() ) {

		$defaults = array(
			'list_class' => ''
		);

		$args = wp_parse_args( $args, $defaults );

		woocommerce_get_template( 'cart/mini-cart.php', $args );
	}
}

/** Login *****************************************************************/

/**
 * WooCommerce Login Form
 **/
if ( ! function_exists( 'woocommerce_login_form' ) ) {
	function woocommerce_login_form( $args = array() ) {

		$defaults = array(
			'message' => '',
			'redirect' => ''
		);

		$args = wp_parse_args( $args, $defaults  );

		woocommerce_get_template( 'shop/form-login.php', $args );
	}
}

/**
 * WooCommerce Checkout Login Form
 **/
if ( ! function_exists( 'woocommerce_checkout_login_form' ) ) {
	function woocommerce_checkout_login_form() {
		woocommerce_get_template( 'checkout/form-login.php' );
	}
}

/**
 * WooCommerce Breadcrumb
 **/
if ( ! function_exists( 'woocommerce_breadcrumb' ) ) {
	function woocommerce_breadcrumb( $args = array() ) {

		$defaults = array(
			'delimiter'  => ' &rsaquo; ',
			'wrap_before'  => '<div id="breadcrumb" itemprop="breadcrumb">',
			'wrap_after' => '</div>',
			'before'   => '',
			'after'   => '',
			'home'    => null
		);

		$args = wp_parse_args( $args, $defaults  );

		woocommerce_get_template( 'shop/breadcrumb.php', $args );
	}
}

/**
 * Order review table for checkout
 **/
if ( ! function_exists( 'woocommerce_order_review' ) ) {
	function woocommerce_order_review() {
		woocommerce_get_template( 'checkout/review-order.php' );
	}
}

/**
 * Coupon form for checkout
 **/
if ( ! function_exists( 'woocommerce_checkout_coupon_form' ) ) {
	function woocommerce_checkout_coupon_form() {
		woocommerce_get_template( 'checkout/form-coupon.php' );
	}
}

/**
 * display product sub categories as thumbnails
 **/
if ( ! function_exists( 'woocommerce_product_subcategories' ) ) {
	function woocommerce_product_subcategories( $args = array() ) {
		global $woocommerce, $wp_query, $_chosen_attributes;

		$defaults = array(
			'before'  => '',
			'after'  => '',
			'force_display' => false
		);

		$args = wp_parse_args( $args, $defaults );

		extract( $args );

		// Main query only
		if ( ! is_main_query() && ! $force_display ) return;

		// Don't show when filtering
		if ( sizeof( $_chosen_attributes ) > 0 || ( isset( $_GET['max_price'] ) && isset( $_GET['min_price'] ) ) ) return;

		// Don't show when searching or when on page > 1 and ensure we're on a product archive
		if ( is_search() || is_paged() || ( ! is_product_category() && ! is_shop() ) ) return;

		// Check cateogries are enabled
		if ( is_product_category() && get_option( 'woocommerce_show_subcategories' ) == 'no' ) return;
		if ( is_shop() && get_option( 'woocommerce_shop_show_subcategories' ) == 'no' ) return;

		// Find the category + category parent, if applicable
		if ( $product_cat_slug = get_query_var( 'product_cat' ) ) {
			$product_cat = get_term_by( 'slug', $product_cat_slug, 'product_cat' );
			$product_category_parent = $product_cat->term_id;
		} else {
			$product_category_parent = 0;
		}

		// NOTE: using child_of instead of parent - this is not ideal but due to a WP bug ( http://core.trac.wordpress.org/ticket/15626 ) pad_counts won't work
		$args = array(
			'child_of'		=> $product_category_parent,
			'menu_order'	=> 'ASC',
			'hide_empty'	=> 1,
			'hierarchical'	=> 1,
			'taxonomy'		=> 'product_cat',
			'pad_counts'	=> 1
		);
		$product_categories = get_categories( $args  );

		$product_category_found = false;

		if ( $product_categories ) {

			foreach ( $product_categories as $category ) {

				if ( $category->parent != $product_category_parent )
					continue;

				if ( ! $product_category_found ) {
					// We found a category
					$product_category_found = true;
					echo $before;
				}

				woocommerce_get_template( 'content-product_cat.php', array(
					'category' => $category
				) );

			}

		}

		// If we are hiding products disable the loop and pagination
		if ( $product_category_found == true && get_option( 'woocommerce_hide_products_when_showing_subcategories' ) == 'yes' ) {
			$wp_query->post_count = 0;
			$wp_query->max_num_pages = 0;
		}

		if ( $product_category_found ) {
			echo $after;
			return true;
		}

	}
}

/**
 * Show subcategory thumbnail
 **/
if ( ! function_exists( 'woocommerce_subcategory_thumbnail' ) ) {
	function woocommerce_subcategory_thumbnail( $category  ) {
		global $woocommerce;

		$small_thumbnail_size  = apply_filters( 'single_product_small_thumbnail_size', 'shop_catalog' );
		$image_width    = $woocommerce->get_image_size( 'shop_catalog_image_width' );
		$image_height    = $woocommerce->get_image_size( 'shop_catalog_image_height' );

		$thumbnail_id  = get_woocommerce_term_meta( $category->term_id, 'thumbnail_id', true  );

		if ( $thumbnail_id ) {
			$image = wp_get_attachment_image_src( $thumbnail_id, $small_thumbnail_size  );
			$image = $image[0];
		} else {
			$image = woocommerce_placeholder_img_src();
		}

		if ( $image )
			echo '<img src="' . $image . '" alt="' . $category->name . '" width="' . $image_width . '" height="' . $image_height . '" />';
	}
}

/**
 * Displays order details in a table
 **/
if ( ! function_exists( 'woocommerce_order_details_table' ) ) {
	function woocommerce_order_details_table( $order_id  ) {
		if ( ! $order_id ) return;

		woocommerce_get_template( 'order/order-details.php', array(
			'order_id' => $order_id
		) );
	}
}

/** Forms ****************************************************************/

/**
 * Outputs a checkout/address form field
 */
if ( ! function_exists( 'woocommerce_form_field' ) ) {
	function woocommerce_form_field( $key, $args, $value = ''  ) {
		global $woocommerce;

		$defaults = array(
			'type' => 'text',
			'label' => '',
			'placeholder' => '',
			'required' => false,
			'class' => array() ,
			'label_class' => array() ,
			'return' => false,
			'options' => array()
		);

		$args = wp_parse_args( $args, $defaults  );

		if ( ( isset( $args['clear'] ) && $args['clear'] ) ) $after = '<div class="clear"></div>'; else $after = '';

		$required = ( $args['required']  ) ? ' <abbr class="required" title="' . esc_attr__( 'required', 'woocommerce'  ) . '">*</abbr>' : '';

		switch ( $args['type'] ) {
		case "country" :

			$field = '<p class="form-row ' . implode( ' ', $args['class'] ) .'" id="' . $key . '_field">
					<label for="' . $key . '" class="' . implode( ' ', $args['label_class'] ) .'">' . $args['label']. $required  . '</label>
					<select name="' . $key . '" id="' . $key . '" class="country_to_state ' . implode( ' ', $args['class'] ) .'">
						<option value="">'.__( 'Select a country&hellip;', 'woocommerce' ) .'</option>';

			foreach ( $woocommerce->countries->get_allowed_countries() as $ckey => $cvalue ) {
				$field .= '<option value="' . $ckey . '" '.selected( $value, $ckey, false ) .'>'.__( $cvalue, 'woocommerce' ) .'</option>';
			}

			$field .= '</select>';

			$field .= '<noscript><input type="submit" name="woocommerce_checkout_update_totals" value="' . __('Update country', 'woocommerce') . '" /></noscript>';

			$field .= '</p>' . $after;

			break;
		case "state" :

			/* Get Country */
			$country_key = $key == 'billing_state'? 'billing_country' : 'shipping_country';

			if ( isset( $_POST[ $country_key ] ) ) {
				$current_cc = woocommerce_clean( $_POST[ $country_key ] );
			} elseif ( is_user_logged_in() ) {
				$current_cc = get_user_meta( get_current_user_id() , $country_key, true );
			} elseif ( $country_key == 'billing_country' ) {
				$current_cc = apply_filters('default_checkout_country', ($woocommerce->customer->get_country()) ? $woocommerce->customer->get_country() : $woocommerce->countries->get_base_country());
			} else {
				$current_cc 	= apply_filters('default_checkout_country', ($woocommerce->customer->get_shipping_country()) ? $woocommerce->customer->get_shipping_country() : $woocommerce->countries->get_base_country());
			}

			$states = $woocommerce->countries->get_states( $current_cc );

			if ( is_array( $states ) && empty( $states ) ) {

				$field  = '<p class="form-row ' . implode( ' ', $args['class'] ) .'" id="' . $key . '_field" style="display: none">';
				$field .= '<label for="' . $key . '" class="' . implode( ' ', $args['label_class'] ) .'">' . $args['label']. $required . '</label>';
				$field .= '<input type="hidden" class="hidden" name="' . $key  . '" id="' . $key . '" value="" />';
				$field .= '</p>' . $after;

			} elseif ( is_array( $states ) ) {

				$field  = '<p class="form-row ' . implode( ' ', $args['class'] ) .'" id="' . $key . '_field">';
				$field .= '<label for="' . $key . '" class="' . implode( ' ', $args['label_class'] ) .'">' . $args['label']. $required . '</label>';
				$field .= '<select name="' . $key . '" id="' . $key . '" class="state_select">
					<option value="">'.__( 'Select a state&hellip;', 'woocommerce' ) .'</option>';

				foreach ( $states as $ckey => $cvalue )
					$field .= '<option value="' . $ckey . '" '.selected( $value, $ckey, false ) .'>'.__( $cvalue, 'woocommerce' ) .'</option>';

				$field .= '</select>';
				$field .= '</p>' . $after;

			} else {

				$field  = '<p class="form-row ' . implode( ' ', $args['class'] ) .'" id="' . $key . '_field">';
				$field .= '<label for="' . $key . '" class="' . implode( ' ', $args['label_class'] ) .'">' . $args['label']. $required . '</label>';
				$field .= '<input type="text" class="input-text" value="' . $value . '"  placeholder="' . $args['placeholder'] . '" name="' . $key . '" id="' . $key . '" />';
				$field .= '</p>' . $after;

			}

			break;
		case "textarea" :

			$field = '<p class="form-row ' . implode( ' ', $args['class'] ) .'" id="' . $key . '_field">
					<label for="' . $key . '" class="' . implode( ' ', $args['label_class'] ) .'">' . $args['label']. $required  . '</label>
					<textarea name="' . $key . '" class="input-text" id="' . $key . '" placeholder="' . $args['placeholder'] . '" cols="5" rows="2">'. esc_textarea( $value  ) .'</textarea>
				</p>' . $after;

			break;
		case "checkbox" :

			$field = '<p class="form-row ' . implode( ' ', $args['class'] ) .'" id="' . $key . '_field">
					<input type="' . $args['type'] . '" class="input-checkbox" name="' . $key . '" id="' . $key . '" value="1" '.checked( $value, 1, false ) .' />
					<label for="' . $key . '" class="checkbox ' . implode( ' ', $args['label_class'] ) .'">' . $args['label'] . $required . '</label>
				</p>' . $after;

			break;
		case "password" :

			$field = '<p class="form-row ' . implode( ' ', $args['class'] ) .'" id="' . $key . '_field">
					<label for="' . $key . '" class="' . implode( ' ', $args['label_class'] ) .'">' . $args['label']. $required . '</label>
					<input type="password" class="input-text" name="' . $key . '" id="' . $key . '" placeholder="' . $args['placeholder'] . '" value="'. $value.'" />
				</p>' . $after;

			break;
		case "text" :

			$field = '<p class="form-row ' . implode( ' ', $args['class'] ) .'" id="' . $key . '_field">
					<label for="' . $key . '" class="' . implode( ' ', $args['label_class'] ) .'">' . $args['label'] . $required . '</label>
					<input type="text" class="input-text" name="' . $key . '" id="' . $key . '" placeholder="' . $args['placeholder'] . '" value="'. $value.'" />
				</p>' . $after;

			break;
		case "select" :

			$options = '';

			if ( ! empty( $args['options'] ) )
				foreach ( $args['options'] as $option_key => $option_text )
					$options .= '<option value="' . $option_key . '" '. selected( $value, $option_key, false ) . '>' . $option_text .'</option>';

				$field = '<p class="form-row ' . implode( ' ', $args['class'] ) .'" id="' . $key . '_field">
					<label for="' . $key . '" class="' . implode( ' ', $args['label_class'] ) .'">' . $args['label']. $required . '</label>
					<select name="' . $key . '" id="' . $key . '" class="select">
						' . $options . '
					</select>
				</p>' . $after;

			break;
		default :

			$field = apply_filters( 'woocommerce_form_field_' . $args['type'], '', $key, $args, $value  );

			break;
		}

		if ( $args['return'] ) return $field; else echo $field;
	}
}

/**
 * Product search forms
 */
function get_product_search_form( $echo = true  ) {
	do_action( 'get_product_search_form'  );

	$search_form_template = locate_template( 'product-searchform.php' );
	if ( '' != $search_form_template  ) {
		require $search_form_template;
		return;
	}

	$form = '<form role="search" method="get" id="searchform" action="' . esc_url( home_url( '/'  ) ) . '">
		<div>
			<label class="screen-reader-text" for="s">' . __( 'Search for:', 'woocommerce' ) . '</label>
			<input type="text" value="' . get_search_query() . '" name="s" id="s" placeholder="' . __( 'Search for products', 'woocommerce' ) . '" />
			<input type="submit" id="searchsubmit" value="'. esc_attr__( 'Search' ) .'" />
			<input type="hidden" name="post_type" value="product" />
		</div>
	</form>';

	if ( $echo  )
		echo apply_filters( 'get_product_search_form', $form );
	else
		return apply_filters( 'get_product_search_form', $form );

}