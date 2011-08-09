<?php
/**
 * FUNCTIONS USED IN TEMPLATE FILES
 **/

/**
 * Front page archive/shop template
 */
if (!function_exists('jigoshop_front_page_archive')) {
	function jigoshop_front_page_archive() {
			
		global $paged;
		
		if ( is_front_page() && is_page( get_option('jigoshop_shop_page_id') )) :
			
			if ( get_query_var('paged') ) {
			    $paged = get_query_var('paged');
			} else if ( get_query_var('page') ) {
			    $paged = get_query_var('page');
			} else {
			    $paged = 1;
			}
			
			query_posts( array( 'page_id' => '', 'post_type' => 'product', 'paged' => $paged ) );
			
			define('SHOP_IS_ON_FRONT', true);

		endif;
	}
}
add_action('wp_head', 'jigoshop_front_page_archive', 0);


/**
 * Content Wrappers
 **/
if (!function_exists('jigoshop_output_content_wrapper')) {
	function jigoshop_output_content_wrapper() {	
		if(  get_option('template') === 'twentyeleven' ) echo '<section id="primary"><div id="content" role="main">';
		else echo '<div id="container"><div id="content" role="main">';	
	}
}
if (!function_exists('jigoshop_output_content_wrapper_end')) {
	function jigoshop_output_content_wrapper_end() {	
		if(  get_option('template') === 'twentyeleven' ) echo  '</section></div>';
		else echo '</div></div>';	
	}
}

/**
 * Sale Flash
 **/
if (!function_exists('jigoshop_show_product_sale_flash')) {
	function jigoshop_show_product_sale_flash( $post, $_product ) {
		if ($_product->is_on_sale()) echo '<span class="onsale">'.__('Sale!', 'jigoshop').'</span>';
	}
}

/**
 * Sidebar
 **/
if (!function_exists('jigoshop_get_sidebar')) {
	function jigoshop_get_sidebar() {		
		get_sidebar('shop');	
	}
}

/**
 * Products Loop
 **/
if (!function_exists('jigoshop_template_loop_add_to_cart')) {
	function jigoshop_template_loop_add_to_cart( $post, $_product ) {		
		?><a href="<?php echo $_product->add_to_cart_url(); ?>" class="button"><?php _e('Add to cart', 'jigoshop'); ?></a><?php
	}
}
if (!function_exists('jigoshop_template_loop_product_thumbnail')) {
	function jigoshop_template_loop_product_thumbnail( $post, $_product ) {
		echo jigoshop_get_product_thumbnail();
	}
}
if (!function_exists('jigoshop_template_loop_price')) {
	function jigoshop_template_loop_price( $post, $_product ) {
		?><span class="price"><?php echo $_product->get_price_html(); ?></span><?php
	}
}

/**
 * Check product visibility in loop
 **/
if (!function_exists('jigoshop_check_product_visibility')) {
	function jigoshop_check_product_visibility( $post, $_product ) {
		if (!$_product->is_visible() && $post->post_parent > 0) : wp_safe_redirect(get_permalink($post->post_parent)); exit; endif;
		if (!$_product->is_visible()) : wp_safe_redirect(home_url()); exit; endif;
	}
}

/**
 * Before Single Products Summary Div
 **/
if (!function_exists('jigoshop_show_product_images')) {
	function jigoshop_show_product_images() {
		
		global $_product, $post;

		echo '<div class="images">';

		$thumb_id = 0;
		if (has_post_thumbnail()) :
			$thumb_id = get_post_thumbnail_id();
			$large_thumbnail_size = apply_filters('single_product_large_thumbnail_size', 'shop_large');
			echo '<a href="'.wp_get_attachment_url($thumb_id).'" class="zoom" rel="thumbnails">';
			the_post_thumbnail($large_thumbnail_size); 
			echo '</a>';
		else : 
			echo '<img src="'.jigoshop::plugin_url().'/assets/images/placeholder.png" alt="Placeholder" />'; 
		endif; 

		do_action('jigoshop_product_thumbnails');
		
		echo '</div>';
		
	}
}
if (!function_exists('jigoshop_show_product_thumbnails')) {
	function jigoshop_show_product_thumbnails() {
		
		global $_product, $post;
		
		echo '<div class="thumbnails">';
		
		$thumb_id = get_post_thumbnail_id();
		$small_thumbnail_size = apply_filters('single_product_small_thumbnail_size', 'shop_thumbnail');
		$args = array( 'post_type' => 'attachment', 'numberposts' => -1, 'post_status' => null, 'post_parent' => $post->ID ); 
		$attachments = get_posts($args);
		if ($attachments) :
			$loop = 0;
			$columns = 3;
			foreach ( $attachments as $attachment ) : 
				
				if ($thumb_id==$attachment->ID) continue;
				
				$loop++;
				
				$_post = & get_post( $attachment->ID );
				$url = wp_get_attachment_url($_post->ID);
				$post_title = esc_attr($_post->post_title);
				$image = wp_get_attachment_image($attachment->ID, $small_thumbnail_size);
				
				echo '<a href="'.$url.'" title="'.$post_title.'" rel="thumbnails" class="zoom ';
				if ($loop==1 || ($loop-1)%$columns==0) echo 'first';
				if ($loop%$columns==0) echo 'last';
				echo '">'.$image.'</a>';
				
			endforeach;
		endif;
		wp_reset_query();
		
		echo '</div>';
		
	}
}

/**
 * After Single Products Summary Div
 **/
if (!function_exists('jigoshop_output_product_data_tabs')) {
	function jigoshop_output_product_data_tabs() {
		
		if (isset($_COOKIE["current_tab"])) $current_tab = $_COOKIE["current_tab"]; else $current_tab = '#tab-description';
		
		?>
		<div id="tabs">
			<ul class="tabs">
			
				<?php do_action('jigoshop_product_tabs', $current_tab); ?>
				
			</ul>			
			
			<?php do_action('jigoshop_product_tab_panels'); ?>
			
		</div>
		<?php
		
	}
}

/**
 * Product summary box
 **/
if (!function_exists('jigoshop_template_single_price')) {
	function jigoshop_template_single_price( $post, $_product ) {
		?><p class="price"><?php echo $_product->get_price_html(); ?></p><?php
	}
}

if (!function_exists('jigoshop_template_single_excerpt')) {
	function jigoshop_template_single_excerpt( $post, $_product ) {
		if ($post->post_excerpt) echo wpautop(wptexturize($post->post_excerpt));
	}
}

if (!function_exists('jigoshop_template_single_meta')) {
	function jigoshop_template_single_meta( $post, $_product ) {
		
		?>
		<div class="product_meta"><?php if ($_product->is_type('simple') && get_option('jigoshop_enable_sku')=='yes') : ?><span class="sku">SKU: <?php echo $_product->sku; ?>.</span><?php endif; ?><?php echo $_product->get_categories( ', ', ' <span class="posted_in">Posted in ', '.</span>'); ?><?php echo $_product->get_tags( ', ', ' <span class="tagged_as">Tagged as ', '.</span>'); ?></div>
		<?php
		
	}
}

if (!function_exists('jigoshop_template_single_sharing')) {
	function jigoshop_template_single_sharing( $post, $_product ) {
		
		if (get_option('jigoshop_sharethis')) :
			echo '<div class="social">
				<iframe src="https://www.facebook.com/plugins/like.php?href='.urlencode(get_permalink($post->ID)).'&amp;layout=button_count&amp;show_faces=false&amp;width=100&amp;action=like&amp;colorscheme=light&amp;height=21" scrolling="no" frameborder="0" style="border:none; overflow:hidden; width:100px; height:21px;" allowTransparency="true"></iframe>
				<span class="st_twitter"></span><span class="st_email"></span><span class="st_sharethis"></span><span class="st_plusone_button"></span>
			</div>';
		endif;
		
	}
}

/**
 * Product Add to cart buttons
 **/
if (!function_exists('jigoshop_template_single_add_to_cart')) {
	function jigoshop_template_single_add_to_cart( $post, $_product ) {
		do_action( $_product->product_type . '_add_to_cart' );
	}
}
if (!function_exists('jigoshop_simple_add_to_cart')) {
	function jigoshop_simple_add_to_cart() {

		global $_product; $availability = $_product->get_availability();

		if ($availability['availability']) : ?><p class="stock <?php echo $availability['class'] ?>"><?php echo $availability['availability']; ?></p><?php endif;
		
		?>			
		<form action="<?php echo $_product->add_to_cart_url(); ?>" class="cart" method="post">
		 	<div class="quantity"><input name="quantity" value="1" size="4" title="Qty" class="input-text qty text" maxlength="12" /></div>
		 	<button type="submit" class="button-alt"><?php _e('Add to cart', 'jigoshop'); ?></button>
		 	<?php do_action('jigoshop_add_to_cart_form'); ?>
		</form>
		<?php
	}
}
if (!function_exists('jigoshop_virtual_add_to_cart')) {
	function jigoshop_virtual_add_to_cart() {

		jigoshop_simple_add_to_cart();
		
	}
}
if (!function_exists('jigoshop_downloadable_add_to_cart')) {
	function jigoshop_downloadable_add_to_cart() {

		global $_product; $availability = $_product->get_availability();

		if ($availability['availability']) : ?><p class="stock <?php echo $availability['class'] ?>"><?php echo $availability['availability']; ?></p><?php endif;
		
		?>						
		<form action="<?php echo $_product->add_to_cart_url(); ?>" class="cart" method="post">
			<button type="submit" class="button-alt"><?php _e('Add to cart', 'jigoshop'); ?></button>
			<?php do_action('jigoshop_add_to_cart_form'); ?>
		</form>
		<?php
	}
}
if (!function_exists('jigoshop_grouped_add_to_cart')) {
	function jigoshop_grouped_add_to_cart() {

		global $_product;
		
		?>
		<form action="<?php echo $_product->add_to_cart_url(); ?>" class="cart" method="post">
			<table cellspacing="0">
				<tbody>
					<?php foreach ($_product->children as $child) : $child_product = &new jigoshop_product( $child->ID ); $cavailability = $child_product->get_availability(); ?>
						<tr>
							<td><div class="quantity"><input name="quantity[<?php echo $child->ID; ?>]" value="0" size="4" title="Qty" class="input-text qty text" maxlength="12" /></div></td>
							<td><label for="product-<?php echo $child_product->id; ?>"><?php 
								if ($child_product->is_visible()) echo '<a href="'.get_permalink($child->ID).'">';
								echo $child_product->get_title(); 
								if ($child_product->is_visible()) echo '</a>';
							?></label></td>
							<td class="price"><?php echo $child_product->get_price_html(); ?><small class="stock <?php echo $cavailability['class'] ?>"><?php echo $cavailability['availability']; ?></small></td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
			<button type="submit" class="button-alt"><?php _e('Add to cart', 'jigoshop'); ?></button>
			<?php do_action('jigoshop_add_to_cart_form'); ?>
		</form>
		<?php
	}
}
if (!function_exists('jigoshop_variable_add_to_cart')) {
	function jigoshop_variable_add_to_cart() {
		
		global $post, $_product;
		
		$attributes = maybe_unserialize( get_post_meta($post->ID, 'product_attributes', true) );
		if (!isset($attributes)) $attributes = array();

		?>
		<form action="<?php echo $_product->add_to_cart_url(); ?>" class="variations_form cart" method="post">
			
			<table class="variations" cellspacing="0">
				<tbody>
				<?php
					foreach ($attributes as $attribute) :
								
						if ( $attribute['variation']!=='yes' ) continue;
						
						$options = $attribute['value'];
						
						if (!is_array($options)) $options = explode(',', $options);
						
						echo '<tr><td><label for="'.sanitize_title($attribute['name']).'">'.ucfirst($attribute['name']).'</label></td><td><select id="'.sanitize_title($attribute['name']).'" name="tax_'.sanitize_title($attribute['name']).'"><option value="">'.__('Choose an option', 'jigoshop').'&hellip;</option><option>'.implode('</option><option>', $options).'</option></select></td></tr>';
	
					endforeach;
				?>
				</tbody>
			</table>
			<div class="single_variation"></div>
			<div class="variations_button" style="display:none;">
				
				<div class="quantity"><input name="quantity" value="1" size="4" title="Qty" class="input-text qty text" maxlength="12" /></div>
				<button type="submit" class="button-alt"><?php _e('Add to cart', 'jigoshop'); ?></button>
			</div>
			<?php do_action('jigoshop_add_to_cart_form'); ?>
		</form>
		<?php
	}
}


/**
 * Product Add to Cart forms
 **/
if (!function_exists('jigoshop_add_to_cart_form_nonce')) {
	function jigoshop_add_to_cart_form_nonce() {
		jigoshop::nonce_field('add_to_cart');
	}
}

/**
 * Pagination
 **/
if (!function_exists('jigoshop_pagination')) {
	function jigoshop_pagination() {
		
		global $wp_query;
		
		if (  $wp_query->max_num_pages > 1 ) : 
			?>
			<div class="navigation">
				<div class="nav-next"><?php next_posts_link( __( 'Next <span class="meta-nav">&rarr;</span>', 'jigoshop' ) ); ?></div>
				<div class="nav-previous"><?php previous_posts_link( __( '<span class="meta-nav">&larr;</span> Previous', 'jigoshop' ) ); ?></div>
			</div>
			<?php 
		endif;
		
	}
}

/**
 * Product page tabs
 **/
if (!function_exists('jigoshop_product_description_tab')) {
	function jigoshop_product_description_tab( $current_tab ) {
		?>
		<li <?php if ($current_tab=='#tab-description') echo 'class="active"'; ?>><a href="#tab-description"><?php _e('Description', 'jigoshop'); ?></a></li>
		<?php
	}
}
if (!function_exists('jigoshop_product_attributes_tab')) {
	function jigoshop_product_attributes_tab( $current_tab ) {
		
		global $_product;
		
		if ($_product->has_attributes()) : ?><li <?php if ($current_tab=='#tab-attributes') echo 'class="active"'; ?>><a href="#tab-attributes"><?php _e('Additional Information', 'jigoshop'); ?></a></li><?php endif;
		
	}
}
if (!function_exists('jigoshop_product_reviews_tab')) {
	function jigoshop_product_reviews_tab( $current_tab ) {
		
		if ( comments_open() ) : ?><li <?php if ($current_tab=='#tab-reviews') echo 'class="active"'; ?>><a href="#tab-reviews"><?php _e('Reviews', 'jigoshop'); ?><?php echo comments_number(' (0)', ' (1)', ' (%)'); ?></a></li><?php endif;
		
	}
}

/**
 * Product page tab panels
 **/
if (!function_exists('jigoshop_product_description_panel')) {
	function jigoshop_product_description_panel() {
		echo '<div class="panel" id="tab-description">';
		echo '<h2>' . apply_filters('jigoshop_product_description_heading', __('Product Description', 'jigoshop')) . '</h2>';
		the_content();
		echo '</div>';
	}
}
if (!function_exists('jigoshop_product_attributes_panel')) {
	function jigoshop_product_attributes_panel() {
		global $_product;
		echo '<div class="panel" id="tab-attributes">';
		echo '<h2>' . apply_filters('jigoshop_product_description_heading', __('Additional Information', 'jigoshop')) . '</h2>';
		$_product->list_attributes(); 
		echo '</div>';
	}
}
if (!function_exists('jigoshop_product_reviews_panel')) {
	function jigoshop_product_reviews_panel() {
		echo '<div class="panel" id="tab-reviews">';
		comments_template();
		echo '</div>';
	}
}

 

/**
 * Jigoshop Product Thumbnail
 **/
if (!function_exists('jigoshop_get_product_thumbnail')) {
	function jigoshop_get_product_thumbnail( $size = 'shop_small', $placeholder_width = 0, $placeholder_height = 0 ) {
		
		global $post;
		
		if (!$placeholder_width) $placeholder_width = jigoshop::get_var('shop_small_w');
		if (!$placeholder_height) $placeholder_height = jigoshop::get_var('shop_small_h');
		
		if ( has_post_thumbnail() ) return get_the_post_thumbnail($post->ID, $size); else return '<img src="'.jigoshop::plugin_url(). '/assets/images/placeholder.png" alt="Placeholder" width="'.$placeholder_width.'" height="'.$placeholder_height.'" />';
		
	}
}

/**
 * Jigoshop Related Products
 **/
if (!function_exists('jigoshop_output_related_products')) {
	function jigoshop_output_related_products() {
		// 4 Related Products in 4 columns
		jigoshop_related_products( 2, 2 );
	}
}
 
if (!function_exists('jigoshop_related_products')) {
	function jigoshop_related_products( $posts_per_page = 4, $post_columns = 4, $orderby = 'rand' ) {
		
		global $_product, $columns, $per_page;
		
		// Pass vars to loop
		$per_page = $posts_per_page;
		$columns = $post_columns;
		
		$related = $_product->get_related();
		if (sizeof($related)>0) :
			echo '<div class="related products"><h2>'.__('Related Products', 'jigoshop').'</h2>';
			$args = array(
				'post_type'	=> 'product',
				'ignore_sticky_posts'	=> 1,
				'posts_per_page' => $per_page,
				'orderby' => $orderby,
				'post__in' => $related
			);
			$args = apply_filters('jigoshop_related_products_args', $args);
			query_posts($args);
			jigoshop_get_template_part( 'loop', 'shop' ); 
			echo '</div>';
		endif;
		wp_reset_query();
		
	}
}

/**
 * Jigoshop Shipping Calculator
 **/
if (!function_exists('jigoshop_shipping_calculator')) {
	function jigoshop_shipping_calculator() {
		if (jigoshop_shipping::$enabled && get_option('jigoshop_enable_shipping_calc')=='yes' && jigoshop_cart::needs_shipping()) : 
		?>
		<form class="shipping_calculator" action="<?php echo jigoshop_cart::get_cart_url(); ?>" method="post">
			<h2><a href="#" class="shipping-calculator-button"><?php _e('Calculate Shipping', 'jigoshop'); ?> <span>&darr;</span></a></h2>
			<section class="shipping-calculator-form">
			<p class="form-row">
				<select name="calc_shipping_country" id="calc_shipping_country" class="country_to_state" rel="calc_shipping_state">
					<option value=""><?php _e('Select a country&hellip;', 'jigoshop'); ?></option>
					<?php				
						foreach(jigoshop_countries::get_allowed_countries() as $key=>$value) :
							echo '<option value="'.$key.'"';
							if (jigoshop_customer::get_shipping_country()==$key) echo 'selected="selected"';
							echo '>'.$value.'</option>';
						endforeach;
					?>
				</select>
			</p>
			<div class="col2-set">
				<p class="form-row col-1">
					<?php 
						$current_cc = jigoshop_customer::get_shipping_country();
						$current_r = jigoshop_customer::get_shipping_state();
						$states = jigoshop_countries::$states;
						
						if (isset( $states[$current_cc][$current_r] )) :
							// Dropdown
							?>
							<span>
								<select name="calc_shipping_state" id="calc_shipping_state"><option value=""><?php _e('Select a state&hellip;', 'jigoshop'); ?></option><?php
									foreach($states[$current_cc] as $key=>$value) :
										echo '<option value="'.$key.'"';
										if ($current_r==$key) echo 'selected="selected"';
										echo '>'.$value.'</option>';
									endforeach;
								?></select>
							</span>
							<?php
						else :
							// Input
							?>
							<input type="text" class="input-text" value="<?php echo $current_r; ?>" placeholder="<?php _e('state', 'jigoshop'); ?>" name="calc_shipping_state" id="calc_shipping_state" />
							<?php
						endif;
					?>
				</p>
				<p class="form-row col-2">
					<input type="text" class="input-text" value="<?php echo jigoshop_customer::get_shipping_postcode(); ?>" placeholder="<?php _e('Postcode/Zip', 'jigoshop'); ?>" title="<?php _e('Postcode', 'jigoshop'); ?>" name="calc_shipping_postcode" id="calc_shipping_postcode" />
				</p>
			</div>
			<p><button type="submit" name="calc_shipping" value="1" class="button"><?php _e('Update Totals', 'jigoshop'); ?></button></p>
			<?php jigoshop::nonce_field('cart') ?>
			</section>
		</form>
		<?php
		endif;
	}
}

/**
 * Jigoshop Login Form
 **/
if (!function_exists('jigoshop_login_form')) {
	function jigoshop_login_form() {
	
		if (is_user_logged_in()) return;
		
		?>
		<form method="post" class="login">
			<p class="form-row form-row-first">
				<label for="username"><?php _e('Username', 'jigoshop'); ?> <span class="required">*</span></label>
				<input type="text" class="input-text" name="username" id="username" />
			</p>
			<p class="form-row form-row-last">
				<label for="password"><?php _e('Password', 'jigoshop'); ?> <span class="required">*</span></label>
				<input class="input-text" type="password" name="password" id="password" />
			</p>
			<div class="clear"></div>
			
			<p class="form-row">
				<?php jigoshop::nonce_field('login', 'login') ?>
				<input type="submit" class="button" name="login" value="<?php _e('Login', 'jigoshop'); ?>" />
				<a class="lost_password" href="<?php echo home_url('wp-login.php?action=lostpassword'); ?>"><?php _e('Lost Password?', 'jigoshop'); ?></a>
			</p>
		</form>
		<?php
	}
}

/**
 * Jigoshop Login Form
 **/
if (!function_exists('jigoshop_checkout_login_form')) {
	function jigoshop_checkout_login_form() {
		
		if (is_user_logged_in()) return;
		
		?><p class="info"><?php _e('Already registered?', 'jigoshop'); ?> <a href="#" class="showlogin"><?php _e('Click here to login', 'jigoshop'); ?></a></p><?php

		jigoshop_login_form();
	}
}

/**
 * Jigoshop Breadcrumb
 **/
if (!function_exists('jigoshop_breadcrumb')) {
	function jigoshop_breadcrumb( $delimiter = ' &rsaquo; ', $wrap_before = '<div id="breadcrumb">', $wrap_after = '</div>', $before = '', $after = '', $home = null ) {
	 	
	 	global $post, $wp_query, $author, $paged;
	 	
	 	if( !$home ) $home = _x('Home', 'breadcrumb', 'jigoshop'); 	
	 	
	 	$home_link = home_url();
	 	
	 	$prepend = '';
	 	
	 	if ( get_option('jigoshop_prepend_shop_page_to_urls')=="yes" && get_option('jigoshop_shop_page_id') && get_option('page_on_front') !== get_option('jigoshop_shop_page_id') )
	 		$prepend =  $before . '<a href="' . get_permalink( get_option('jigoshop_shop_page_id') ) . '">' . get_the_title( get_option('jigoshop_shop_page_id') ) . '</a> ' . $after . $delimiter;

	 	
	 	if ( (!is_home() && !is_front_page() && !(is_post_type_archive() && get_option('page_on_front')==get_option('jigoshop_shop_page_id'))) || is_paged() ) :
	 	
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
	 			
	 			//echo $before . '<a href="' . get_post_type_archive_link('product') . '">' . ucwords(get_option('jigoshop_shop_slug')) . '</a>' . $after . $delimiter;
	 			
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
	      		echo $prepend . $before . __('Products tagged &ldquo;', 'jigoshop') . $queried_object->name . '&rdquo;' . $after;
				
	 		elseif ( is_day() ) :
	 		
				echo $before . '<a href="' . get_year_link(get_the_time('Y')) . '">' . get_the_time('Y') . '</a>' . $after . $delimiter;
				echo $before . '<a href="' . get_month_link(get_the_time('Y'),get_the_time('m')) . '">' . get_the_time('F') . '</a>' . $after . $delimiter;
				echo $before . get_the_time('d') . $after;
	 
			elseif ( is_month() ) :
			
				echo $before . '<a href="' . get_year_link(get_the_time('Y')) . '">' . get_the_time('Y') . '</a>' . $after . $delimiter;
				echo $before . get_the_time('F') . $after;
	 
			elseif ( is_year() ) :
	
				echo $before . get_the_time('Y') . $after;
	 		
	 		elseif ( is_post_type_archive('product') && get_option('page_on_front') !== get_option('jigoshop_shop_page_id') ) :
			
	 			$_name = get_option('jigoshop_shop_page_id') ? get_the_title( get_option('jigoshop_shop_page_id') ) : ucwords(get_option('jigoshop_shop_slug'));
	 		
	 			if (is_search()) :
	 				
	 				echo $before . '<a href="' . get_post_type_archive_link('product') . '">' . $_name . '</a>' . $delimiter . __('Search results for &ldquo;', 'jigoshop') . get_search_query() . '&rdquo;' . $after;
	 			
	 			else :
	 			
	 				echo $before . '<a href="' . get_post_type_archive_link('product') . '">' . $_name . '</a>' . $after;
	 			
	 			endif;
	 		
			elseif ( is_single() && !is_attachment() ) :
				
				if ( get_post_type() == 'product' ) :
					
	       			//echo $before . '<a href="' . get_post_type_archive_link('product') . '">' . ucwords(get_option('jigoshop_shop_slug')) . '</a>' . $after . $delimiter;
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
		    
		    	echo $before . __('Error 404', 'jigoshop') . $after;
	
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
			
				echo $before . __('Search results for &ldquo;', 'jigoshop') . get_search_query() . '&rdquo;' . $after;
	 
			elseif ( is_tag() ) :
			
	      		echo $before . __('Posts tagged &ldquo;', 'jigoshop') . single_tag_title('', false) . '&rdquo;' . $after;
	 
			elseif ( is_author() ) :
			
				$userdata = get_userdata($author);
				echo $before . __('Author: ', 'jigoshop') . $userdata->display_name . $after;
	     	
		    endif;
	 
			if ( get_query_var('paged') ) :
			
				echo ' (' . __('Page', 'jigoshop') . ' ' . get_query_var('paged') .')';
				
			endif;
	 
	    	echo $wrap_after;
	
		endif;
		
	}
}


function jigoshop_body_classes ($classes) {
	
	if( ! is_singular('product') ) return $classes;
	
	$key = array_search('singular', $classes);
	if ( $key !== false ) unset($classes[$key]);
	return $classes;
	
}
