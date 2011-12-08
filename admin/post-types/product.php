<?php
/**
 * Admin functions for the shop_product post type
 *
 * @author 		WooThemes
 * @category 	Admin
 * @package 	WooCommerce
 */
 
/**
 * Columns for Products page
 **/
add_filter('manage_edit-product_columns', 'woocommerce_edit_product_columns');

function woocommerce_edit_product_columns($columns){
	
	$columns = array();
	
	$columns["cb"] = "<input type=\"checkbox\" />";
	$columns["thumb"] = __("Image", 'woothemes');
	
	$columns["title"] = __("Name", 'woothemes');
	if( get_option('woocommerce_enable_sku', true) == 'yes' ) $columns["sku"] = __("ID", 'woothemes');
	$columns["product_type"] = __("Type", 'woothemes');
	
	$columns["product_cat"] = __("Categories", 'woothemes');
	$columns["product_tags"] = __("Tags", 'woothemes');
	$columns["featured"] = __("Featured", 'woothemes');
	
	if (get_option('woocommerce_manage_stock')=='yes') :
		$columns["is_in_stock"] = __("In Stock?", 'woothemes');
	endif;
	
	$columns["price"] = __("Price", 'woothemes');
	$columns["product_date"] = __("Date", 'woothemes');
	
	return $columns;
}


/**
 * Custom Columns for Products page
 **/
add_action('manage_product_posts_custom_column', 'woocommerce_custom_product_columns', 2);

function woocommerce_custom_product_columns($column) {
	global $post, $woocommerce;
	$product = &new woocommerce_product($post->ID);

	switch ($column) {
		case "thumb" :
			if (has_post_thumbnail($post->ID)) :
				echo get_the_post_thumbnail($post->ID, 'shop_thumbnail');
			endif;
		break;
		case "price":
			echo $product->get_price_html();	
		break;
		case "product_cat" :
			if (!$terms = get_the_term_list($post->ID, 'product_cat', '', ', ','')) echo '<span class="na">&ndash;</span>'; else echo $terms;
		break;
		case "product_tags" :
			if (!$terms = get_the_term_list($post->ID, 'product_tag', '', ', ','')) echo '<span class="na">&ndash;</span>'; else echo $terms;
		break;
		case "sku" :
			if ( $sku = get_post_meta( $post->ID, 'sku', true )) :
				echo '#'.$post->ID.' - SKU: ' . $sku;	
			else :
				echo '#'.$post->ID;
			endif;
		break;
		case "featured" :
			$url = wp_nonce_url( admin_url('admin-ajax.php?action=woocommerce-feature-product&product_id=' . $post->ID) );
			echo '<a href="'.$url.'" title="'.__('Change', 'woothemes') .'">';
			if ($product->is_featured()) echo '<a href="'.$url.'"><img src="'.$woocommerce->plugin_url().'/assets/images/success.gif" alt="yes" />';
			else echo '<img src="'.$woocommerce->plugin_url().'/assets/images/success-off.gif" alt="no" />';
			echo '</a>';
		break;
		case "is_in_stock" :
			if ( !$product->is_type( 'grouped' ) && $product->is_in_stock() ) :
				echo '<img src="'.$woocommerce->plugin_url().'/assets/images/success.gif" alt="yes" /> ';
			else :
				echo '<img src="'.$woocommerce->plugin_url().'/assets/images/success-off.gif" alt="no" /> ';
			endif;
			if ( $product->managing_stock() ) :
				echo $product->stock.__(' in stock', 'woothemes');
			endif;
		break;
		case "product_type" :
			echo ucwords($product->product_type);
		break;
		case "product_date" :
			if ( '0000-00-00 00:00:00' == $post->post_date ) :
				$t_time = $h_time = __( 'Unpublished', 'woothemes' );
				$time_diff = 0;
			else :
				$t_time = get_the_time( __( 'Y/m/d g:i:s A', 'woothemes' ) );
				$m_time = $post->post_date;
				$time = get_post_time( 'G', true, $post );

				$time_diff = time() - $time;

				if ( $time_diff > 0 && $time_diff < 24*60*60 )
					$h_time = sprintf( __( '%s ago', 'woothemes' ), human_time_diff( $time ) );
				else
					$h_time = mysql2date( __( 'Y/m/d', 'woothemes' ), $m_time );
			endif;

			echo '<abbr title="' . $t_time . '">' . apply_filters( 'post_date_column_time', $h_time, $post ) . '</abbr><br />';
			
			if ( 'publish' == $post->post_status ) :
				_e( 'Published' );
			elseif ( 'future' == $post->post_status ) :
				if ( $time_diff > 0 ) :
					echo '<strong class="attention">' . __( 'Missed schedule', 'woothemes' ) . '</strong>';
				else :
					_e( 'Scheduled' );
				endif;
			else :
				_e( 'Last Modified' );
			endif;

			if ( $this_data = $product->visibility ) :
				echo '<br />' . ucfirst($this_data);	
			endif;
			
		break;
	}
}


/**
 * Make product columns sortable
 * https://gist.github.com/906872
 **/
add_filter("manage_edit-product_sortable_columns", 'woocommerce_custom_product_sort');

function woocommerce_custom_product_sort($columns) {
	$custom = array(
		'is_in_stock' 	=> 'inventory',
		'price'			=> 'price',
		'featured'		=> 'featured',
		'sku'			=> 'sku',
		'product_date'	=> 'date'
	);
	return wp_parse_args($custom, $columns);
}


/**
 * Product column orderby
 * http://scribu.net/wordpress/custom-sortable-columns.html#comment-4732
 **/
add_filter( 'request', 'woocommerce_custom_product_orderby' );

function woocommerce_custom_product_orderby( $vars ) {
	if (isset( $vars['orderby'] )) :
		if ( 'inventory' == $vars['orderby'] ) :
			$vars = array_merge( $vars, array(
				'meta_key' 	=> 'stock',
				'orderby' 	=> 'meta_value_num'
			) );
		endif;
		if ( 'price' == $vars['orderby'] ) :
			$vars = array_merge( $vars, array(
				'meta_key' 	=> 'price',
				'orderby' 	=> 'meta_value_num'
			) );
		endif;
		if ( 'featured' == $vars['orderby'] ) :
			$vars = array_merge( $vars, array(
				'meta_key' 	=> 'featured',
				'orderby' 	=> 'meta_value'
			) );
		endif;
		if ( 'sku' == $vars['orderby'] ) :
			$vars = array_merge( $vars, array(
				'orderby' 	=> 'id'
			) );
		endif;
	endif;
	
	return $vars;
}

/**
 * Filter products by category, uses slugs for option values. Code adapted by Andrew Benbow - chromeorange.co.uk
 **/
add_action('restrict_manage_posts','woocommerce_products_by_category');

function woocommerce_products_by_category() {
	global $typenow, $wp_query;
    if ($typenow=='product') :
    	woocommerce_product_dropdown_categories();
    endif;
}


/**
 * Filter products by type
 **/
add_action('restrict_manage_posts', 'woocommerce_products_by_type');

function woocommerce_products_by_type() {
    global $typenow, $wp_query;
    if ($typenow=='product') :
    	
    	// Types
		$terms = get_terms('product_type');
		$output = "<select name='product_type' id='dropdown_product_type'>";
		$output .= '<option value="">'.__('Show all product types', 'woothemes').'</option>';
		foreach($terms as $term) :
			$output .="<option value='$term->slug' ";
			if ( isset( $wp_query->query['product_type'] ) ) $output .=selected($term->slug, $wp_query->query['product_type'], false);
			$output .=">".ucfirst($term->name)." ($term->count)</option>";
		endforeach;
		$output .="</select>";
		
		// Downloadable/virtual
		$output .= "<select name='product_subtype' id='dropdown_product_subtype'>";
		$output .= '<option value="">'.__('Show all sub-types', 'woothemes').'</option>';
		
		$output .="<option value='downloadable' ";
		if ( isset( $_GET['product_subtype'] ) ) $output .= selected('downloadable', $_GET['product_subtype'], false);
		$output .=">".__('Downloadable', 'woothemes')."</option>";
		
		$output .="<option value='virtual' ";
		if ( isset( $_GET['product_subtype'] ) ) $output .= selected('virtual', $_GET['product_subtype'], false);
		$output .=">".__('Virtual', 'woothemes')."</option>";

		$output .="</select>";
		
		echo $output;
    endif;
}

add_filter( 'parse_query', 'woocommerce_products_subtype_query' );

function woocommerce_products_subtype_query($query) {
	global $typenow, $wp_query;
    if ($typenow=='product' && isset($_GET['product_subtype']) && $_GET['product_subtype']) :
    	if ($_GET['product_subtype']=='downloadable') :
        	$query->query_vars['meta_value'] 	= 'yes';
        	$query->query_vars['meta_key'] 		= 'downloadable';
        endif;
        if ($_GET['product_subtype']=='virtual') :
        	$query->query_vars['meta_value'] 	= 'yes';
        	$query->query_vars['meta_key'] 		= 'virtual';
        endif;
	endif;
}


/**
 * Add functionality to the image uploader on product pages to exlcude an image
 **/
add_filter('attachment_fields_to_edit', 'woocommerce_exclude_image_from_product_page_field', 1, 2);
add_filter('attachment_fields_to_save', 'woocommerce_exclude_image_from_product_page_field_save', 1, 2);

function woocommerce_exclude_image_from_product_page_field( $fields, $object ) {
	
	if (!$object->post_parent) return $fields;
	
	$parent = get_post( $object->post_parent );
	
	if ($parent->post_type!=='product') return $fields;
	
	$exclude_image = (int) get_post_meta($object->ID, '_woocommerce_exclude_image', true);
	
	$label = __('Exclude image', 'woothemes');
	
	$html = '<input type="checkbox" '.checked($exclude_image, 1, false).' name="attachments['.$object->ID.'][woocommerce_exclude_image]" id="attachments['.$object->ID.'][woocommerce_exclude_image" />';
	
	$fields['woocommerce_exclude_image'] = array(
			'label' => $label,
			'input' => 'html',
			'html' =>  $html,
			'value' => '',
			'helps' => __('Enabling this option will hide it from the product page image gallery.', 'woothemes')
	);
	
	return $fields;
}

function woocommerce_exclude_image_from_product_page_field_save( $post, $attachment ) {

	if (isset($_REQUEST['attachments'][$post['ID']]['woocommerce_exclude_image'])) :
		delete_post_meta( (int) $post['ID'], '_woocommerce_exclude_image' );
		update_post_meta( (int) $post['ID'], '_woocommerce_exclude_image', 1);
	else :
		delete_post_meta( (int) $post['ID'], '_woocommerce_exclude_image' );
		update_post_meta( (int) $post['ID'], '_woocommerce_exclude_image', 0);
	endif;
		
	return $post;
				
}


/**
 * Search by SKU or ID for products. Adapted from code by BenIrvin (Admin Search by ID)
 */
if (is_admin()) :
	add_action( 'parse_request', 'woocommerce_admin_product_search' );
	add_filter( 'get_search_query', 'woocommerce_admin_product_search_label' );
endif;

function woocommerce_admin_product_search( $wp ) {
    global $pagenow, $wpdb;
	
	if( 'edit.php' != $pagenow ) return;
	if( !isset( $wp->query_vars['s'] ) ) return;
	if ($wp->query_vars['post_type']!='product') return;

	if( '#' == substr( $wp->query_vars['s'], 0, 1 ) ) :

		$id = absint( substr( $wp->query_vars['s'], 1 ) );
			
		if( !$id ) return; 
		
		unset( $wp->query_vars['s'] );
		$wp->query_vars['p'] = $id;
		
	elseif( 'SKU:' == substr( $wp->query_vars['s'], 0, 4 ) ) :
		
		$sku = trim( substr( $wp->query_vars['s'], 4 ) );
			
		if( !$sku ) return; 
		
		$id = $wpdb->get_var('SELECT post_id FROM '.$wpdb->postmeta.' WHERE meta_key="sku" AND meta_value LIKE "%'.$sku.'%";');
		
		if( !$id ) return; 

		unset( $wp->query_vars['s'] );
		$wp->query_vars['p'] = $id;
		$wp->query_vars['sku'] = $sku;
		
	endif;
}

function woocommerce_admin_product_search_label($query) {
	global $pagenow, $typenow, $wp;

    if ( 'edit.php' != $pagenow ) return $query;
    if ( $typenow!='product' ) return $query;
	
	$s = get_query_var( 's' );
	if ($s) return $query;
	
	$sku = get_query_var( 'sku' );
	if($sku) {
		$post_type = get_post_type_object($wp->query_vars['post_type']);
		return sprintf(__("[%s with SKU of %s]", 'woothemes'), $post_type->labels->singular_name, $sku);
	}
	
	$p = get_query_var( 'p' );
	if ($p) {
		$post_type = get_post_type_object($wp->query_vars['post_type']);
		return sprintf(__("[%s with ID of %d]", 'woothemes'), $post_type->labels->singular_name, $p);
	}
	
	return $query;
}