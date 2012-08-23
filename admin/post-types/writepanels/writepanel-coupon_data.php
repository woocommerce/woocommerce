<?php
/**
 * Coupon Data
 *
 * Functions for displaying the coupon data meta box.
 *
 * @author 		WooThemes
 * @category 	Admin
 * @package 	WooCommerce/Admin/WritePanels
 * @version     1.6.4
 */


/**
 * Displays the coupon data meta box.
 *
 * @access public
 * @param mixed $post
 * @return void
 */
function woocommerce_coupon_data_meta_box($post) {
	global $woocommerce;

	wp_nonce_field( 'woocommerce_save_data', 'woocommerce_meta_nonce' );

	?>
	<style type="text/css">
		#edit-slug-box { display:none }
	</style>
	<div id="coupon_options" class="panel woocommerce_options_panel">
		<?php

			echo '<div class="options_group">';

			// Type
    		woocommerce_wp_select( array( 'id' => 'discount_type', 'label' => __('Discount type', 'woocommerce'), 'options' => $woocommerce->get_coupon_discount_types() ) );

			// Amount
			woocommerce_wp_text_input( array( 'id' => 'coupon_amount', 'label' => __('Coupon amount', 'woocommerce'), 'placeholder' => '0.00', 'description' => __('Enter an amount e.g. 2.99', 'woocommerce') ) );

			// Individual use
			woocommerce_wp_checkbox( array( 'id' => 'individual_use', 'label' => __('Individual use', 'woocommerce'), 'description' => __('Check this box if the coupon cannot be used in conjunction with other coupons', 'woocommerce') ) );

			// Apply before tax
			woocommerce_wp_checkbox( array( 'id' => 'apply_before_tax', 'label' => __('Apply before tax', 'woocommerce'), 'description' => __('Check this box if the coupon should be applied before calculating cart tax', 'woocommerce') ) );

			// Free Shipping
			woocommerce_wp_checkbox( array( 'id' => 'free_shipping', 'label' => __('Enable free shipping', 'woocommerce'), 'description' => sprintf(__('Check this box if the coupon enables free shipping (see <a href="%s">Free Shipping</a>)', 'woocommerce'), admin_url('admin.php?page=woocommerce_settings&tab=shipping&section=WC_Free_Shipping')) ) );

			echo '</div><div class="options_group">';

			// minimum spend
			woocommerce_wp_text_input( array( 'id' => 'minimum_amount', 'label' => __('Minimum amount', 'woocommerce'), 'placeholder' => __('No minimum', 'woocommerce'), 'description' => __('This field allows you to set the minimum subtotal needed to use the coupon.', 'woocommerce') ) );

			echo '</div><div class="options_group">';

			// Product ids
			?>
			<p class="form-field"><label for="product_ids"><?php _e('Products', 'woocommerce') ?></label>
			<select id="product_ids" name="product_ids[]" class="ajax_chosen_select_products_and_variations" multiple="multiple" data-placeholder="<?php _e('Search for a product&hellip;', 'woocommerce'); ?>">
				<?php
					$product_ids = get_post_meta( $post->ID, 'product_ids', true );
					if ($product_ids) {
						$product_ids = explode(',', $product_ids);
						foreach ($product_ids as $product_id) {
							$title 	= get_the_title($product_id);
							$sku 	= get_post_meta($product_id, '_sku', true);

							if (!$title) continue;

							if (isset($sku) && $sku) $sku = ' (SKU: ' . $sku . ')';

							echo '<option value="'.$product_id.'" selected="selected">'. $title . $sku .'</option>';
						}
					}
				?>
			</select> <img class="help_tip" data-tip='<?php _e('Products which need to be in the cart to use this coupon or, for "Product Discounts", which products are discounted.', 'woocommerce') ?>' src="<?php echo $woocommerce->plugin_url(); ?>/assets/images/help.png" /></p>
			<?php

			// Exclude Product ids
			?>
			<p class="form-field"><label for="exclude_product_ids"><?php _e('Exclude Products', 'woocommerce') ?></label>
			<select id="exclude_product_ids" name="exclude_product_ids[]" class="ajax_chosen_select_products_and_variations" multiple="multiple" data-placeholder="<?php _e('Search for a product…', 'woocommerce'); ?>">
				<?php
					$product_ids = get_post_meta( $post->ID, 'exclude_product_ids', true );
					if ($product_ids) {
						$product_ids = explode(',', $product_ids);
						foreach ($product_ids as $product_id) {
							$title 	= get_the_title($product_id);
							$sku 	= get_post_meta($product_id, '_sku', true);

							if (!$title) continue;

							if (isset($sku) && $sku) $sku = ' (SKU: ' . $sku . ')';

							echo '<option value="'.$product_id.'" selected="selected">'. $title . $sku .'</option>';
						}
					}
				?>
			</select> <img class="help_tip" data-tip='<?php _e('Products which must not be in the cart to use this coupon or, for "Product Discounts", which products are not discounted.', 'woocommerce') ?>' src="<?php echo $woocommerce->plugin_url(); ?>/assets/images/help.png" /></p>
			<?php

			echo '</div><div class="options_group">';

			// Categories
			?>
			<p class="form-field"><label for="product_ids"><?php _e('Product Categories', 'woocommerce') ?></label>
			<select id="product_categories" name="product_categories[]" class="chosen_select" multiple="multiple" data-placeholder="<?php _e('Any category', 'woocommerce'); ?>">
				<?php
					$category_ids = (array) get_post_meta( $post->ID, 'product_categories', true );

					$categories = get_terms( 'product_cat', 'orderby=name&hide_empty=0' );
					if ($categories) foreach ($categories as $cat) {
						echo '<option value="'.$cat->term_id.'"';
						if (in_array($cat->term_id, $category_ids)) echo 'selected="selected"';
						echo '>'. $cat->name .'</option>';
					}
				?>
			</select> <img class="help_tip" data-tip='<?php _e('A product must be in this category for the coupon to remain valid or, for "Product Discounts", products in these categories will be discounted.', 'woocommerce') ?>' src="<?php echo $woocommerce->plugin_url(); ?>/assets/images/help.png" /></p>
			<?php

			// Exclude Categories
			?>
			<p class="form-field"><label for="exclude_product_categories"><?php _e('Exclude Categories', 'woocommerce') ?></label>
			<select id="exclude_product_categories" name="exclude_product_categories[]" class="chosen_select" multiple="multiple" data-placeholder="<?php _e('No categories', 'woocommerce'); ?>">
				<?php
					$category_ids = (array) get_post_meta( $post->ID, 'exclude_product_categories', true );

					$categories = get_terms( 'product_cat', 'orderby=name&hide_empty=0' );
					if ($categories) foreach ($categories as $cat) {
						echo '<option value="'.$cat->term_id.'"';
						if (in_array($cat->term_id, $category_ids)) echo 'selected="selected"';
						echo '>'. $cat->name .'</option>';
					}
				?>
			</select> <img class="help_tip" data-tip='<?php _e('Product must not be in this category for the coupon to remain valid or, for "Product Discounts", products in these categories will not be discounted.', 'woocommerce') ?>' src="<?php echo $woocommerce->plugin_url(); ?>/assets/images/help.png" /></p>
			<?php

			echo '</div><div class="options_group">';

			// Customers
			woocommerce_wp_text_input( array( 'id' => 'customer_email', 'label' => __('Customer Emails', 'woocommerce'), 'placeholder' => __('Any customer', 'woocommerce'), 'description' => __('Comma separate email addresses to restrict this coupon to specific billing and user emails.', 'woocommerce'), 'value' => implode(', ', (array) get_post_meta( $post->ID, 'customer_email', true )) ) );

			echo '</div><div class="options_group">';

			// Usage limit
			woocommerce_wp_text_input( array( 'id' => 'usage_limit', 'label' => __('Usage limit', 'woocommerce'), 'placeholder' => _x('Unlimited usage', 'placeholder', 'woocommerce'), 'description' => __('How many times this coupon can be used before it is void', 'woocommerce') ) );

			// Expiry date
			woocommerce_wp_text_input( array( 'id' => 'expiry_date', 'label' => __('Expiry date', 'woocommerce'), 'placeholder' => _x('Never expire', 'placeholder', 'woocommerce'), 'description' => __('The date this coupon will expire, <code>YYYY-MM-DD</code>', 'woocommerce'), 'class' => 'short date-picker' ) );

			echo '</div>';

			do_action('woocommerce_coupon_options');

		?>
	</div>
	<?php
}


/**
 * Save the coupon data meta box.
 *
 * @access public
 * @param mixed $post_id
 * @param mixed $post
 * @return void
 */
function woocommerce_process_shop_coupon_meta( $post_id, $post ) {
	global $wpdb, $woocommerce_errors;

	// Check for dupe coupons
	$coupon_found = $wpdb->get_var( $wpdb->prepare( "
		SELECT $wpdb->posts.ID
	    FROM $wpdb->posts
	    WHERE $wpdb->posts.post_type = 'shop_coupon'
	    AND $wpdb->posts.post_status = 'publish'
	    AND $wpdb->posts.post_title = '%s'
	    AND $wpdb->posts.ID != %s
	 ", esc_attr( $_POST['post_title'] ), $post_id ) );
	if ( $coupon_found )
		$woocommerce_errors[] = __( 'Coupon code already exists.', 'woocommerce' );

	// Add/Replace data to array
		$type 			= strip_tags(stripslashes( $_POST['discount_type'] ));
		$amount 		= strip_tags(stripslashes( $_POST['coupon_amount'] ));
		$usage_limit 	= (isset($_POST['usage_limit']) && $_POST['usage_limit']>0) ? (int) $_POST['usage_limit'] : '';
		$individual_use = isset($_POST['individual_use']) ? 'yes' : 'no';
		$expiry_date 	= strip_tags(stripslashes( $_POST['expiry_date'] ));
		$apply_before_tax = isset($_POST['apply_before_tax']) ? 'yes' : 'no';
		$free_shipping = isset($_POST['free_shipping']) ? 'yes' : 'no';
		$minimum_amount = strip_tags(stripslashes( $_POST['minimum_amount'] ));
		$customer_email = array_filter(array_map('trim', explode(',', strip_tags(stripslashes( $_POST['customer_email'] )))));

		if (isset($_POST['product_ids'])) {
			$product_ids 			= (array) $_POST['product_ids'];
			$product_ids 			= implode(',', array_filter(array_map('intval', $product_ids)));
		} else {
			$product_ids = '';
		}

		if (isset($_POST['exclude_product_ids'])) {
			$exclude_product_ids 	= (array) $_POST['exclude_product_ids'];
			$exclude_product_ids 	= implode(',', array_filter(array_map('intval', $exclude_product_ids)));
		} else {
			$exclude_product_ids = '';
		}

		$product_categories = (isset($_POST['product_categories'])) ? array_map('intval', $_POST['product_categories']) : array();
		$exclude_product_categories = (isset($_POST['exclude_product_categories'])) ? array_map('intval', $_POST['exclude_product_categories']) : array();

	// Save
		update_post_meta( $post_id, 'discount_type', $type );
		update_post_meta( $post_id, 'coupon_amount', $amount );
		update_post_meta( $post_id, 'individual_use', $individual_use );
		update_post_meta( $post_id, 'product_ids', $product_ids );
		update_post_meta( $post_id, 'exclude_product_ids', $exclude_product_ids );
		update_post_meta( $post_id, 'usage_limit', $usage_limit );
		update_post_meta( $post_id, 'expiry_date', $expiry_date );
		update_post_meta( $post_id, 'apply_before_tax', $apply_before_tax );
		update_post_meta( $post_id, 'free_shipping', $free_shipping );
		update_post_meta( $post_id, 'product_categories', $product_categories );
		update_post_meta( $post_id, 'exclude_product_categories', $exclude_product_categories );
		update_post_meta( $post_id, 'minimum_amount', $minimum_amount );
		update_post_meta( $post_id, 'customer_email', $customer_email );

		do_action('woocommerce_coupon_options');
}

add_action('woocommerce_process_shop_coupon_meta', 'woocommerce_process_shop_coupon_meta', 1, 2);