<?php
/**
 * WooCommerce Custom Post Types/Taxonomies
 * 
 * Inits custom post types and taxonomies
 *
 * @package		WooCommerce
 * @category	Core
 * @author		WooThemes
 */
 
/**
 * Custom Post Types
 **/
function woocommerce_post_type() {

	global $wpdb, $woocommerce;
	
	$shop_page_id = get_option('woocommerce_shop_page_id');
	
	$base_slug = ($shop_page_id > 0 && get_page( $shop_page_id )) ? get_page_uri( $shop_page_id ) : 'shop';	
	
	if (get_option('woocommerce_prepend_shop_page_to_urls')=="yes") :
		$category_base = trailingslashit($base_slug);
	else :
		$category_base = '';
	endif;
	
	register_taxonomy( 'product_cat',
        array('product'),
        array(
            'hierarchical' => true,
            'update_count_callback' => '_update_post_term_count',
            'labels' => array(
                    'name' => __( 'Categories', 'woothemes'),
                    'singular_name' => __( 'Product Category', 'woothemes'),
                    'search_items' =>  __( 'Search Product Categories', 'woothemes'),
                    'all_items' => __( 'All Product Categories', 'woothemes'),
                    'parent_item' => __( 'Parent Product Category', 'woothemes'),
                    'parent_item_colon' => __( 'Parent Product Category:', 'woothemes'),
                    'edit_item' => __( 'Edit Product Category', 'woothemes'),
                    'update_item' => __( 'Update Product Category', 'woothemes'),
                    'add_new_item' => __( 'Add New Product Category', 'woothemes'),
                    'new_item_name' => __( 'New Product Category Name', 'woothemes')
            ),
            'show_ui' => true,
            'query_var' => true,
            'rewrite' => array( 'slug' => $category_base . _x('product-category', 'slug', 'woothemes'), 'with_front' => false ),
        )
    );
    
    register_taxonomy( 'product_tag',
        array('product'),
        array(
            'hierarchical' => false,
            'labels' => array(
                    'name' => __( 'Tags', 'woothemes'),
                    'singular_name' => __( 'Product Tag', 'woothemes'),
                    'search_items' =>  __( 'Search Product Tags', 'woothemes'),
                    'all_items' => __( 'All Product Tags', 'woothemes'),
                    'parent_item' => __( 'Parent Product Tag', 'woothemes'),
                    'parent_item_colon' => __( 'Parent Product Tag:', 'woothemes'),
                    'edit_item' => __( 'Edit Product Tag', 'woothemes'),
                    'update_item' => __( 'Update Product Tag', 'woothemes'),
                    'add_new_item' => __( 'Add New Product Tag', 'woothemes'),
                    'new_item_name' => __( 'New Product Tag Name', 'woothemes')
            ),
            'show_ui' => true,
            'query_var' => true,
            'rewrite' => array( 'slug' => $category_base . _x('product-tag', 'slug', 'woothemes'), 'with_front' => false ),
        )
    );
    
    $attribute_taxonomies = $woocommerce->get_attribute_taxonomies();    
	if ( $attribute_taxonomies ) :
		foreach ($attribute_taxonomies as $tax) :
	    	
	    	$name = $woocommerce->attribute_name($tax->attribute_name);
	    	$hierarchical = true;
	    	if ($name) :
	    	
	    		$label = ( isset( $tax->attribute_label ) && $tax->attribute_label ) ? $tax->attribute_label : $tax->attribute_name;

	    		register_taxonomy( $name,
			        array('product'),
			        array(
			            'hierarchical' => $hierarchical,
			            'labels' => array(
			                    'name' => $label,
			                    'singular_name' => $label,
			                    'search_items' =>  __( 'Search ', 'woothemes') . $label,
			                    'all_items' => __( 'All ', 'woothemes') . $label,
			                    'parent_item' => __( 'Parent ', 'woothemes') . $label,
			                    'parent_item_colon' => __( 'Parent ', 'woothemes') . $label . ':',
			                    'edit_item' => __( 'Edit ', 'woothemes') . $label,
			                    'update_item' => __( 'Update ', 'woothemes') . $label,
			                    'add_new_item' => __( 'Add New ', 'woothemes') . $label,
			                    'new_item_name' => __( 'New ', 'woothemes') . $label
			            ),
			            'show_ui' => false,
			            'query_var' => true,
			            'show_in_nav_menus' => false,
			            'rewrite' => array( 'slug' => $category_base . strtolower(sanitize_title($tax->attribute_name)), 'with_front' => false, 'hierarchical' => $hierarchical ),
			        )
			    );
	    		
	    	endif;
	    endforeach;    	
    endif;
    
	register_post_type( "product",
		array(
			'labels' => array(
				'name' => __( 'Products', 'woothemes' ),
				'singular_name' => __( 'Product', 'woothemes' ),
				'add_new' => __( 'Add Product', 'woothemes' ),
				'add_new_item' => __( 'Add New Product', 'woothemes' ),
				'edit' => __( 'Edit', 'woothemes' ),
				'edit_item' => __( 'Edit Product', 'woothemes' ),
				'new_item' => __( 'New Product', 'woothemes' ),
				'view' => __( 'View Product', 'woothemes' ),
				'view_item' => __( 'View Product', 'woothemes' ),
				'search_items' => __( 'Search Products', 'woothemes' ),
				'not_found' => __( 'No Products found', 'woothemes' ),
				'not_found_in_trash' => __( 'No Products found in trash', 'woothemes' ),
				'parent' => __( 'Parent Product', 'woothemes' )
			),
			'description' => __( 'This is where you can add new products to your store.', 'woothemes' ),
			'public' => true,
			'show_ui' => true,
			'capability_type' => 'post',
			'publicly_queryable' => true,
			'exclude_from_search' => false,
			'hierarchical' => true,
			'rewrite' => array( 'slug' => $base_slug, 'with_front' => false ),
			'query_var' => true,			
			'supports' => array( 'title', 'editor', 'excerpt', 'thumbnail', 'comments'/*, 'page-attributes'*/ ),
			'has_archive' => $base_slug,
			'show_in_nav_menus' => false,
		)
	);
	
	register_post_type( "product_variation",
		array(
			'labels' => array(
				'name' => __( 'Variations', 'woothemes' ),
				'singular_name' => __( 'Variation', 'woothemes' ),
				'add_new' => __( 'Add Variation', 'woothemes' ),
				'add_new_item' => __( 'Add New Variation', 'woothemes' ),
				'edit' => __( 'Edit', 'woothemes' ),
				'edit_item' => __( 'Edit Variation', 'woothemes' ),
				'new_item' => __( 'New Variation', 'woothemes' ),
				'view' => __( 'View Variation', 'woothemes' ),
				'view_item' => __( 'View Variation', 'woothemes' ),
				'search_items' => __( 'Search Variations', 'woothemes' ),
				'not_found' => __( 'No Variations found', 'woothemes' ),
				'not_found_in_trash' => __( 'No Variations found in trash', 'woothemes' ),
				'parent' => __( 'Parent Variation', 'woothemes' )
			),
			'public' => true,
			'show_ui' => false,
			'capability_type' => 'post',
			'publicly_queryable' => true,
			'exclude_from_search' => true,
			'hierarchical' => true,
			'rewrite' => false,
			'query_var' => true,			
			'supports' => array( 'title', 'editor', 'custom-fields', 'page-attributes', 'thumbnail' ),
			'show_in_nav_menus' => false,
			//'show_in_menu' => 'edit.php?post_type=product'
		)
	);
	
    register_taxonomy( 'product_type',
        array('product'),
        array(
            'hierarchical' => false,
            'show_ui' => false,
            'query_var' => true,
            'show_in_nav_menus' => false,
        )
    );
    
    register_post_type( "shop_order",
		array(
			'labels' => array(
				'name' => __( 'Orders', 'woothemes' ),
				'singular_name' => __( 'Order', 'woothemes' ),
				'add_new' => __( 'Add Order', 'woothemes' ),
				'add_new_item' => __( 'Add New Order', 'woothemes' ),
				'edit' => __( 'Edit', 'woothemes' ),
				'edit_item' => __( 'Edit Order', 'woothemes' ),
				'new_item' => __( 'New Order', 'woothemes' ),
				'view' => __( 'View Order', 'woothemes' ),
				'view_item' => __( 'View Order', 'woothemes' ),
				'search_items' => __( 'Search Orders', 'woothemes' ),
				'not_found' => __( 'No Orders found', 'woothemes' ),
				'not_found_in_trash' => __( 'No Orders found in trash', 'woothemes' ),
				'parent' => __( 'Parent Orders', 'woothemes' )
			),
			'description' => __( 'This is where store orders are stored.', 'woothemes' ),
			'public' => true,
			'show_ui' => true,
			'capability_type' => 'post',
			'publicly_queryable' => false,
			'exclude_from_search' => true,
			'show_in_menu' => 'woocommerce',
			'hierarchical' => false,
			'show_in_nav_menus' => false,
			'rewrite' => false,
			'query_var' => true,			
			'supports' => array( 'title', 'comments', 'custom-fields' ),
			'has_archive' => false
		)
	);
	
    register_taxonomy( 'shop_order_status',
        array('shop_order'),
        array(
            'hierarchical' => true,
            'update_count_callback' => '_update_post_term_count',
            'labels' => array(
                    'name' => __( 'Order statuses', 'woothemes'),
                    'singular_name' => __( 'Order status', 'woothemes'),
                    'search_items' =>  __( 'Search Order statuses', 'woothemes'),
                    'all_items' => __( 'All  Order statuses', 'woothemes'),
                    'parent_item' => __( 'Parent Order status', 'woothemes'),
                    'parent_item_colon' => __( 'Parent Order status:', 'woothemes'),
                    'edit_item' => __( 'Edit Order status', 'woothemes'),
                    'update_item' => __( 'Update Order status', 'woothemes'),
                    'add_new_item' => __( 'Add New Order status', 'woothemes'),
                    'new_item_name' => __( 'New Order status Name', 'woothemes')
            ),
            'show_ui' => false,
            'show_in_nav_menus' => false,
            'query_var' => true,
            'rewrite' => false,
        )
    );
    
    register_post_type( "shop_coupon",
		array(
			'labels' => array(
				'name' => __( 'Coupons', 'woothemes' ),
				'singular_name' => __( 'Coupon', 'woothemes' ),
				'add_new' => __( 'Add Coupon', 'woothemes' ),
				'add_new_item' => __( 'Add New Coupon', 'woothemes' ),
				'edit' => __( 'Edit', 'woothemes' ),
				'edit_item' => __( 'Edit Coupon', 'woothemes' ),
				'new_item' => __( 'New Coupon', 'woothemes' ),
				'view' => __( 'View Coupons', 'woothemes' ),
				'view_item' => __( 'View Coupon', 'woothemes' ),
				'search_items' => __( 'Search Coupons', 'woothemes' ),
				'not_found' => __( 'No Coupons found', 'woothemes' ),
				'not_found_in_trash' => __( 'No Coupons found in trash', 'woothemes' ),
				'parent' => __( 'Parent Coupon', 'woothemes' )
			),
			'description' => __( 'This is where you can add new coupons that customers can use in your store.', 'woothemes' ),
			'public' => true,
			'show_ui' => true,
			'capability_type' => 'post',
			'publicly_queryable' => true,
			'exclude_from_search' => true,
			'show_in_menu' => 'woocommerce',
			'hierarchical' => false,
			'rewrite' => false,
			'query_var' => false,			
			'supports' => array( 'title' ),
			'show_in_nav_menus' => false,
		)
	);

    if (get_option('woocommerce_update_rewrite_rules')=='1') :
    	// Re-generate rewrite rules
    	global $wp_rewrite;
    	$wp_rewrite->flush_rules();
    	update_option('woocommerce_update_rewrite_rules', '0');
    endif;
    
} 

/**
 * Categories ordering
 */

/**
 * Add a table to $wpdb to benefit from wordpress meta api
 */
function taxonomy_metadata_wpdbfix() {
	global $wpdb;
	$wpdb->woocommerce_termmeta = "{$wpdb->prefix}woocommerce_termmeta";
}
add_action('init','taxonomy_metadata_wpdbfix');
add_action('switch_blog','taxonomy_metadata_wpdbfix');

/**
 * Add product_cat ordering to get_terms
 * 
 * It enables the support a 'menu_order' parameter to get_terms for the product_cat taxonomy.
 * By default it is 'ASC'. It accepts 'DESC' too
 * 
 * To disable it, set it ot false (or 0)
 * 
 */
function woocommerce_terms_clauses($clauses, $taxonomies, $args ) {
	global $wpdb;
	
	// wordpress should give us the taxonomies asked when calling the get_terms function
	if( !in_array('product_cat', (array)$taxonomies) ) return $clauses;
	
	// query order
	if( isset($args['menu_order']) && !$args['menu_order']) return $clauses; // menu_order is false so we do not add order clause
	
	// query fields
	if( strpos('COUNT(*)', $clauses['fields']) === false ) $clauses['fields']  .= ', tm.* ';

	//query join
	$clauses['join'] .= " LEFT JOIN {$wpdb->woocommerce_termmeta} AS tm ON (t.term_id = tm.woocommerce_term_id AND tm.meta_key = 'order') ";
	
	// default to ASC
	if( ! isset($args['menu_order']) || ! in_array( strtoupper($args['menu_order']), array('ASC', 'DESC')) ) $args['menu_order'] = 'ASC';

	$order = "ORDER BY CAST(tm.meta_value AS SIGNED) " . $args['menu_order'];
	
	if ( $clauses['orderby'] ):
		$clauses['orderby'] = str_replace ('ORDER BY', $order . ',', $clauses['orderby'] );
	else:
		$clauses['orderby'] = $order;
	endif;
	
	return $clauses;
}
add_filter( 'terms_clauses', 'woocommerce_terms_clauses', 10, 3);

/**
 * Reorder on category insertion
 * 
 * @param int $term_id
 */
function woocommerce_create_product_cat ($term_id) {
	
	$next_id = null;
	
	$term = get_term($term_id, 'product_cat');
	
	// gets the sibling terms
	$siblings = get_terms('product_cat', "parent={$term->parent}&menu_order=ASC&hide_empty=0");
	
	foreach ($siblings as $sibling) {
		if( $sibling->term_id == $term_id ) continue;
		$next_id =  $sibling->term_id; // first sibling term of the hierachy level
		break;
	}

	// reorder
	woocommerce_order_categories ( $term, $next_id );
	
}
add_action("create_product_cat", 'woocommerce_create_product_cat');

/**
 * Delete terms metas on deletion
 * 
 * @param int $term_id
 */
function woocommerce_delete_product_cat($term_id) {
	
	$term_id = (int) $term_id;
	
	if(!$term_id) return;
	
	global $wpdb;
	$wpdb->query("DELETE FROM {$wpdb->woocommerce_termmeta} WHERE `woocommerce_term_id` = " . $term_id);
	
}
add_action("delete_product_cat", 'woocommerce_delete_product_cat');

/**
 * Move a category before the a	given element of its hierarchy level
 *
 * @param object $the_term
 * @param int $next_id the id of the next slibling element in save hierachy level
 * @param int $index
 * @param int $terms
 */
function woocommerce_order_categories ( $the_term, $next_id, $index=0, $terms=null ) {
	
	if( ! $terms ) $terms = get_terms('product_cat', 'menu_order=ASC&hide_empty=0&parent=0');
	if( empty( $terms ) ) return $index;
	
	$id	= $the_term->term_id;
	
	$term_in_level = false; // flag: is our term to order in this level of terms
	
	foreach ($terms as $term) {
		
		if( $term->term_id == $id ) { // our term to order, we skip
			$term_in_level = true;
			continue; // our term to order, we skip
		}
		// the nextid of our term to order, lets move our term here
		if(null !== $next_id && $term->term_id == $next_id) { 
			$index++;
			$index = woocommerce_set_category_order($id, $index, true);
		}		
		
		// set order
		$index++;
		$index = woocommerce_set_category_order($term->term_id, $index);
		
		// if that term has children we walk through them
		$children = get_terms('product_cat', "parent={$term->term_id}&menu_order=ASC&hide_empty=0");
		if( !empty($children) ) {
			$index = woocommerce_order_categories ( $the_term, $next_id, $index, $children );	
		}
	}
	
	// no nextid meaning our term is in last position
	if( $term_in_level && null === $next_id )
		$index = woocommerce_set_category_order($id, $index+1, true);
	
	return $index;
	
}

/**
 * Set the sort order of a category
 * 
 * @param int $term_id
 * @param int $index
 * @param bool $recursive
 */
function woocommerce_set_category_order ($term_id, $index, $recursive=false) {
	global $wpdb;
	
	$term_id 	= (int) $term_id;
	$index 		= (int) $index;
	
	update_metadata('woocommerce_term', $term_id, 'order', $index);
	
	if( ! $recursive ) return $index;
	
	$children = get_terms('product_cat', "parent=$term_id&menu_order=ASC&hide_empty=0");

	foreach ( $children as $term ) {
		$index ++;
		$index = woocommerce_set_category_order ($term->term_id, $index, true);		
	}
	
	return $index;

}