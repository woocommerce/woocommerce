<?php
/**
 * Functions for handling WordPress import to make it compatable with WooCommerce
 *
 * WordPress import should work - however, it fails to import custom product attribute taxonomies.
 * This code grabs the file before it is imported and ensures the taxonomies are created.
 *
 * @author 		WooThemes
 * @category 	Admin
 * @package 	WooCommerce
 */
 
function woocommerce_import_start() {
	
	global $wpdb;
	
	$id = (int) $_POST['import_id'];
	$file = get_attached_file( $id );

	$parser = new WXR_Parser();
	$import_data = $parser->parse( $file );

	if (isset($import_data['posts'])) :
		$posts = $import_data['posts'];
		
		if ($posts && sizeof($posts)>0) foreach ($posts as $post) :
			
			if ($post['post_type']=='product') :
				
				if ($post['terms'] && sizeof($post['terms'])>0) :
					
					foreach ($post['terms'] as $term) :
						
						$domain = $term['domain'];
						
						if (strstr($domain, 'product_attribute_')) :
							
							// Make sure it exists!
							if (!taxonomy_exists( $domain )) :
								
								$nicename = ucfirst(str_replace('product_attribute_', '', $domain));
								
								// Create the taxonomy
								$wpdb->insert( $wpdb->prefix . "woocommerce_attribute_taxonomies", array( 'attribute_name' => $nicename, 'attribute_type' => 'text' ), array( '%s', '%s' ) );
								
								// Register the taxonomy now so that the import works!
								register_taxonomy( $domain,
							        array('product'),
							        array(
							            'hierarchical' => true,
							            'labels' => array(
							                    'name' => $nicename,
							                    'singular_name' => $nicename,
							                    'search_items' =>  __( 'Search ', 'woothemes') . $nicename,
							                    'all_items' => __( 'All ', 'woothemes') . $nicename,
							                    'parent_item' => __( 'Parent ', 'woothemes') . $nicename,
							                    'parent_item_colon' => __( 'Parent ', 'woothemes') . $nicename . ':',
							                    'edit_item' => __( 'Edit ', 'woothemes') . $nicename,
							                    'update_item' => __( 'Update ', 'woothemes') . $nicename,
							                    'add_new_item' => __( 'Add New ', 'woothemes') . $nicename,
							                    'new_item_name' => __( 'New ', 'woothemes') . $nicename
							            ),
							            'show_ui' => false,
							            'query_var' => true,
							            'rewrite' => array( 'slug' => strtolower(sanitize_title($nicename)), 'with_front' => false, 'hierarchical' => true ),
							        )
							    );
			
								update_option('woocommerce_update_rewrite_rules', '1');
								
							endif;
							
						endif;
						
					endforeach;
					
				endif;
				
			endif;
			
		endforeach;
		
	endif;

}

add_action('import_start', 'woocommerce_import_start');