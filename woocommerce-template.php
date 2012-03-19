<?php
/**
 * WooCommerce Template Functions
 *
 * Functions used in the template files to output content - in most cases hooked in via the template actions. All functions are pluggable.
 *
 * @package		WooCommerce
 * @category	Core
 * @author		WooThemes
 */

/** Template pages ********************************************************/

if (!function_exists('woocommerce_content')) {
	// This function is only used in the optional 'woocommerce.php' template
	// people can add to their themes to add basic woocommerce support.
	function woocommerce_content() {
		if ( is_singular('product') ) 
			woocommerce_single_product_content();
		elseif ( is_tax('product_cat') || is_tax('product_tag') )
			woocommerce_product_taxonomy_content();
		else
			woocommerce_archive_product_content();
	}
}
if (!function_exists('woocommerce_archive_product_content')) {
	function woocommerce_archive_product_content() {
		
		if (!is_search()) {
			$shop_page = get_post( woocommerce_get_page_id('shop') );
			$shop_page_title = apply_filters('the_title', (get_option('woocommerce_shop_page_title')) ? get_option('woocommerce_shop_page_title') : $shop_page->post_title);
			if( is_object( $shop_page ) ) $shop_page_content = $shop_page->post_content;
    } else {
			$shop_page_title = __('Search Results:', 'woocommerce') . ' &ldquo;' . get_search_query() . '&rdquo;'; 
			if (get_query_var('paged')) $shop_page_title .= ' &mdash; ' . __('Page', 'woocommerce') . ' ' . get_query_var('paged');
			$shop_page_content = '';
    }
		
		?><h1 class="page-title"><?php echo $shop_page_title ?></h1>
		
		<?php if( ! empty( $shop_page_content ) ) echo apply_filters('the_content', $shop_page_content); ?>
		
		<?php woocommerce_get_template_part( 'loop', 'shop' ); ?>
		
		<?php do_action('woocommerce_pagination'); 
		
	}
}
if (!function_exists('woocommerce_product_taxonomy_content')) {
	function woocommerce_product_taxonomy_content() { 
		
		global $wp_query; 
		
		$term = get_term_by( 'slug', get_query_var($wp_query->query_vars['taxonomy']), $wp_query->query_vars['taxonomy']);
		
		?><h1 class="page-title"><?php echo wptexturize($term->name); ?></h1>
			
		<?php if ($term->description) : ?>
		
			<div class="term_description"><?php echo wpautop(wptexturize($term->description)); ?></div>
			
		<?php endif; ?>
		
		<?php woocommerce_get_template_part( 'loop', 'shop' ); ?>
		
		<?php do_action('woocommerce_pagination');
	
	}
}
if (!function_exists('woocommerce_single_product_content')) {
	function woocommerce_single_product_content( $wc_query = false ) {
		
		// Let developers override the query used, in case they want to use this function for their own loop/wp_query
		if (!$wc_query) {
			global $wp_query;
			
			$wc_query = $wp_query;
		}
		
		if ( $wc_query->have_posts() ) while ( $wc_query->have_posts() ) : $wc_query->the_post(); ?>
			
			<?php do_action('woocommerce_before_single_product'); ?>
		
			<div itemscope itemtype="http://schema.org/Product" id="product-<?php the_ID(); ?>" <?php post_class(); ?>>
				
				<?php do_action('woocommerce_before_single_product_summary'); ?>
				
				<div class="summary">
					
					<?php do_action( 'woocommerce_single_product_summary'); ?>
		
				</div>
				
				<?php do_action('woocommerce_after_single_product_summary'); ?>
		
			</div>

			<?php do_action('woocommerce_after_single_product'); ?>
			
		<?php endwhile;
	
	}
}

/** Global ****************************************************************/

if (!function_exists('woocommerce_output_content_wrapper')) {
	function woocommerce_output_content_wrapper() {
		woocommerce_get_template('shop/wrapper-start.php');
	}
}
if (!function_exists('woocommerce_output_content_wrapper_end')) {
	function woocommerce_output_content_wrapper_end() {
		woocommerce_get_template('shop/wrapper-end.php');
	}
}

/**
 * Messages
 **/
if (!function_exists('woocommerce_show_messages')) {
	function woocommerce_show_messages() {
		global $woocommerce;
		
		if ( $woocommerce->error_count() > 0 ) {
			
			woocommerce_get_template('shop/errors.php', array(
				'errors' => $woocommerce->get_errors()
			));
			
		} elseif ( $woocommerce->message_count() > 0 ) {
		
			woocommerce_get_template('shop/messages.php', array(
				'messages' => $woocommerce->get_messages()
			));
		
		}
		
		$woocommerce->clear_messages();
	}
}

/**
 * Sidebar
 **/
if (!function_exists('woocommerce_get_sidebar')) {
	function woocommerce_get_sidebar() {
		woocommerce_get_template('shop/sidebar.php');
	}
}

/**
 * Prevent Cache
 **/
if (!function_exists('woocommerce_prevent_sidebar_cache')) {
	function woocommerce_prevent_sidebar_cache( $sidebar ) {
		echo '<!--mfunc get_sidebar("'.$sidebar.'") --><!--/mfunc-->';
	}
}

/**
 * Demo Banner
 *
 * Adds a demo store banner to the site if enabled
 **/
if (!function_exists('woocommerce_demo_store')) {
	function woocommerce_demo_store() {
		if (get_option('woocommerce_demo_store')=='no') return;
		
		echo apply_filters('woocommerce_demo_store', '<p class="demo_store">'.__('This is a demo store for testing purposes &mdash; no orders shall be fulfilled.', 'woocommerce').'</p>' );
	}
}

/** Loop ******************************************************************/

/**
 * Products Loop
 **/
if (!function_exists('woocommerce_template_loop_add_to_cart')) {
	function woocommerce_template_loop_add_to_cart() {
		woocommerce_get_template('loop/add-to-cart.php');
	}
}
if (!function_exists('woocommerce_template_loop_product_thumbnail')) {
	function woocommerce_template_loop_product_thumbnail() {
		echo woocommerce_get_product_thumbnail();
	}
}
if (!function_exists('woocommerce_template_loop_price')) {
	function woocommerce_template_loop_price() {
		woocommerce_get_template('loop/price.php');
	}
}
if (!function_exists('woocommerce_show_product_loop_sale_flash')) {
	function woocommerce_show_product_loop_sale_flash() {
		woocommerce_get_template('loop/sale-flash.php');
	}
}

/**
 * WooCommerce Product Thumbnail
 **/
if (!function_exists('woocommerce_get_product_thumbnail')) {
	function woocommerce_get_product_thumbnail( $size = 'shop_catalog', $placeholder_width = 0, $placeholder_height = 0 ) {
		global $post, $woocommerce;

		if (!$placeholder_width) $placeholder_width = $woocommerce->get_image_size('shop_catalog_image_width');
		if (!$placeholder_height) $placeholder_height = $woocommerce->get_image_size('shop_catalog_image_height');

		if ( has_post_thumbnail() ) return get_the_post_thumbnail($post->ID, $size); else return '<img src="'. woocommerce_placeholder_img_src() .'" alt="Placeholder" width="'.$placeholder_width.'" height="'.$placeholder_height.'" />';
	}
}

/**
 * Pagination
 **/
if (!function_exists('woocommerce_pagination')) {
	function woocommerce_pagination() {
		woocommerce_get_template('loop/pagination.php');
	}
}

/**
 * Sorting
 **/
if (!function_exists('woocommerce_catalog_ordering')) {
	function woocommerce_catalog_ordering() {
		if (!isset($_SESSION['orderby'])) $_SESSION['orderby'] = apply_filters('woocommerce_default_catalog_orderby', get_option('woocommerce_default_catalog_orderby'));
		woocommerce_get_template('loop/sorting.php');
	}
}

/** Single Product ********************************************************/

/**
 * Before Single Products Summary Div
 **/
if (!function_exists('woocommerce_show_product_images')) {
	function woocommerce_show_product_images() {
		woocommerce_get_template('single-product/product-image.php');
	}
}
if (!function_exists('woocommerce_show_product_thumbnails')) {
	function woocommerce_show_product_thumbnails() {
		woocommerce_get_template('single-product/product-thumbnails.php');
	}
}
if (!function_exists('woocommerce_output_product_data_tabs')) {
	function woocommerce_output_product_data_tabs() {
		woocommerce_get_template('single-product/tabs.php');
	}
}
if (!function_exists('woocommerce_template_single_title')) {
	function woocommerce_template_single_title() {
		woocommerce_get_template('single-product/title.php');
	}
}
if (!function_exists('woocommerce_template_single_price')) {
	function woocommerce_template_single_price() {
		woocommerce_get_template('single-product/price.php');
	}
}
if (!function_exists('woocommerce_template_single_excerpt')) {
	function woocommerce_template_single_excerpt() {
		woocommerce_get_template('single-product/short-description.php');
	}
}
if (!function_exists('woocommerce_template_single_meta')) {
	function woocommerce_template_single_meta() {
		woocommerce_get_template('single-product/meta.php');
	}
}
if (!function_exists('woocommerce_template_single_sharing')) {
	function woocommerce_template_single_sharing() {
		woocommerce_get_template('single-product/share.php');
	}
}
if (!function_exists('woocommerce_show_product_sale_flash')) {
	function woocommerce_show_product_sale_flash() {
		woocommerce_get_template('single-product/sale-flash.php');
	}
}

/**
 * Product Add to cart buttons
 **/
if (!function_exists('woocommerce_template_single_add_to_cart')) {
	function woocommerce_template_single_add_to_cart() {
		global $product;
		do_action( 'woocommerce_' . $product->product_type . '_add_to_cart' );
	}
}
if (!function_exists('woocommerce_simple_add_to_cart')) {
	function woocommerce_simple_add_to_cart() {
		woocommerce_get_template('single-product/add-to-cart/simple.php');
	}
}
if (!function_exists('woocommerce_grouped_add_to_cart')) {
	function woocommerce_grouped_add_to_cart() {
		woocommerce_get_template('single-product/add-to-cart/grouped.php');
	}
}
if (!function_exists('woocommerce_variable_add_to_cart')) {
	function woocommerce_variable_add_to_cart() {
		global $woocommerce, $product, $post;

		$attributes = $product->get_available_attribute_variations();
		$default_attributes = (array) maybe_unserialize(get_post_meta( $post->ID, '_default_attributes', true ));
		$selected_attributes = apply_filters( 'woocommerce_product_default_attributes', $default_attributes );
		
		// Put available variations into an array and put in a Javascript variable (JSON encoded)
		$available_variations = array();
		
		foreach($product->get_children() as $child_id) {
		
		    $variation = $product->get_child( $child_id );
		
		    if($variation instanceof WC_Product_Variation) {
		
		    	if (get_post_status( $variation->get_variation_id() ) != 'publish') continue; // Disabled
		    	
		    	if (!$variation->is_visible()) continue; // Visible setting - may be hidden if out of stock
		
		        $variation_attributes = $variation->get_variation_attributes();
		        $availability = $variation->get_availability();
		        $availability_html = (!empty($availability['availability'])) ? apply_filters( 'woocommerce_stock_html', '<p class="stock '.$availability['class'].'">'. $availability['availability'].'</p>', $availability['availability'] ) : '';
		
		        if (has_post_thumbnail($variation->get_variation_id())) {
		            $attachment_id = get_post_thumbnail_id( $variation->get_variation_id() );
		            $large_thumbnail_size = apply_filters('single_product_large_thumbnail_size', 'shop_single');
		            $image = current(wp_get_attachment_image_src( $attachment_id, $large_thumbnail_size ));
		            $image_link = current(wp_get_attachment_image_src( $attachment_id, 'full' ));
		        } else {
		            $image = '';
		            $image_link = '';
		        }
		
		        $available_variations[] = apply_filters('woocommerce_available_variation', array(
		            'variation_id' => $variation->get_variation_id(),
		            'attributes' => $variation_attributes,
		            'image_src' => $image,
		            'image_link' => $image_link,
		            'price_html' => '<span class="price">'.$variation->get_price_html().'</span>',
		            'availability_html' => $availability_html,
		            'sku' => __('SKU:', 'woocommerce') . ' ' . $variation->sku,
		            'min_qty' => 1,
		            'max_qty' => $variation->stock,
		            'is_downloadable' => $variation->is_downloadable(),
		            'is_virtual' => $variation->is_virtual()
		        ), $product, $variation);
		    }
		}
		woocommerce_get_template('single-product/add-to-cart/variable.php', array(
			'available_variations' 	=> $available_variations,
			'attributes'			=> $attributes,
			'selected_attributes'	=> $selected_attributes,
		));
	}
}
if (!function_exists('woocommerce_external_add_to_cart')) {
	function woocommerce_external_add_to_cart() {
		global $product;
		
		$product_url = get_post_meta( $product->id, '_product_url', true );
		$button_text = get_post_meta( $product->id, '_button_text', true );
		
		if (!$product_url) return;

		woocommerce_get_template('single-product/add-to-cart/external.php', array(
			'product_url' => $product_url,
			'button_text' => ($button_text) ? $button_text : __('Buy product', 'woocommerce'),
		));
	}
}

/**
 * Quantity inputs
 **/
if (!function_exists('woocommerce_quantity_input')) {
	function woocommerce_quantity_input( $args = array() ) {
		$defaults = array(
			'input_name' 	=> 'quantity',
			'input_value' 	=> '1',
			'max_value'		=> '',
			'min_value'		=> '0'
		);

		$args = apply_filters('woocommerce_quantity_input_args', wp_parse_args( $args, $defaults ));
		
		woocommerce_get_template('single-product/add-to-cart/quantity.php', $args);
	}
}

/**
 * Product page tabs
 **/
if (!function_exists('woocommerce_product_description_tab')) {
	function woocommerce_product_description_tab() {
		woocommerce_get_template('single-product/tabs/tab-description.php');
	}
}
if (!function_exists('woocommerce_product_attributes_tab')) {
	function woocommerce_product_attributes_tab() {
		woocommerce_get_template('single-product/tabs/tab-attributes.php');
	}
}
if (!function_exists('woocommerce_product_reviews_tab')) {
	function woocommerce_product_reviews_tab() {
		woocommerce_get_template('single-product/tabs/tab-reviews.php');
	}
}

/**
 * Product page tab panels
 **/
if (!function_exists('woocommerce_product_description_panel')) {
	function woocommerce_product_description_panel() {
		woocommerce_get_template('single-product/tabs/description.php');
	}
}
if (!function_exists('woocommerce_product_attributes_panel')) {
	function woocommerce_product_attributes_panel() {
		woocommerce_get_template('single-product/tabs/attributes.php');
	}
}
if (!function_exists('woocommerce_product_reviews_panel')) {
	function woocommerce_product_reviews_panel() {
		woocommerce_get_template('single-product/tabs/reviews.php');
	}
}

/**
 * Review comments template
 **/
if (!function_exists('woocommerce_comments')) {
	function woocommerce_comments($comment, $args, $depth) {
		$GLOBALS['comment'] = $comment;
		woocommerce_get_template('single-product/review.php');
	}
}

/**
 * WooCommerce Related Products
 **/
if (!function_exists('woocommerce_output_related_products')) {
	function woocommerce_output_related_products() {
		woocommerce_related_products( 2, 2 );
	}
}

if (!function_exists('woocommerce_related_products')) {
	function woocommerce_related_products( $posts_per_page = 4, $columns = 4, $orderby = 'rand' ) {
		woocommerce_get_template('single-product/related.php', array(
			'posts_per_page' 	=> $posts_per_page,
			'orderby' 			=> $orderby,
			'columns' 			=> $columns
		));
	}
}

/**
 * Display Up Sells
 **/
if (!function_exists('woocommerce_upsell_display')) {
	function woocommerce_upsell_display() {
		woocommerce_get_template('single-product/up-sells.php');
	}
}

/** Cart ******************************************************************/

/**
 * WooCommerce Shipping Calculator
 **/
if (!function_exists('woocommerce_shipping_calculator')) {
	function woocommerce_shipping_calculator() {
		woocommerce_get_template('cart/shipping-calculator.php');
	}
}

/**
 * WooCommerce Cart totals
 **/
if (!function_exists('woocommerce_cart_totals')) {
	function woocommerce_cart_totals() {
		woocommerce_get_template('cart/totals.php');
	}
}

/**
 * Display Cross Sells
 **/
if (!function_exists('woocommerce_cross_sell_display')) {
	function woocommerce_cross_sell_display() {
		woocommerce_get_template('cart/cross-sells.php');
	}
}

/** Login *****************************************************************/

/**
 * WooCommerce Login Form
 **/
if (!function_exists('woocommerce_login_form')) {
	function woocommerce_login_form( $args = array() ) {

		$defaults = array(
			'message' => '',
			'redirect' => ''
		);

		$args = wp_parse_args( $args, $defaults );
	
		woocommerce_get_template('shop/form-login.php', $args);
	}
}

/**
 * WooCommerce Checkout Login Form
 **/
if (!function_exists('woocommerce_checkout_login_form')) {
	function woocommerce_checkout_login_form() {
		woocommerce_get_template('checkout/form-login.php');
	}
}

/**
 * WooCommerce Breadcrumb
 **/
if (!function_exists('woocommerce_breadcrumb')) {
	function woocommerce_breadcrumb( $args = array() ) {
		
		$defaults = array(
			'delimiter' 	=> ' &rsaquo; ',
			'wrap_before' 	=> '<div id="breadcrumb">',
			'wrap_after'	=> '</div>',
			'before' 		=> '',
			'after' 		=> '',
			'home' 			=> null
		);

		$args = wp_parse_args( $args, $defaults );

		woocommerce_get_template('shop/breadcrumb.php', $args);
	}
}

/**
 * Order review table for checkout
 **/
if (!function_exists('woocommerce_order_review')) {
	function woocommerce_order_review() {
		woocommerce_get_template('checkout/review-order.php');
	}
}

/**
 * Coupon form for checkout
 **/
if (!function_exists('woocommerce_checkout_coupon_form')) {
	function woocommerce_checkout_coupon_form() {
		woocommerce_get_template('checkout/form-coupon.php');
	}
}

/**
 * display product sub categories as thumbnails
 **/
if (!function_exists('woocommerce_product_subcategories')) {
	function woocommerce_product_subcategories() {
		global $woocommerce, $woocommerce_loop, $wp_query, $wp_the_query, $_chosen_attributes, $product_category_found;
	
		if ($wp_query !== $wp_the_query) return; // Detect main query
		if (sizeof($_chosen_attributes)>0 || (isset($_GET['max_price']) && isset($_GET['min_price']))) return; // Don't show when filtering
		if (is_search()) return;
		if (!is_product_category() && !is_shop()) return;
		if (is_product_category() && get_option('woocommerce_show_subcategories')=='no') return;
		if (is_shop() && get_option('woocommerce_shop_show_subcategories')=='no') return;
		if (is_paged()) return;
	
		if ($product_cat_slug = get_query_var('product_cat')) :
			$product_cat 				= get_term_by('slug', $product_cat_slug, 'product_cat');
			$product_category_parent 	= $product_cat->term_id;
		else :
			$product_category_parent 	= 0;
		endif;
	
		// NOTE: using child_of instead of parent - this is not ideal but due to a WP bug (http://core.trac.wordpress.org/ticket/15626) pad_counts won't work
		$args = array(
		    'child_of'                  => $product_category_parent,
		    'menu_order'                => 'ASC',
		    'hide_empty'               	=> 1,
		    'hierarchical'             	=> 1,
		    'taxonomy'                  => 'product_cat',
		    'pad_counts'				=> 1
		    );
		$product_categories = get_categories( $args );
	
		if ($product_categories) :
	
			woocommerce_get_template('loop-product-cats.php', array(
				'product_categories'		=> $product_categories,
				'product_category_parent' 	=> $product_category_parent
			));
			
			// If we are hiding products disable the loop and pagination
			if ($product_category_found==true && get_option('woocommerce_hide_products_when_showing_subcategories')=='yes') :
				$woocommerce_loop['show_products'] = false;
				$wp_query->max_num_pages = 0;
			endif;
	
		endif;
	}
}

/**
 * Show subcategory thumbnail
 **/
if (!function_exists('woocommerce_subcategory_thumbnail')) {
	function woocommerce_subcategory_thumbnail( $category ) {
		global $woocommerce;
	
		$small_thumbnail_size 	= apply_filters('single_product_small_thumbnail_size', 'shop_catalog');
		$image_width 			= $woocommerce->get_image_size('shop_catalog_image_width');
		$image_height 			= $woocommerce->get_image_size('shop_catalog_image_height');
	
		$thumbnail_id 	= get_woocommerce_term_meta( $category->term_id, 'thumbnail_id', true );
	
		if ($thumbnail_id) :
			$image = wp_get_attachment_image_src( $thumbnail_id, $small_thumbnail_size );
			$image = $image[0];
		else :
			$image = woocommerce_placeholder_img_src();
		endif;
	
		echo '<img src="'.$image.'" alt="'.$category->name.'" width="'.$image_width.'" height="'.$image_height.'" />';
	}
}

/**
 * Displays order details in a table
 **/
if (!function_exists('woocommerce_order_details_table')) {
	function woocommerce_order_details_table( $order_id ) {		
		if (!$order_id) return;

		woocommerce_get_template('order/order-details.php', array(
			'order_id' => $order_id
		));
	}	
}

/** Forms ****************************************************************/

/**
 * Outputs a checkout/address form field
 */
if (!function_exists('woocommerce_form_field')) {
	function woocommerce_form_field( $key, $args, $value = '' ) {
		global $woocommerce;
		
		$defaults = array(
			'type' => 'text',
			'label' => '',
			'placeholder' => '',
			'required' => false,
			'class' => array(),
			'label_class' => array(),
			'return' => false
		);
		
		$args = wp_parse_args( $args, $defaults );
		
		if ((isset($args['clear']) && $args['clear'])) $after = '<div class="clear"></div>'; else $after = '';
		
		$required = ( $args['required'] ) ? ' <abbr class="required" title="' . esc_attr__( 'required', 'woocommerce' ) . '">*</abbr>' : '';
		
		switch ($args['type']) :
			case "country" :
				
				$field = '<p class="form-row '.implode(' ', $args['class']).'" id="'.$key.'_field">
					<label for="'.$key.'" class="'.implode(' ', $args['label_class']).'">'.$args['label']. $required .'</label>
					<select name="'.$key.'" id="'.$key.'" class="country_to_state '.implode(' ', $args['class']).'">
						<option value="">'.__('Select a country&hellip;', 'woocommerce').'</option>';
				
				foreach($woocommerce->countries->get_allowed_countries() as $ckey=>$cvalue) :
					$field .= '<option value="'.$ckey.'" '.selected($value, $ckey, false).'>'.__($cvalue, 'woocommerce').'</option>';
				endforeach;
				
				$field .= '</select></p>'.$after;
	
			break;
			case "state" :
				
				$field = '<p class="form-row '.implode(' ', $args['class']).'" id="'.$key.'_field">
					<label for="'.$key.'" class="'.implode(' ', $args['label_class']).'">'.$args['label']. $required . '</label>';
				
				/* Get Country */
				$country_key = ($key=='billing_state') ? 'billing_country' : 'shipping_country';

				if (isset($_POST[$country_key])) :
					$current_cc = woocommerce_clean($_POST[$country_key]);
				elseif (is_user_logged_in()) :
					$current_cc = get_user_meta( get_current_user_id(), $country_key, true );
				else :
					$current_cc = apply_filters('default_checkout_country', ($woocommerce->customer->get_country()) ? $woocommerce->customer->get_country() : $woocommerce->countries->get_base_country());
				endif;

				$states = $woocommerce->countries->states;	
					
				if (isset( $states[$current_cc][$value] )) :
					// Dropdown
					$field .= '<select name="'.$key.'" id="'.$key.'" class="state_select"><option value="">'.__('Select a state&hellip;', 'woocommerce').'</option>';
					foreach($states[$current_cc] as $ckey=>$cvalue) :
						$field .= '<option value="'.$ckey.'" '.selected($value, $ckey, false).'>'.__($cvalue, 'woocommerce').'</option>';
					endforeach;
					$field .= '</select>';
				else :
					// Input
					$field .= '<input type="text" class="input-text" value="'.$value.'"  placeholder="'.$args['placeholder'].'" name="'.$key.'" id="'.$key.'" />';
				endif;
	
				$field .= '</p>'.$after;
				
			break;
			case "textarea" :
				
				$field = '<p class="form-row '.implode(' ', $args['class']).'" id="'.$key.'_field">
					<label for="'.$key.'" class="'.implode(' ', $args['label_class']).'">'.$args['label']. $required .'</label>
					<textarea name="'.$key.'" class="input-text" id="'.$key.'" placeholder="'.$args['placeholder'].'" cols="5" rows="2">'. esc_textarea( $value ).'</textarea>
				</p>'.$after;
				
			break;
			case "checkbox" :
				
				$field = '<p class="form-row '.implode(' ', $args['class']).'" id="'.$key.'_field">
					<input type="'.$args['type'].'" class="input-checkbox" name="'.$key.'" id="'.$key.'" value="1" '.checked($value, 1, false).' />
					<label for="'.$key.'" class="checkbox '.implode(' ', $args['label_class']).'">'.$args['label'] . $required . '</label>
				</p>'.$after;
				
			break;
			case "password" :
		
				$field = '<p class="form-row '.implode(' ', $args['class']).'" id="'.$key.'_field">
					<label for="'.$key.'" class="'.implode(' ', $args['label_class']).'">'.$args['label']. $required . '</label>
					<input type="password" class="input-text" name="'.$key.'" id="'.$key.'" placeholder="'.$args['placeholder'].'" value="'. $value.'" />
				</p>'.$after;

			break;
			case "text" :
			
				$field = '<p class="form-row '.implode(' ', $args['class']).'" id="'.$key.'_field">
					<label for="'.$key.'" class="'.implode(' ', $args['label_class']).'">'.$args['label']. $required . '</label>
					<input type="text" class="input-text" name="'.$key.'" id="'.$key.'" placeholder="'.$args['placeholder'].'" value="'. $value.'" />
				</p>'.$after;
				
			break;
			default :
			
				$field = apply_filters( 'woocommerce_form_field_' . $args['type'], '', $key, $args, $value );
			
			break;
		endswitch;

		if ($args['return']) return $field; else echo $field;
	}
}
