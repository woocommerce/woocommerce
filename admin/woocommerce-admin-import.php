<?php
/**
 * Functions for handling WordPress import to make it compatable with WooCommerce
 *
 * WordPress import should work - however, it fails to import custom product attribute taxonomies.
 * This code grabs the file before it is imported and ensures the taxonomies are created.
 *
 * @author 		WooThemes
 * @category 	Admin
 * @package 	WooCommerce/Admin/Import
 * @version     1.6.4
 */

/**
 * When running the WP importer, ensure attributes exist.
 *
 * @access public
 * @return void
 */
function woocommerce_import_start() {
	global $wpdb;

	if (!isset($_POST['import_id'])) return;
	if (!class_exists('WXR_Parser')) return;

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

						if (strstr($domain, 'pa_')) :

							// Make sure it exists!
							if (!taxonomy_exists( $domain )) :

								$nicename = strtolower(sanitize_title(str_replace('pa_', '', $domain)));

								$exists_in_db = $wpdb->get_var("SELECT attribute_id FROM ".$wpdb->prefix . "woocommerce_attribute_taxonomies WHERE attribute_name = '".$nicename."';");

								if (!$exists_in_db) :

									// Create the taxonomy
									$wpdb->insert( $wpdb->prefix . "woocommerce_attribute_taxonomies", array( 'attribute_name' => $nicename, 'attribute_type' => 'select' ), array( '%s', '%s' ) );

								endif;

								// Register the taxonomy now so that the import works!
								register_taxonomy( $domain,
							        array('product'),
							        array(
							            'hierarchical' => true,
							            'show_ui' => false,
							            'query_var' => true,
							            'rewrite' => false,
							        )
							    );

							endif;

						endif;

					endforeach;

				endif;

			endif;

		endforeach;

	endif;

}

add_action('import_start', 'woocommerce_import_start');