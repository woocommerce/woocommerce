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

/**
 * Content Wrappers
 **/
if (!function_exists('woocommerce_output_content_wrapper')) {
	function woocommerce_output_content_wrapper() {	
		if ( get_option('template') === 'twentyeleven' ) :
			echo '<div id="primary"><div id="content" role="main">';
		else :
			echo '<div id="container"><div id="content" role="main">';	
		endif;
	}
}
if (!function_exists('woocommerce_output_content_wrapper_end')) {
	function woocommerce_output_content_wrapper_end() {	
		if ( get_option('template') === 'twentyeleven' ) :
			echo  '</div></div>';
		else :
			echo '</div></div>';
		endif;
	}
}

/**
 * Sale Flash
 **/
if (!function_exists('woocommerce_show_product_sale_flash')) {
	function woocommerce_show_product_sale_flash( $post, $_product ) {
		if ($_product->is_on_sale()) echo apply_filters('woocommerce_sale_flash', '<span class="onsale">'.__('Sale!', 'woothemes').'</span>', $post, $_product);
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

/**
 * Products Loop
 **/
if (!function_exists('woocommerce_template_loop_add_to_cart')) {
	function woocommerce_template_loop_add_to_cart( $post, $_product ) {
	
		// No price set - so no button
		if( $_product->get_price() === '') return;
		
		if (!$_product->is_in_stock()) :
			echo '<a href="'.get_permalink($post->ID).'" class="button">'.__('Read More', 'woothemes').'</a>';
			return;
		endif;
		
		?><a href="<?php echo esc_url( $_product->add_to_cart_url() ); ?>" rel="<?php echo $_product->id; ?>" class="button add_to_cart_button product_type_<?php echo $_product->product_type; ?>"><?php
			switch ($_product->product_type) :
				case "variable" :
					echo apply_filters('variable_add_to_cart_text', __('Select options', 'woothemes'));
				break;
				case "grouped" :
					echo apply_filters('grouped_add_to_cart_text', __('View options', 'woothemes'));
				break;
				default :
					echo apply_filters('add_to_cart_text', __('Add to cart', 'woothemes'));
				break;
			endswitch;
		?></a><?php
	}
}
if (!function_exists('woocommerce_template_loop_product_thumbnail')) {
	function woocommerce_template_loop_product_thumbnail( $post, $_product ) {
		echo woocommerce_get_product_thumbnail();
	}
}
if (!function_exists('woocommerce_template_loop_price')) {
	function woocommerce_template_loop_price( $post, $_product ) {
		?><span class="price"><?php echo $_product->get_price_html(); ?></span><?php
	}
}

/**
 * Check product visibility in loop
 **/
if (!function_exists('woocommerce_check_product_visibility')) {
	function woocommerce_check_product_visibility( $post, $_product ) {
		if (!$_product->is_visible() && $post->post_parent > 0) : wp_safe_redirect(get_permalink($post->post_parent)); exit; endif;
		
		if (!$_product->is_visible()) : wp_safe_redirect(home_url()); exit; endif;
	}
}

/**
 * Before Single Products Summary Div
 **/
if (!function_exists('woocommerce_show_product_images')) {
	function woocommerce_show_product_images() {
		
		global $_product, $post, $woocommerce;

		echo '<div class="images">';

		$thumb_id = 0;
		if (has_post_thumbnail()) :
			$thumb_id = get_post_thumbnail_id();
			$large_thumbnail_size = apply_filters('single_product_large_thumbnail_size', 'shop_single');
			echo '<a href="'.wp_get_attachment_url($thumb_id).'" class="zoom" rel="thumbnails" title="'.get_the_title().'">';
			the_post_thumbnail($large_thumbnail_size); 
			echo '</a>';
		else : 
			echo '<img src="'.$woocommerce->plugin_url().'/assets/images/placeholder.png" alt="Placeholder" />'; 
		endif; 

		do_action('woocommerce_product_thumbnails');
		
		echo '</div>';
		
	}
}
if (!function_exists('woocommerce_show_product_thumbnails')) {
	function woocommerce_show_product_thumbnails() {
		
		global $_product, $post;
		
		echo '<div class="thumbnails">';
		
		$thumb_id = get_post_thumbnail_id();
		$small_thumbnail_size = apply_filters('single_product_small_thumbnail_size', 'shop_thumbnail');
		$args = array( 
			'post_type' 	=> 'attachment', 
			'numberposts' 	=> -1, 
			'post_status' 	=> null, 
			'post_parent' 	=> $post->ID,
			'post__not_in'	=> array($thumb_id),
			'post_mime_type' => 'image',
			'meta_query' 	=> array(
				array(
					'key' 		=> '_woocommerce_exclude_image',
					'value'		=> '1',
					'compare' 	=> '!='
				)
			)
		); 
		$attachments = get_posts($args);
		if ($attachments) :
			$loop = 0;
			$columns = 3;
			foreach ( $attachments as $attachment ) : 
				
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
if (!function_exists('woocommerce_output_product_data_tabs')) {
	function woocommerce_output_product_data_tabs() {
		
		?>
		<div class="woocommerce_tabs">
			<ul class="tabs">
				<?php do_action('woocommerce_product_tabs'); ?>
			</ul>			
			<?php do_action('woocommerce_product_tab_panels'); ?>
		</div>
		<?php
		
	}
}

/**
 * Product summary box
 **/
if (!function_exists('woocommerce_template_single_price')) {
	function woocommerce_template_single_price( $post, $_product ) {
		?><p class="price"><?php echo $_product->get_price_html(); ?></p><?php
	}
}

if (!function_exists('woocommerce_template_single_excerpt')) {
	function woocommerce_template_single_excerpt( $post, $_product ) {
		if ($post->post_excerpt) echo wpautop(wptexturize($post->post_excerpt));
	}
}

if (!function_exists('woocommerce_template_single_meta')) {
	function woocommerce_template_single_meta( $post, $_product ) {
		
		?>
		<div class="product_meta"><?php if ($_product->is_type('simple') && get_option('woocommerce_enable_sku')=='yes') : ?><span class="sku">SKU: <?php echo $_product->sku; ?>.</span><?php endif; ?><?php echo $_product->get_categories( ', ', ' <span class="posted_in">Posted in ', '.</span>'); ?><?php echo $_product->get_tags( ', ', ' <span class="tagged_as">Tagged as ', '.</span>'); ?></div>
		<?php
		
	}
}

if (!function_exists('woocommerce_template_single_sharing')) {
	function woocommerce_template_single_sharing( $post, $_product ) {
		
		if (get_option('woocommerce_sharethis')) :
			echo '<div class="social">
				<iframe src="https://www.facebook.com/plugins/like.php?href='.urlencode(get_permalink($post->ID)).'&amp;layout=button_count&amp;show_faces=false&amp;width=100&amp;action=like&amp;colorscheme=light&amp;height=21" scrolling="no" frameborder="0" style="border:none; overflow:hidden; width:100px; height:21px;" allowTransparency="true"></iframe>
				<span class="st_twitter"></span><span class="st_email"></span><span class="st_sharethis"></span><span class="st_plusone_button"></span>
			</div>';
			
			add_action( 'wp_footer', 'woocommerce_sharethis_script' );
		endif;
		
	}
}


/**
 * Sharethis
 *
 * Adds social sharing code to the footer
 **/
if (!function_exists('woocommerce_sharethis_script')) {
	function woocommerce_sharethis_script() {
		if (is_single() && get_option('woocommerce_sharethis')) :
			
			if (is_ssl()) :
				$sharethis = 'https://ws.sharethis.com/button/buttons.js';
			else :
				$sharethis = 'http://w.sharethis.com/button/buttons.js';
			endif;
			
			echo '<script type="text/javascript">var switchTo5x=true;</script><script type="text/javascript" src="'.$sharethis.'"></script><script type="text/javascript">stLight.options({publisher:"'.get_option('woocommerce_sharethis').'"});</script>';
			
		endif;
	}
}


/**
 * Product Add to cart buttons
 **/
if (!function_exists('woocommerce_template_single_add_to_cart')) {
	function woocommerce_template_single_add_to_cart( $post, $_product ) {
		do_action( 'woocommerce_' . $_product->product_type . '_add_to_cart' );
	}
}
if (!function_exists('woocommerce_simple_add_to_cart')) {
	function woocommerce_simple_add_to_cart() {

		global $_product; $availability = $_product->get_availability();
		
		// No price set - so no button
		if( $_product->get_price() === '') return;

		if ($availability['availability']) : ?><p class="stock <?php echo $availability['class'] ?>"><?php echo $availability['availability']; ?></p><?php endif;
		
		// Don't show cart if out of stock
		if (!$_product->is_in_stock()) return;
		?>			
		<form action="<?php echo esc_url( $_product->add_to_cart_url() ); ?>" class="cart" method="post">
		 	<div class="quantity"><input name="quantity" value="1" size="4" title="Qty" class="input-text qty text" maxlength="12" /></div>
		 	<button type="submit" class="button alt"><?php _e('Add to cart', 'woothemes'); ?></button>
		 	<?php do_action('woocommerce_add_to_cart_form'); ?>
		</form>
		<?php
	}
}
if (!function_exists('woocommerce_virtual_add_to_cart')) {
	function woocommerce_virtual_add_to_cart() {

		woocommerce_simple_add_to_cart();
		
	}
}
if (!function_exists('woocommerce_downloadable_add_to_cart')) {
	function woocommerce_downloadable_add_to_cart() {

		global $_product; $availability = $_product->get_availability();
		
		// No price set - so no button
		if( $_product->get_price() === '') return;
		
		if ($availability['availability']) : ?><p class="stock <?php echo $availability['class'] ?>"><?php echo $availability['availability']; ?></p><?php endif;
		
		// Don't show cart if out of stock
		if (!$_product->is_in_stock()) return;
		?>						
		<form action="<?php echo esc_url( $_product->add_to_cart_url() ); ?>" class="cart" method="post">
			<button type="submit" class="button alt"><?php _e('Add to cart', 'woothemes'); ?></button>
			<?php do_action('woocommerce_add_to_cart_form'); ?>
		</form>
		<?php
	}
}
if (!function_exists('woocommerce_grouped_add_to_cart')) {
	function woocommerce_grouped_add_to_cart() {

		global $_product;
		
		?>
		<form action="<?php echo esc_url( $_product->add_to_cart_url() ); ?>" class="cart" method="post">
			<table cellspacing="0" class="group_table">
				<tbody>
					<?php foreach ($_product->get_children() as $child) : $child_product = &new woocommerce_product( $child->ID ); $cavailability = $child_product->get_availability(); ?>
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
			<button type="submit" class="button alt"><?php _e('Add to cart', 'woothemes'); ?></button>
			<?php do_action('woocommerce_add_to_cart_form'); ?>
		</form>
		<?php
	}
}
if (!function_exists('woocommerce_variable_add_to_cart')) {
	function woocommerce_variable_add_to_cart() {
		
		global $post, $_product, $woocommerce;
		
		$attributes = $_product->get_available_attribute_variations();

		// Put available variations into an array and put in a Javascript variable (JSON encoded)
        $available_variations = array();
        
        foreach($_product->get_children() as $child) {
            $variation = $child->product;
            
            if($variation instanceof woocommerce_product_variation) {
                        	
            	if ($variation->variation->post_status != 'publish') continue; // Disabled
            
                $variation_attributes = $variation->get_variation_attributes();
                $availability = $variation->get_availability();
                $availability_html = (!empty($availability['availability'])) ? '<p class="stock '.$availability['class'].'">'. $availability['availability'].'</p>' : '';
                
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
		?>
        <script type="text/javascript">
            var product_variations = <?php echo json_encode($available_variations) ?>;
        </script>

		<form action="<?php echo esc_url( $_product->add_to_cart_url() ); ?>" class="variations_form cart" method="post">
			<table class="variations" cellspacing="0">
				<tbody>
				<?php foreach ($attributes as $name => $options) :?>
					<tr>
						<td><label for="<?php echo sanitize_title($name); ?>"><?php echo $woocommerce->attribute_label($name); ?></label></td>
						<td><select id="<?php echo esc_attr( sanitize_title($name) ); ?>" name="attribute_<?php echo sanitize_title($name); ?>">
							<option value=""><?php echo __('Choose an option', 'woothemes') ?>&hellip;</option>
							<?php if(is_array($options)) : ?>
								<?php foreach ($options as $option) : 
									$option_term = get_term_by('slug', $option, $name); 
									if (!is_wp_error($option_term) && isset($option_term->name)) :
										$term_name = $option_term->name;
									else :
										$term_name = $option;
									endif;
									?>
									<?php echo '<option value="'.$option.'">'.$term_name.'</option>'; ?>
								<?php endforeach; ?>
							<?php endif;?>
						</td>
					</tr>
                <?php endforeach;?>
				</tbody>
			</table>
			<div class="single_variation_wrap" style="display:none;">
				<div class="single_variation"></div>
				<div class="variations_button">
					<input type="hidden" name="variation_id" value="" />
					<div class="quantity"><input name="quantity" value="1" size="4" title="Qty" class="input-text qty text" maxlength="12" /></div>
					<button type="submit" class="button alt"><?php _e('Add to cart', 'woothemes'); ?></button>
				</div>
			</div>
			<div><input type="hidden" name="product_id" value="<?php echo esc_attr( $post->ID ); ?>" /></div>
			<?php do_action('woocommerce_add_to_cart_form'); ?>
		</form>
		<?php
	}
}


/**
 * Product Add to Cart forms
 **/
if (!function_exists('woocommerce_add_to_cart_form_nonce')) {
	function woocommerce_add_to_cart_form_nonce() {
		global $woocommerce;
		$woocommerce->nonce_field('add_to_cart');
	}
}

/**
 * Pagination
 **/
if (!function_exists('woocommerce_pagination')) {
	function woocommerce_pagination() {
		
		global $wp_query;
		
		if (  $wp_query->max_num_pages > 1 ) : 
			?>
			<div class="navigation">
				<div class="nav-next"><?php next_posts_link( __( 'Next <span class="meta-nav">&rarr;</span>', 'woothemes' ) ); ?></div>
				<div class="nav-previous"><?php previous_posts_link( __( '<span class="meta-nav">&larr;</span> Previous', 'woothemes' ) ); ?></div>
			</div>
			<?php 
		endif;
		
	}
}



/**
 * Sorting
 **/
if (!function_exists('woocommerce_catalog_ordering')) {
	function woocommerce_catalog_ordering() {

		?>
		<form class="woocommerce_ordering" method="post">
			<select name="catalog_orderby" class="orderby">
				<option value="title" <?php if (isset($_SESSION['orderby'])) selected( $_SESSION['orderby'], 'title' ); ?>><?php _e('Alphabetically', 'woothemes'); ?></option>
				<option value="date" <?php if (isset($_SESSION['orderby'])) selected( $_SESSION['orderby'], 'date' ); ?>><?php _e('Most Recent', 'woothemes'); ?></option>
				<option value="price" <?php if (isset($_SESSION['orderby'])) selected( $_SESSION['orderby'], 'price' ); ?>><?php _e('Price', 'woothemes'); ?></option>
			</select>
		</form>
		<?php
		
	}
}


/**
 * Product page tabs
 **/
if (!function_exists('woocommerce_product_description_tab')) {
	function woocommerce_product_description_tab() {
		?>
		<li><a href="#tab-description"><?php _e('Description', 'woothemes'); ?></a></li>
		<?php
	}
}
if (!function_exists('woocommerce_product_attributes_tab')) {
	function woocommerce_product_attributes_tab() {
		
		global $_product;
		
		if ($_product->has_attributes()) : ?><li><a href="#tab-attributes"><?php _e('Additional Information', 'woothemes'); ?></a></li><?php endif;
		
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
		echo '<div class="panel" id="tab-description">';
		echo '<h2>' . apply_filters('woocommerce_product_description_heading', __('Product Description', 'woothemes')) . '</h2>';
		the_content();
		echo '</div>';
	}
}
if (!function_exists('woocommerce_product_attributes_panel')) {
	function woocommerce_product_attributes_panel() {
		global $_product;
		echo '<div class="panel" id="tab-attributes">';
		echo '<h2>' . apply_filters('woocommerce_product_additional_information_heading', __('Additional Information', 'woothemes')) . '</h2>';
		$_product->list_attributes(); 
		echo '</div>';
	}
}
if (!function_exists('woocommerce_product_reviews_panel')) {
	function woocommerce_product_reviews_panel() {
		echo '<div class="panel" id="tab-reviews">';
		comments_template();
		echo '</div>';
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
		// 2 Related Products in 2 columns
		woocommerce_related_products( 2, 2 );
	}
}
 
if (!function_exists('woocommerce_related_products')) {
	function woocommerce_related_products( $posts_per_page = 4, $post_columns = 4, $orderby = 'rand' ) {
		
		global $_product, $columns;
		
		// Pass vars to loop
		$columns = $post_columns;
		
		$related = $_product->get_related();
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
		endif;
		wp_reset_query();
		
	}
}

/**
 * WooCommerce Shipping Calculator
 **/
if (!function_exists('woocommerce_shipping_calculator')) {
	function woocommerce_shipping_calculator() {
		global $woocommerce;
		if ($woocommerce->shipping->enabled && get_option('woocommerce_enable_shipping_calc')=='yes' && $woocommerce->cart->needs_shipping()) : 
		?>
		<form class="shipping_calculator" action="<?php echo esc_url( $woocommerce->cart->get_cart_url() ); ?>" method="post">
			<h2><a href="#" class="shipping-calculator-button"><?php _e('Calculate Shipping', 'woothemes'); ?> <span>&darr;</span></a></h2>
			<section class="shipping-calculator-form">
			<p class="form-row">
				<select name="calc_shipping_country" id="calc_shipping_country" class="country_to_state" rel="calc_shipping_state">
					<option value=""><?php _e('Select a country&hellip;', 'woothemes'); ?></option>
					<?php				
						foreach($woocommerce->countries->get_allowed_countries() as $key=>$value) :
							echo '<option value="'.$key.'"';
							if ($woocommerce->customer->get_shipping_country()==$key) echo 'selected="selected"';
							echo '>'.$value.'</option>';
						endforeach;
					?>
				</select>
			</p>
			<div class="col2-set">
				<p class="form-row col-1">
					<?php 
						$current_cc = $woocommerce->customer->get_shipping_country();
						$current_r = $woocommerce->customer->get_shipping_state();
						$states = $woocommerce->countries->states;
						
						if (isset( $states[$current_cc][$current_r] )) :
							// Dropdown
							?>
							<span>
								<select name="calc_shipping_state" id="calc_shipping_state"><option value=""><?php _e('Select a state&hellip;', 'woothemes'); ?></option><?php
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
							<input type="text" class="input-text" value="<?php echo esc_attr( $current_r ); ?>" placeholder="<?php _e('state', 'woothemes'); ?>" name="calc_shipping_state" id="calc_shipping_state" />
							<?php
						endif;
					?>
				</p>
				<p class="form-row col-2">
					<input type="text" class="input-text" value="<?php echo esc_attr( $woocommerce->customer->get_shipping_postcode() ); ?>" placeholder="<?php _e('Postcode/Zip', 'woothemes'); ?>" title="<?php _e('Postcode', 'woothemes'); ?>" name="calc_shipping_postcode" id="calc_shipping_postcode" />
				</p>
			</div>
			<p><button type="submit" name="calc_shipping" value="1" class="button"><?php _e('Update Totals', 'woothemes'); ?></button></p>
			<?php $woocommerce->nonce_field('cart') ?>
			</section>
		</form>
		<?php
		endif;
	}
}


/**
 * WooCommerce Cart totals
 **/
if (!function_exists('woocommerce_cart_totals')) {
	function woocommerce_cart_totals() {
		global $woocommerce;
		
		$available_methods = $woocommerce->shipping->get_available_shipping_methods();
		?>
		<div class="cart_totals">
		<?php
		if ($available_methods || !$woocommerce->customer->get_shipping_country() || !$woocommerce->shipping->enabled ) : 
			// Hide totals if customer has set location and there are no methods going there
			?>
			<h2><?php _e('Cart Totals', 'woothemes'); ?></h2>
			<table cellspacing="0" cellpadding="0">
				<tbody>
					<tr>
						<th><?php _e('Subtotal', 'woothemes'); ?></th>
						<td><?php echo $woocommerce->cart->get_cart_subtotal(); ?></td>
					</tr>
					
					<?php if ($woocommerce->cart->get_cart_shipping_total()) : ?><tr>
						<th><?php _e('Shipping', 'woothemes'); ?> <small><?php echo $woocommerce->countries->shipping_to_prefix().' '.$woocommerce->countries->countries[ $woocommerce->customer->get_shipping_country() ]; ?></small></th>
						<td>
							<?php
								if (sizeof($available_methods)>0) :
									
									echo '<select name="shipping_method" id="shipping_method">';
									
									foreach ($available_methods as $method ) :
										
										echo '<option value="'.$method->id.'" '.selected($method->id, $_SESSION['_chosen_shipping_method'], false).'>'.$method->title.' &ndash; ';
										if ($method->shipping_total>0) :
										
											if (get_option('woocommerce_display_totals_tax')=='excluding') :
					
												echo woocommerce_price($method->shipping_total);
												if ($method->shipping_tax>0) :
													_e(' (ex. tax)', 'woothemes');
												endif;
												
											else :
												
												echo woocommerce_price($method->shipping_total + $method->shipping_tax);
												if ($method->shipping_tax>0) :
													_e(' (inc. tax)', 'woothemes');
												endif;
											
											endif;
	
										else :
											echo __('Free', 'woothemes');
										endif;
										
										echo '</option>';
			
									endforeach;
									
									echo '</select>';
								endif;
							?>
						</td>
					</tr><?php endif; ?>
					
					<?php if ($woocommerce->cart->get_cart_tax()) : ?><tr>
						<th><?php _e('Tax', 'woothemes'); ?> <?php if ($woocommerce->customer->is_customer_outside_base()) : ?><small><?php echo sprintf(__('estimated for %s', 'woothemes'), $woocommerce->countries->estimated_for_prefix() . $woocommerce->countries->countries[ $woocommerce->countries->get_base_country() ] ); ?></small><?php endif; ?></th>
						<td><?php 
							echo $woocommerce->cart->get_cart_tax(); 
						?></td>
					</tr><?php endif; ?>
					
					<?php if ($woocommerce->cart->get_total_discount()) : ?><tr class="discount">
						<th><?php _e('Discount', 'woothemes'); ?></th>
						<td>-<?php echo $woocommerce->cart->get_total_discount(); ?></td>
					</tr><?php endif; ?>
					<tr>
						<th><strong><?php _e('Total', 'woothemes'); ?></strong></th>
						<td><strong><?php echo $woocommerce->cart->get_total(); ?></strong></td>
					</tr>
				</tbody>
			</table>
			<p><small><?php _e('Note: Tax and shipping totals are estimated and will be updated during checkout based on your billing information.', 'woothemes'); ?></small></p>
			<?php
		else :
			echo '<p>'.__('Sorry, it seems that there are no available shipping methods to your location. Please contact us if you require assistance or wish to make alternate arrangements.', 'woothemes').'</p>';
		endif;
		?>
		</div>
		<?php
	}
}

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
				<a class="lost_password" href="<?php echo esc_url( home_url('wp-login.php?action=lostpassword') ); ?>"><?php _e('Lost Password?', 'woothemes'); ?></a>
			</p>
		</form>
		<?php
	}
}

/**
 * WooCommerce Login Form
 **/
if (!function_exists('woocommerce_checkout_login_form')) {
	function woocommerce_checkout_login_form() {
		
		if (is_user_logged_in()) return;
		
		?><p class="info"><?php _e('Already registered?', 'woothemes'); ?> <a href="#" class="showlogin"><?php _e('Click here to login', 'woothemes'); ?></a></p><?php

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
	 			
	 			//echo $before . '<a href="' . get_post_type_archive_link('product') . '">' . ucwords(get_option('woocommerce_shop_slug')) . '</a>' . $after . $delimiter;
	 			
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
				echo $before . __('Author: ', 'woothemes') . $userdata->display_name . $after;
	     	
		    endif;
	 
			if ( get_query_var('paged') ) :
			
				echo ' (' . __('Page', 'woothemes') . ' ' . get_query_var('paged') .')';
				
			endif;
	 
	    	echo $wrap_after;
	
		endif;
		
	}
}

/**
 * Remove the singular class for woocommerce single product
 **/
function woocommerce_body_classes ($classes) {
	
	if( ! is_singular('product') ) return $classes;
	
	$key = array_search('singular', $classes);
	if ( $key !== false ) unset($classes[$key]);
	return $classes;
	
}

/**
 * Display Up Sells
 **/
function woocommerce_upsell_display() {
	global $_product;
	$upsells = $_product->get_upsells();
	if (sizeof($upsells)>0) :
		echo '<div class="upsells products"><h2>'.__('You may also like&hellip;', 'woothemes').'</h2><ul>';
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
	global $columns, $woocommerce;
	$columns = 2;
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
	if (get_option('woocommerce_demo_store')=='yes') :
		echo '<p class="demo_store">'.__('This is a demo store for testing purposes &mdash; no orders shall be fulfilled.', 'woothemes').'</p>';
	endif;
}

/**
 * display product sub categories as thumbnails
 **/
function woocommerce_product_subcategories() {
	global $woocommerce, $columns, $loop, $wp_query, $wp_the_query;
	
	if ($wp_query !== $wp_the_query) return; // Detect main query
	
	if (is_search()) return;
	if (!is_product_category() && !is_shop()) return;
	if (is_product_category() && get_option('woocommerce_show_subcategories')=='no') return;
	if (is_shop() && get_option('woocommerce_shop_show_subcategories')=='no') return;

	$product_cat_slug 	= get_query_var('product_cat');
	
	if ($product_cat_slug) :
		$product_cat 		= get_term_by('slug', $product_cat_slug, 'product_cat');
		$parent 			= $product_cat->term_id;
	else :
		$parent = 0;
	endif;
	
	$args = array(
	    'parent'                   => $parent,
	    'orderby'                  => 'menu_order',
	    'order'                    => 'ASC',
	    'hide_empty'               => 1,
	    'hierarchical'             => 0,
	    'taxonomy'                 => 'product_cat',
	    );
	$categories = get_categories( $args );
	if ($categories) foreach ($categories as $category) : $loop++;
			
		?>
		<li class="product <?php if ($loop%$columns==0) echo 'last'; if (($loop-1)%$columns==0) echo 'first'; ?>">
			
			<?php do_action('woocommerce_before_subcategory', $category); ?>
			
			<a href="<?php echo get_term_link($category->slug, 'product_cat'); ?>">
				
				<?php do_action('woocommerce_before_subcategory_title', $category); ?>
				
				<h3><?php echo $category->name; ?> (<?php echo $category->count; ?>)</h3>
				
				<?php do_action('woocommerce_after_subcategory_title', $category); ?>
			
			</a>
	
			<?php do_action('woocommerce_after_subcategory', $category); ?>
			
		</li><?php 
		
	endforeach;
	
}

function woocommerce_subcategory_thumbnail( $category ) {
	global $woocommerce;
	
	$small_thumbnail_size 	= apply_filters('single_product_small_thumbnail_size', 'shop_thumbnail');		
	$image_width 			= $woocommerce->get_image_size('shop_thumbnail_image_width');
	$image_height 			= $woocommerce->get_image_size('shop_thumbnail_image_height');
	
	$thumbnail_id 	= get_woocommerce_term_meta( $category->term_id, 'thumbnail_id', true );
	
	if ($thumbnail_id) :
		$image = wp_get_attachment_image_src( $thumbnail_id, $small_thumbnail_size );
		$image = $image[0];
	else :
		$image = $woocommerce->plugin_url().'/assets/images/placeholder.png';
	endif;

	echo '<img src="'.$image.'" alt="'.$category->slug.'" width="'.$image_width.'" height="'.$image_height.'" />';
}

