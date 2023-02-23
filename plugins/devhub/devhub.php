<?php
/**
 * Plugin Name: WooCommerce DevHub
 * Plugin URI: https://github.com/woocommerce/woocommerce
 * Description: WooCommerce DevHub
 * Version: 0.0.1
 * Author: WooCommerce
 * Author URI: http://woocommerce.com/
 * Requires at least: 5.8
 * Tested up to: 6.0
 * WC requires at least: 6.7
 * WC tested up to: 7.0
 * Text Domain: woocommerce-dev-hub
 *
 * @package WC_DevHub
 */

defined( 'ABSPATH' ) || exit;

function is_ignored_dir($dir) {
    $ignored_prefixes = array(
        '~',
        '_',
        '.'
    );
    $ignored_dirs = array(
        'page-data',
        'static'
    );

    return in_array($dir, $ignored_dirs) || in_array(substr($dir, 0, 1), $ignored_prefixes);
}

function is_static_page($file) {
    return substr($file, -5) === '.html' && is_file($file);
}

// Function to loop through the contents of a directory and create pages
function create_static_site_pages( $parent_id, $dir_path ) {
    // Get a list of all directories and files in the directory
    $items = scandir( $dir_path );

    // Loop through each item in the directory
    foreach ( $items as $item ) {
        // Skip any items that start with a period (e.g. ".", "..")
        if ( substr( $item, 0, 1 ) == '.' ) {
            continue;
        }

        // Determine the path to the item
        $item_path = $dir_path . '/' . $item;

        // If the item is a directory, create a new page and tag and loop through its contents
        if ( is_dir( $item_path ) && ! is_ignored_dir($item) ) {
            // Create the page data
            $page_data = array(
                'post_title'    => ucfirst( $item ),
                'post_content'  => '',
                'post_status'   => 'publish',
                'post_type'     => 'page',
                'post_name'     => strtolower( $item ),
                'post_parent'   => $parent_id
            );

            // Insert the page into the database
            $page_id = wp_insert_post( $page_data );

            // Add a unique tag to the page
            $tag = 'static_folder_' . strtolower( str_replace( '/', '_', $item_path ) );
            wp_set_post_tags( $page_id, $tag );

            // Recursively loop through the contents of the directory and create sub-pages
            create_static_site_pages( $page_id, $item_path );
        }

        // If the item is a file, create a new page
        if ( is_static_page( $item_path ) ) {
            // Create the page data
            $page_data = array(
                'post_title'    => ucfirst( str_replace( '.html', '', $item ) ),
                'post_content'  => file_get_contents( $item_path ),
                'post_status'   => 'publish',
                'post_type'     => 'page',
                'post_name'     => strtolower( str_replace( '.html', '', $item ) ),
                'post_parent'   => $parent_id
            );

            // Insert the page into the database
            $page_id = wp_insert_post( $page_data );
            $dir_path = dirname(__FILE__) . '/site/public';            
            $relative_path = str_replace($dir_path, '', dirname(realpath($item_path))) . '/' . basename($item);

            // Add a unique tag to the page
            $tag = 'static_file_' . strtolower( str_replace( '/', '_', substr($relative_path,1) ) );
            wp_set_post_tags( $page_id, $tag );
        }
    }
}

function create_pages() { 
    // Set the base directory for your HTML files
    $base_dir = dirname(__FILE__) . '/site/public';   
    
    create_static_site_pages( 0, $base_dir );
}

register_activation_hook(__FILE__, 'create_pages');

function load_static_site_template( $original_template ) {
    $url = 'http' . (isset($_SERVER['HTTPS']) ? 's' : '') . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
    $url_parts = parse_url($url);

    if (isset($url_parts['path']) && strpos($url_parts['path'], '/docs') === 0) {
        return dirname(__FILE__) . '/static_site.php';
    }
    return $template;
}

add_filter( 'template_include', 'load_static_site_template' );

function tags_support_all() {
    register_taxonomy_for_object_type('post_tag', 'page');
}

add_action('init', 'tags_support_all');
