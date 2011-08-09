<?php
/**
 * Custom Post Types
 **/
function jigoshop_post_type() {

	global $wpdb;
	
	$shop_page_id = get_option('jigoshop_shop_page_id');
	
	$base_slug = $shop_page_id && get_page_uri( $shop_page_id ) ? get_page_uri( $shop_page_id ) : 'shop';
	
	if (get_option('jigoshop_prepend_shop_page_to_urls')=="yes") :
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
                    'name' => __( 'Product Categories', 'jigoshop'),
                    'singular_name' => __( 'Product Category', 'jigoshop'),
                    'search_items' =>  __( 'Search Product Categories', 'jigoshop'),
                    'all_items' => __( 'All Product Categories', 'jigoshop'),
                    'parent_item' => __( 'Parent Product Category', 'jigoshop'),
                    'parent_item_colon' => __( 'Parent Product Category:', 'jigoshop'),
                    'edit_item' => __( 'Edit Product Category', 'jigoshop'),
                    'update_item' => __( 'Update Product Category', 'jigoshop'),
                    'add_new_item' => __( 'Add New Product Category', 'jigoshop'),
                    'new_item_name' => __( 'New Product Category Name', 'jigoshop')
            ),
            'show_ui' => true,
            'query_var' => true,
            'rewrite' => array( 'slug' => $category_base . _x('product-category', 'slug', 'jigoshop'), 'with_front' => false ),
        )
    );
    
    register_taxonomy( 'product_tag',
        array('product'),
        array(
            'hierarchical' => false,
            'labels' => array(
                    'name' => __( 'Product Tags', 'jigoshop'),
                    'singular_name' => __( 'Product Tag', 'jigoshop'),
                    'search_items' =>  __( 'Search Product Tags', 'jigoshop'),
                    'all_items' => __( 'All Product Tags', 'jigoshop'),
                    'parent_item' => __( 'Parent Product Tag', 'jigoshop'),
                    'parent_item_colon' => __( 'Parent Product Tag:', 'jigoshop'),
                    'edit_item' => __( 'Edit Product Tag', 'jigoshop'),
                    'update_item' => __( 'Update Product Tag', 'jigoshop'),
                    'add_new_item' => __( 'Add New Product Tag', 'jigoshop'),
                    'new_item_name' => __( 'New Product Tag Name', 'jigoshop')
            ),
            'show_ui' => true,
            'query_var' => true,
            'rewrite' => array( 'slug' => $category_base . _x('product-tag', 'slug', 'jigoshop'), 'with_front' => false ),
        )
    );
    
    $attribute_taxonomies = jigoshop::$attribute_taxonomies;    
	if ( $attribute_taxonomies ) :
		foreach ($attribute_taxonomies as $tax) :
	    	
	    	$name = 'product_attribute_'.strtolower(sanitize_title($tax->attribute_name));
	    	$hierarchical = true;
	    	if ($name) :

	    		register_taxonomy( $name,
			        array('product'),
			        array(
			            'hierarchical' => $hierarchical,
			            'labels' => array(
			                    'name' => $tax->attribute_name,
			                    'singular_name' =>$tax->attribute_name,
			                    'search_items' =>  __( 'Search ', 'jigoshop') . $tax->attribute_name,
			                    'all_items' => __( 'All ', 'jigoshop') . $tax->attribute_name,
			                    'parent_item' => __( 'Parent ', 'jigoshop') . $tax->attribute_name,
			                    'parent_item_colon' => __( 'Parent ', 'jigoshop') . $tax->attribute_name . ':',
			                    'edit_item' => __( 'Edit ', 'jigoshop') . $tax->attribute_name,
			                    'update_item' => __( 'Update ', 'jigoshop') . $tax->attribute_name,
			                    'add_new_item' => __( 'Add New ', 'jigoshop') . $tax->attribute_name,
			                    'new_item_name' => __( 'New ', 'jigoshop') . $tax->attribute_name
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
				'name' => __( 'Products', 'jigoshop' ),
				'singular_name' => __( 'Product', 'jigoshop' ),
				'add_new' => __( 'Add Product', 'jigoshop' ),
				'add_new_item' => __( 'Add New Product', 'jigoshop' ),
				'edit' => __( 'Edit', 'jigoshop' ),
				'edit_item' => __( 'Edit Product', 'jigoshop' ),
				'new_item' => __( 'New Product', 'jigoshop' ),
				'view' => __( 'View Product', 'jigoshop' ),
				'view_item' => __( 'View Product', 'jigoshop' ),
				'search_items' => __( 'Search Products', 'jigoshop' ),
				'not_found' => __( 'No Products found', 'jigoshop' ),
				'not_found_in_trash' => __( 'No Products found in trash', 'jigoshop' ),
				'parent' => __( 'Parent Product', 'jigoshop' )
			),
			'description' => __( 'This is where you can add new products to your store.', 'jigoshop' ),
			'public' => true,
			'show_ui' => true,
			'capability_type' => 'post',
			'publicly_queryable' => true,
			'exclude_from_search' => false,
			'menu_position' => 57,
			'hierarchical' => true,
			'rewrite' => array( 'slug' => $base_slug, 'with_front' => false ),
			'query_var' => true,			
			'supports' => array( 'title', 'editor', 'thumbnail', 'comments'/*, 'page-attributes'*/ ),
			'has_archive' => $base_slug,
			'show_in_nav_menus' => false,
		)
	);
	
	register_post_type( "product_variation",
		array(
			'labels' => array(
				'name' => __( 'Variations', 'jigoshop' ),
				'singular_name' => __( 'Variation', 'jigoshop' ),
				'add_new' => __( 'Add Variation', 'jigoshop' ),
				'add_new_item' => __( 'Add New Variation', 'jigoshop' ),
				'edit' => __( 'Edit', 'jigoshop' ),
				'edit_item' => __( 'Edit Variation', 'jigoshop' ),
				'new_item' => __( 'New Variation', 'jigoshop' ),
				'view' => __( 'View Variation', 'jigoshop' ),
				'view_item' => __( 'View Variation', 'jigoshop' ),
				'search_items' => __( 'Search Variations', 'jigoshop' ),
				'not_found' => __( 'No Variations found', 'jigoshop' ),
				'not_found_in_trash' => __( 'No Variations found in trash', 'jigoshop' ),
				'parent' => __( 'Parent Variation', 'jigoshop' )
			),
			'public' => true,
			'show_ui' => true,
			'capability_type' => 'post',
			'publicly_queryable' => true,
			'exclude_from_search' => true,
			'hierarchical' => false,
			'rewrite' => false,
			'query_var' => true,			
			'supports' => array( 'title', 'editor', 'custom-fields' ),
			'show_in_nav_menus' => false,
			'show_in_menu' => 'edit.php?post_type=product'
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
				'name' => __( 'Orders', 'jigoshop' ),
				'singular_name' => __( 'Order', 'jigoshop' ),
				'add_new' => __( 'Add Order', 'jigoshop' ),
				'add_new_item' => __( 'Add New Order', 'jigoshop' ),
				'edit' => __( 'Edit', 'jigoshop' ),
				'edit_item' => __( 'Edit Order', 'jigoshop' ),
				'new_item' => __( 'New Order', 'jigoshop' ),
				'view' => __( 'View Order', 'jigoshop' ),
				'view_item' => __( 'View Order', 'jigoshop' ),
				'search_items' => __( 'Search Orders', 'jigoshop' ),
				'not_found' => __( 'No Orders found', 'jigoshop' ),
				'not_found_in_trash' => __( 'No Orders found in trash', 'jigoshop' ),
				'parent' => __( 'Parent Orders', 'jigoshop' )
			),
			'description' => __( 'This is where store orders are stored.', 'jigoshop' ),
			'public' => true,
			'show_ui' => true,
			'capability_type' => 'post',
			'publicly_queryable' => false,
			'exclude_from_search' => true,
			'menu_position' => 58,
			'hierarchical' => false,
			'show_in_nav_menus' => false,
			'rewrite' => false,
			'query_var' => true,			
			'supports' => array( 'title', 'comments' ),
			'has_archive' => false
		)
	);
	
    register_taxonomy( 'shop_order_status',
        array('shop_order'),
        array(
            'hierarchical' => true,
            'update_count_callback' => '_update_post_term_count',
            'labels' => array(
                    'name' => __( 'Order statuses', 'jigoshop'),
                    'singular_name' => __( 'Order status', 'jigoshop'),
                    'search_items' =>  __( 'Search Order statuses', 'jigoshop'),
                    'all_items' => __( 'All  Order statuses', 'jigoshop'),
                    'parent_item' => __( 'Parent Order status', 'jigoshop'),
                    'parent_item_colon' => __( 'Parent Order status:', 'jigoshop'),
                    'edit_item' => __( 'Edit Order status', 'jigoshop'),
                    'update_item' => __( 'Update Order status', 'jigoshop'),
                    'add_new_item' => __( 'Add New Order status', 'jigoshop'),
                    'new_item_name' => __( 'New Order status Name', 'jigoshop')
            ),
            'show_ui' => false,
            'show_in_nav_menus' => false,
            'query_var' => true,
            'rewrite' => false,
        )
    );

    if (get_option('jigowatt_update_rewrite_rules')=='1') :
    	// Re-generate rewrite rules
    	global $wp_rewrite;
    	$wp_rewrite->flush_rules();
    	update_option('jigowatt_update_rewrite_rules', '0');
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
	$wpdb->jigoshop_termmeta = "{$wpdb->prefix}jigoshop_termmeta";
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
function jigoshop_terms_clauses($clauses, $taxonomies, $args ) {
	global $wpdb;
	
	// wordpress should give us the taxonomies asked when calling the get_terms function
	if( !in_array('product_cat', (array)$taxonomies) ) return $clauses;
	
	// query order
	if( isset($args['menu_order']) && !$args['menu_order']) return $clauses; // menu_order is false so we do not add order clause
	
	// query fields
	if( strpos('COUNT(*)', $clauses['fields']) === false ) $clauses['fields']  .= ', tm.* ';

	//query join
	$clauses['join'] .= " LEFT JOIN {$wpdb->jigoshop_termmeta} AS tm ON (t.term_id = tm.jigoshop_term_id AND tm.meta_key = 'order') ";
	
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
add_filter( 'terms_clauses', 'jigoshop_terms_clauses', 10, 3);

/**
 * Reorder on category insertion
 * 
 * @param int $term_id
 */
function jigoshop_create_product_cat ($term_id) {
	
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
	jigoshop_order_categories ( $term, $next_id );
	
}
add_action("create_product_cat", 'jigoshop_create_product_cat');

/**
 * Delete terms metas on deletion
 * 
 * @param int $term_id
 */
function jigoshop_delete_product_cat($term_id) {
	
	$term_id = (int) $term_id;
	
	if(!$term_id) return;
	
	global $wpdb;
	$wpdb->query("DELETE FROM {$wpdb->jigoshop_termmeta} WHERE `jigoshop_term_id` = " . $term_id);
	
}
add_action("delete_product_cat", 'jigoshop_delete_product_cat');

/**
 * Move a category before the a	given element of its hierarchy level
 *
 * @param object $the_term
 * @param int $next_id the id of the next slibling element in save hierachy level
 * @param int $index
 * @param int $terms
 */
function jigoshop_order_categories ( $the_term, $next_id, $index=0, $terms=null ) {
	
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
			$index = jigoshop_set_category_order($id, $index, true);
		}		
		
		// set order
		$index++;
		$index = jigoshop_set_category_order($term->term_id, $index);
		
		// if that term has children we walk through them
		$children = get_terms('product_cat', "parent={$term->term_id}&menu_order=ASC&hide_empty=0");
		if( !empty($children) ) {
			$index = jigoshop_order_categories ( $the_term, $next_id, $index, $children );	
		}
	}
	
	// no nextid meaning our term is in last position
	if( $term_in_level && null === $next_id )
		$index = jigoshop_set_category_order($id, $index+1, true);
	
	return $index;
	
}

/**
 * Set the sort order of a category
 * 
 * @param int $term_id
 * @param int $index
 * @param bool $recursive
 */
function jigoshop_set_category_order ($term_id, $index, $recursive=false) {
	global $wpdb;
	
	$term_id 	= (int) $term_id;
	$index 		= (int) $index;
	
	update_metadata('jigoshop_term', $term_id, 'order', $index);
	
	if( ! $recursive ) return $index;
	
	$children = get_terms('product_cat', "parent=$term_id&menu_order=ASC&hide_empty=0");

	foreach ( $children as $term ) {
		$index ++;
		$index = jigoshop_set_category_order ($term->term_id, $index, true);		
	}
	
	return $index;

}
