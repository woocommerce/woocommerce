<?php
/**
 * WooCommerce Template Functions
 *
 * Functions used in the template files to output content - in most cases hooked in via the template actions.
 *
 * @package		WooCommerce
 * @category	Core
 * @author		WooThemes
 */

/** Global ****************************************************************/

/**
 * Content Wrappers
 **/
if (!function_exists('woocommerce_output_content_wrapper')) {
	function woocommerce_output_content_wrapper() {
		$id = ( get_option('template') === 'twentyeleven' ) ? 'primary' : 'container';
		echo '<div id="'.$id.'"><div id="content" role="main">';
	}
}
if (!function_exists('woocommerce_output_content_wrapper_end')) {
	function woocommerce_output_content_wrapper_end() {
		echo '</div></div>';
	}
}

/**
 * Sidebar
 **/
if (!function_exists('woocommerce_get_sidebar')) {
	function woocommerce_get_sidebar() {
		get_sidebar('shop');
	}
}

/** Loop ******************************************************************/

/**
 * Products Loop
 **/
if (!function_exists('woocommerce_template_loop_add_to_cart')) {
	function woocommerce_template_loop_add_to_cart() {
		global $product, $post;
		
		// No price set - so no button
		if( $product->get_price() === '' && $product->product_type!=='external') return;

		if (!$product->is_in_stock()) :
			echo '<a href="'.get_permalink($post->ID).'" class="button">'. apply_filters('out_of_stock_add_to_cart_text', __('Read More', 'woothemes')).'</a>';
			return;
		endif;

		switch ($product->product_type) :
			case "variable" :
				$link 	= get_permalink($post->ID);
				$label 	= apply_filters('variable_add_to_cart_text', __('Select options', 'woothemes'));
			break;
			case "grouped" :
				$link 	= get_permalink($post->ID);
				$label 	= apply_filters('grouped_add_to_cart_text', __('View options', 'woothemes'));
			break;
			case "external" :
				$link 	= get_permalink($post->ID);
				$label 	= apply_filters('external_add_to_cart_text', __('Read More', 'woothemes'));
			break;
			default :
				$link 	= esc_url( $product->add_to_cart_url() );
				$label 	= apply_filters('add_to_cart_text', __('Add to cart', 'woothemes'));
			break;
		endswitch;

		echo sprintf('<a href="%s" data-product_id="%s" class="button add_to_cart_button product_type_%s">%s</a>', $link, $product->id, $product->product_type, $label);
	}
}
if (!function_exists('woocommerce_template_loop_product_thumbnail')) {
	function woocommerce_template_loop_product_thumbnail() {
		echo woocommerce_get_product_thumbnail();
	}
}
if (!function_exists('woocommerce_template_loop_price')) {
	function woocommerce_template_loop_price() {
		global $product;
		$price_html = $product->get_price_html();
		if (!$price_html) return;
		?><span class="price"><?php echo $price_html; ?></span><?php
	}
}
if (!function_exists('woocommerce_show_product_loop_sale_flash')) {
	function woocommerce_show_product_loop_sale_flash() {
		woocommerce_get_template('loop/sale_flash.php', false);
	}
}

/**
 * Check product visibility in loop
 **/
if (!function_exists('woocommerce_check_product_visibility')) {
	function woocommerce_check_product_visibility() {
		global $product;
		if (!$product->is_visible( true ) && $post->post_parent > 0) : wp_safe_redirect(get_permalink($post->post_parent)); exit; endif;
		if (!$product->is_visible( true )) : wp_safe_redirect(home_url()); exit; endif;
	}
}

/**
 * Pagination
 **/
if (!function_exists('woocommerce_pagination')) {
	function woocommerce_pagination() {
		woocommerce_get_template('loop/pagination.php', false);
	}
}

/**
 * Sorting
 **/
if (!function_exists('woocommerce_catalog_ordering')) {
	function woocommerce_catalog_ordering() {
		if (!isset($_SESSION['orderby'])) $_SESSION['orderby'] = apply_filters('woocommerce_default_catalog_orderby', 'title');
		woocommerce_get_template('loop/sorting.php', false);
	}
}

/** Single Product ********************************************************/

/**
 * Before Single Products Summary Div
 **/
if (!function_exists('woocommerce_show_product_images')) {
	function woocommerce_show_product_images() {
		woocommerce_get_template('single-product/product-image.php', false);
	}
}
if (!function_exists('woocommerce_show_product_thumbnails')) {
	function woocommerce_show_product_thumbnails() {
		woocommerce_get_template('single-product/product-thumbnails.php', false);
	}
}
if (!function_exists('woocommerce_output_product_data_tabs')) {
	function woocommerce_output_product_data_tabs() {
		woocommerce_get_template('single-product/tabs.php', false);
	}
}
if (!function_exists('woocommerce_template_single_price')) {
	function woocommerce_template_single_price() {
		woocommerce_get_template('single-product/price.php', false);
	}
}
if (!function_exists('woocommerce_template_single_excerpt')) {
	function woocommerce_template_single_excerpt() {
		woocommerce_get_template('single-product/short-description.php', false);
	}
}

if (!function_exists('woocommerce_template_single_meta')) {
	function woocommerce_template_single_meta() {
		woocommerce_get_template('single-product/meta.php', false);
	}
}
if (!function_exists('woocommerce_template_single_sharing')) {
	function woocommerce_template_single_sharing() {
		woocommerce_get_template('single-product/share.php', false);
	}
}
if (!function_exists('woocommerce_show_product_sale_flash')) {
	function woocommerce_show_product_sale_flash() {
		woocommerce_get_template('single-product/sale_flash.php', false);
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
		woocommerce_get_template('single-product/add-to-cart/simple.php', false);
	}
}
if (!function_exists('woocommerce_grouped_add_to_cart')) {
	function woocommerce_grouped_add_to_cart() {
		woocommerce_get_template('single-product/add-to-cart/grouped.php', false);
	}
}
if (!function_exists('woocommerce_variable_add_to_cart')) {
	function woocommerce_variable_add_to_cart() {
		global $woocommerce, $available_variations, $attributes, $selected_attributes, $product, $post;

		$attributes = $product->get_available_attribute_variations();
		$default_attributes = (array) maybe_unserialize(get_post_meta( $post->ID, '_default_attributes', true ));
		$selected_attributes = apply_filters( 'woocommerce_product_default_attributes', $default_attributes );
		
		// Put available variations into an array and put in a Javascript variable (JSON encoded)
		$available_variations = array();
		
		foreach($product->get_children() as $child_id) {
		
		    $variation = $product->get_child( $child_id );
		
		    if($variation instanceof woocommerce_product_variation) {
		
		    	if (get_post_status( $variation->get_variation_id() ) != 'publish') continue; // Disabled
		
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
		
		        $available_variations[] = array(
		            'variation_id' => $variation->get_variation_id(),
		            'attributes' => $variation_attributes,
		            'image_src' => $image,
		            'image_link' => $image_link,
		            'price_html' => '<span class="price">'.$variation->get_price_html().'</span>',
		            'availability_html' => $availability_html,
		        );
		    }
		}
		
		woocommerce_get_template('single-product/add-to-cart/variable.php', false);
	}
}
if (!function_exists('woocommerce_external_add_to_cart')) {
	function woocommerce_external_add_to_cart() {
		woocommerce_get_template('single-product/add-to-cart/external.php', false);
	}
}

/**
 * Quantity inputs
 **/
if (!function_exists('woocommerce_quantity_input')) {
	function woocommerce_quantity_input( $args = array() ) {
		global $input_name, $input_value;
		
		$defaults = array(
			'input_name' 	=> 'quantity',
			'input_value' 	=> '1'
		);

		$args = wp_parse_args( $args, $defaults );
					
		extract( $args );
		
		woocommerce_get_template('single-product/add-to-cart/quantity.php', false);
	}
}

/**
 * Product page tabs
 **/
if (!function_exists('woocommerce_product_description_tab')) {
	function woocommerce_product_description_tab() {
		?><li><a href="#tab-description"><?php _e('Description', 'woothemes'); ?></a></li><?php
	}
}
if (!function_exists('woocommerce_product_attributes_tab')) {
	function woocommerce_product_attributes_tab() {
		global $product;
		if ($product->has_attributes()) : ?><li><a href="#tab-attributes"><?php _e('Additional Information', 'woothemes'); ?></a></li><?php endif;
	}
}
if (!function_exists('woocommerce_product_reviews_tab')) {
	function woocommerce_product_reviews_tab() {
		if ( comments_open() ) : ?><li class="reviews_tab"><a href="#tab-reviews"><?php _e('Reviews', 'woothemes'); ?><?php echo comments_number(' (0)', ' (1)', ' (%)'); ?></a></li><?php endif;
	}
}

/**
 * Product page tab panels
 **/
if (!function_exists('woocommerce_product_description_panel')) {
	function woocommerce_product_description_panel() {
		woocommerce_get_template('single-product/tabs/description.php', false);
	}
}
if (!function_exists('woocommerce_product_attributes_panel')) {
	function woocommerce_product_attributes_panel() {
		woocommerce_get_template('single-product/tabs/attributes.php', false);
	}
}
if (!function_exists('woocommerce_product_reviews_panel')) {
	function woocommerce_product_reviews_panel() {
		woocommerce_get_template('single-product/tabs/reviews.php', false);
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

		if ( has_post_thumbnail() ) return get_the_post_thumbnail($post->ID, $size); else return '<img src="'.$woocommerce->plugin_url(). '/assets/images/placeholder.png" alt="Placeholder" width="'.$placeholder_width.'" height="'.$placeholder_height.'" />';

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
	function woocommerce_related_products( $posts_per_page = 4, $post_columns = 4, $orderby = 'rand' ) {
		global $product, $woocommerce_loop;

		// Pass vars to loop
		$woocommerce_loop['columns'] = $post_columns;

		$related = $product->get_related();
		if (sizeof($related)>0) :
			echo '<div class="related products"><h2>'.__('Related Products', 'woothemes').'</h2>';
			$args = array(
				'post_type'	=> 'product',
				'ignore_sticky_posts'	=> 1,
				'posts_per_page' => $posts_per_page,
				'orderby' => $orderby,
				'post__in' => $related
			);
			$args = apply_filters('woocommerce_related_products_args', $args);
			query_posts($args);
			woocommerce_get_template_part( 'loop', 'shop' );
			echo '</div>';
			wp_reset_query();
		endif;

	}
}

/** Cart ******************************************************************/

/**
 * WooCommerce Shipping Calculator
 **/
if (!function_exists('woocommerce_shipping_calculator')) {
	function woocommerce_shipping_calculator() {
		global $woocommerce;
		if (get_option('woocommerce_enable_shipping_calc')=='yes' && $woocommerce->cart->needs_shipping()) :
			woocommerce_get_template('cart/shipping_calculator.php', false);
		endif;
	}
}

/**
 * WooCommerce Cart totals
 **/
if (!function_exists('woocommerce_cart_totals')) {
	function woocommerce_cart_totals() {
		woocommerce_get_template('cart/totals.php', false);
	}
}

/** Login *****************************************************************/

/**
 * WooCommerce Login Form
 **/
if (!function_exists('woocommerce_login_form')) {
	function woocommerce_login_form( $message = '' ) {
		global $woocommerce;

		if (is_user_logged_in()) return;

		?>
		<form method="post" class="login">
			<?php if ($message) echo wpautop(wptexturize($message)); ?>
			<p class="form-row form-row-first">
				<label for="username"><?php _e('Username', 'woothemes'); ?> <span class="required">*</span></label>
				<input type="text" class="input-text" name="username" id="username" />
			</p>
			<p class="form-row form-row-last">
				<label for="password"><?php _e('Password', 'woothemes'); ?> <span class="required">*</span></label>
				<input class="input-text" type="password" name="password" id="password" />
			</p>
			<div class="clear"></div>

			<p class="form-row">
				<?php $woocommerce->nonce_field('login', 'login') ?>
				<input type="submit" class="button" name="login" value="<?php _e('Login', 'woothemes'); ?>" />
				<a class="lost_password" href="<?php echo esc_url( wp_lostpassword_url( home_url() ) ); ?>"><?php _e('Lost Password?', 'woothemes'); ?></a>
			</p>
		</form>
		<?php
	}
}

/**
 * WooCommerce Checkout Login Form
 **/
if (!function_exists('woocommerce_checkout_login_form')) {
	function woocommerce_checkout_login_form() {

		if (is_user_logged_in()) return;

		if (get_option('woocommerce_enable_signup_and_login_from_checkout')=="no") return;

		$info_message = apply_filters('woocommerce_checkout_login_message', __('Already registered?', 'woothemes'));

		?><p class="info"><?php echo $info_message; ?> <a href="#" class="showlogin"><?php _e('Click here to login', 'woothemes'); ?></a></p><?php

		woocommerce_login_form( __('If you have shopped with us before, please enter your username and password in the boxes below. If you are a new customer please proceed to the Billing &amp; Shipping section.', 'woothemes') );
	}
}

/**
 * WooCommerce Breadcrumb
 **/
if (!function_exists('woocommerce_breadcrumb')) {
	function woocommerce_breadcrumb( $delimiter = ' &rsaquo; ', $wrap_before = '<div id="breadcrumb">', $wrap_after = '</div>', $before = '', $after = '', $home = null ) {

	 	global $post, $wp_query, $author, $paged;

	 	if( !$home ) $home = _x('Home', 'breadcrumb', 'woothemes');

	 	$home_link = home_url();

	 	$prepend = '';

	 	if ( get_option('woocommerce_prepend_shop_page_to_urls')=="yes" && get_option('woocommerce_shop_page_id') && get_option('page_on_front') !== get_option('woocommerce_shop_page_id') )
	 		$prepend =  $before . '<a href="' . get_permalink( get_option('woocommerce_shop_page_id') ) . '">' . get_the_title( get_option('woocommerce_shop_page_id') ) . '</a> ' . $after . $delimiter;


	 	if ( (!is_home() && !is_front_page() && !(is_post_type_archive() && get_option('page_on_front')==get_option('woocommerce_shop_page_id'))) || is_paged() ) :

			echo $wrap_before;

			echo $before  . '<a class="home" href="' . $home_link . '">' . $home . '</a> '  . $after . $delimiter ;

			if ( is_category() ) :

	      		$cat_obj = $wp_query->get_queried_object();
	      		$this_category = $cat_obj->term_id;
	      		$this_category = get_category( $this_category );
	      		if ($thisCat->parent != 0) :
	      			$parent_category = get_category( $this_category->parent );
	      			echo get_category_parents($parent_category, TRUE, $delimiter );
	      		endif;
	      		echo $before . single_cat_title('', false) . $after;

	 		elseif ( is_tax('product_cat') ) :

	 			$term = get_term_by( 'slug', get_query_var( 'term' ), get_query_var( 'taxonomy' ) );

				$parents = array();
				$parent = $term->parent;
				while ($parent):
					$parents[] = $parent;
					$new_parent = get_term_by( 'id', $parent, get_query_var( 'taxonomy' ));
					$parent = $new_parent->parent;
				endwhile;
				if(!empty($parents)):
					$parents = array_reverse($parents);
					foreach ($parents as $parent):
						$item = get_term_by( 'id', $parent, get_query_var( 'taxonomy' ));
						echo $before .  '<a href="' . get_term_link( $item->slug, 'product_cat' ) . '">' . $item->name . '</a>' . $after . $delimiter;
					endforeach;
				endif;

	 			$queried_object = $wp_query->get_queried_object();
	      		echo $prepend . $before . $queried_object->name . $after;

	      	elseif ( is_tax('product_tag') ) :

	 			$queried_object = $wp_query->get_queried_object();
	      		echo $prepend . $before . __('Products tagged &ldquo;', 'woothemes') . $queried_object->name . '&rdquo;' . $after;

	 		elseif ( is_day() ) :

				echo $before . '<a href="' . get_year_link(get_the_time('Y')) . '">' . get_the_time('Y') . '</a>' . $after . $delimiter;
				echo $before . '<a href="' . get_month_link(get_the_time('Y'),get_the_time('m')) . '">' . get_the_time('F') . '</a>' . $after . $delimiter;
				echo $before . get_the_time('d') . $after;

			elseif ( is_month() ) :

				echo $before . '<a href="' . get_year_link(get_the_time('Y')) . '">' . get_the_time('Y') . '</a>' . $after . $delimiter;
				echo $before . get_the_time('F') . $after;

			elseif ( is_year() ) :

				echo $before . get_the_time('Y') . $after;

	 		elseif ( is_post_type_archive('product') && get_option('page_on_front') !== get_option('woocommerce_shop_page_id') ) :

	 			$_name = get_option('woocommerce_shop_page_id') ? get_the_title( get_option('woocommerce_shop_page_id') ) : ucwords(get_option('woocommerce_shop_slug'));

	 			if (is_search()) :

	 				echo $before . '<a href="' . get_post_type_archive_link('product') . '">' . $_name . '</a>' . $delimiter . __('Search results for &ldquo;', 'woothemes') . get_search_query() . '&rdquo;' . $after;

	 			else :

	 				echo $before . '<a href="' . get_post_type_archive_link('product') . '">' . $_name . '</a>' . $after;

	 			endif;

			elseif ( is_single() && !is_attachment() ) :

				if ( get_post_type() == 'product' ) :

	       			//echo $before . '<a href="' . get_post_type_archive_link('product') . '">' . ucwords(get_option('woocommerce_shop_slug')) . '</a>' . $after . $delimiter;
	       			echo $prepend;

	       			if ($terms = wp_get_object_terms( $post->ID, 'product_cat' )) :
						$term = current($terms);
						$parents = array();
						$parent = $term->parent;
						while ($parent):
							$parents[] = $parent;
							$new_parent = get_term_by( 'id', $parent, 'product_cat');
							$parent = $new_parent->parent;
						endwhile;
						if(!empty($parents)):
							$parents = array_reverse($parents);
							foreach ($parents as $parent):
								$item = get_term_by( 'id', $parent, 'product_cat');
								echo $before . '<a href="' . get_term_link( $item->slug, 'product_cat' ) . '">' . $item->name . '</a>' . $after . $delimiter;
							endforeach;
						endif;
						echo $before . '<a href="' . get_term_link( $term->slug, 'product_cat' ) . '">' . $term->name . '</a>' . $after . $delimiter;
					endif;

	        		echo $before . get_the_title() . $after;

				elseif ( get_post_type() != 'post' ) :
					$post_type = get_post_type_object(get_post_type());
	        		$slug = $post_type->rewrite;
	       			echo $before . '<a href="' . get_post_type_archive_link(get_post_type()) . '">' . $post_type->labels->singular_name . '</a>' . $after . $delimiter;
	        		echo $before . get_the_title() . $after;
				else :
					$cat = current(get_the_category());
					echo get_category_parents($cat, TRUE, $delimiter);
					echo $before . get_the_title() . $after;
				endif;

	 		elseif ( is_404() ) :

		    	echo $before . __('Error 404', 'woothemes') . $after;

	    	elseif ( !is_single() && !is_page() && get_post_type() != 'post' ) :

				$post_type = get_post_type_object(get_post_type());
				if ($post_type) : echo $before . $post_type->labels->singular_name . $after; endif;

			elseif ( is_attachment() ) :

				$parent = get_post($post->post_parent);
				$cat = get_the_category($parent->ID); $cat = $cat[0];
				echo get_category_parents($cat, TRUE, '' . $delimiter);
				echo $before . '<a href="' . get_permalink($parent) . '">' . $parent->post_title . '</a>' . $after . $delimiter;
				echo $before . get_the_title() . $after;

			elseif ( is_page() && !$post->post_parent ) :

				echo $before . get_the_title() . $after;

			elseif ( is_page() && $post->post_parent ) :

				$parent_id  = $post->post_parent;
				$breadcrumbs = array();
				while ($parent_id) {
					$page = get_page($parent_id);
					$breadcrumbs[] = '<a href="' . get_permalink($page->ID) . '">' . get_the_title($page->ID) . '</a>';
					$parent_id  = $page->post_parent;
				}
				$breadcrumbs = array_reverse($breadcrumbs);
				foreach ($breadcrumbs as $crumb) :
					echo $crumb . '' . $delimiter;
				endforeach;
				echo $before . get_the_title() . $after;

			elseif ( is_search() ) :

				echo $before . __('Search results for &ldquo;', 'woothemes') . get_search_query() . '&rdquo;' . $after;

			elseif ( is_tag() ) :

	      		echo $before . __('Posts tagged &ldquo;', 'woothemes') . single_tag_title('', false) . '&rdquo;' . $after;

			elseif ( is_author() ) :

				$userdata = get_userdata($author);
				echo $before . __('Author:', 'woothemes') . ' ' . $userdata->display_name . $after;

		    endif;

			if ( get_query_var('paged') ) :

				echo ' (' . __('Page', 'woothemes') . ' ' . get_query_var('paged') .')';

			endif;

	    	echo $wrap_after;

		endif;
	}
}

/**
 * Display Up Sells
 **/
function woocommerce_upsell_display() {
	global $product;
	$upsells = $product->get_upsells();
	if (sizeof($upsells)>0) :
		echo '<div class="upsells products"><h2>'.__('You may also like&hellip;', 'woothemes').'</h2>';
		$args = array(
			'post_type'	=> 'product',
			'ignore_sticky_posts'	=> 1,
			'posts_per_page' => 4,
			'orderby' => 'rand',
			'post__in' => $upsells
		);
		query_posts($args);
		woocommerce_get_template_part( 'loop', 'shop' );
		echo '</div>';
	endif;
	wp_reset_query();
}

/**
 * Display Cross Sells
 **/
function woocommerce_cross_sell_display() {
	global $woocommerce_loop, $woocommerce;
	$woocommerce_loop['columns'] = 2;
	$crosssells = $woocommerce->cart->get_cross_sells();

	if (sizeof($crosssells)>0) :
		echo '<div class="cross-sells"><h2>'.__('You may be interested in&hellip;', 'woothemes').'</h2>';
		$args = array(
			'post_type'	=> 'product',
			'ignore_sticky_posts'	=> 1,
			'posts_per_page' => 2,
			'orderby' => 'rand',
			'post__in' => $crosssells
		);
		query_posts($args);
		woocommerce_get_template_part( 'loop', 'shop' );
		echo '</div>';
	endif;
	wp_reset_query();
}

/**
 * Order review table for checkout
 **/
function woocommerce_order_review() {
	woocommerce_get_template('checkout/review_order.php', false);
}

/**
 * Demo Banner
 *
 * Adds a demo store banner to the site if enabled
 **/
function woocommerce_demo_store() {
	if (get_option('woocommerce_demo_store')=='no') return;
	
	echo apply_filters('woocommerce_demo_store', '<p class="demo_store">'.__('This is a demo store for testing purposes &mdash; no orders shall be fulfilled.', 'woothemes').'</p>' );
}

/**
 * display product sub categories as thumbnails
 **/
function woocommerce_product_subcategories() {
	global $woocommerce, $woocommerce_loop, $wp_query, $wp_the_query, $_chosen_attributes, $product_categories, $product_category_found, $product_category_parent;

	if ($wp_query !== $wp_the_query) return; // Detect main query

	if (sizeof($_chosen_attributes)>0 || (isset($_GET['max_price']) && isset($_GET['min_price']))) return; // Don't show when filtering

	if (is_search()) return;
	if (!is_product_category() && !is_shop()) return;
	if (is_product_category() && get_option('woocommerce_show_subcategories')=='no') return;
	if (is_shop() && get_option('woocommerce_shop_show_subcategories')=='no') return;
	if (is_paged()) return;

	$product_cat_slug 	= get_query_var('product_cat');

	if ($product_cat_slug) :
		$product_cat 		= get_term_by('slug', $product_cat_slug, 'product_cat');
		$product_category_parent = $product_cat->term_id;
	else :
		$product_category_parent = 0;
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

		woocommerce_get_template('loop-product-cats.php', false);
		
		// If we are hiding products disable the loop and pagination
		if ($product_category_found==true && get_option('woocommerce_hide_products_when_showing_subcategories')=='yes') :
			$woocommerce_loop['show_products'] = false;
			$wp_query->max_num_pages = 0;
		endif;

	endif;
}

/**
 * Show subcategory thumbnail
 **/
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
		$image = $woocommerce->plugin_url().'/assets/images/placeholder.png';
	endif;

	echo '<img src="'.$image.'" alt="'.$category->slug.'" width="'.$image_width.'" height="'.$image_height.'" />';
}

/**
 * Displays order details in a table
 **/
function woocommerce_order_details_table( $id ) {
	global $woocommerce, $order_id; 
	
	if (!$id) return;

	$order_id = $id;
	
	woocommerce_get_template('order/order-details-table.php', false);
}	

/**
 * Review comments template
 **/
function woocommerce_comments($comment, $args, $depth) {
	$GLOBALS['comment'] = $comment; global $post; ?>
	
	<li itemprop="reviews" itemscope itemtype="http://schema.org/Review" <?php comment_class(); ?> id="li-comment-<?php comment_ID() ?>">
		<div id="comment-<?php comment_ID(); ?>" class="comment_container">

  			<?php echo get_avatar( $comment, $size='60' ); ?>
			
			<div class="comment-text">
			
				<div itemprop="reviewRating" itemscope itemtype="http://schema.org/Rating" class="star-rating" title="<?php echo esc_attr( get_comment_meta( $comment->comment_ID, 'rating', true ) ); ?>">
					<span style="width:<?php echo get_comment_meta( $comment->comment_ID, 'rating', true )*16; ?>px"><span itemprop="ratingValue"><?php echo get_comment_meta( $comment->comment_ID, 'rating', true ); ?></span> <?php _e('out of 5', 'woothemes'); ?></span>
				</div>
				
				<?php if ($comment->comment_approved == '0') : ?>
					<p class="meta"><em><?php _e('Your comment is awaiting approval', 'woothemes'); ?></em></p>
				<?php else : ?>
					<p class="meta">
						<?php _e('Rating by', 'woothemes'); ?> <strong itemprop="author"><?php comment_author(); ?></strong> <?php _e('on', 'woothemes'); ?> <time itemprop="datePublished" time datetime="<?php echo get_comment_date('c'); ?>"><?php echo get_comment_date('M jS Y'); ?></time>:
					</p>
				<?php endif; ?>
				
  				<div itemprop="description" class="description"><?php comment_text(); ?></div>
  				<div class="clear"></div>
  			</div>
			<div class="clear"></div>			
		</div>
	<?php
}

/**
 * Prevent Cache
 **/
function woocommerce_prevent_sidebar_cache() {
	echo '<!--mfunc get_sidebar() --><!--/mfunc-->';
}