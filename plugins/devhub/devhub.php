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

function create_gatsby_page() {
  $page_title = 'WooCommerce Developer Docs';
  $page_content = '';
  $page_exists = get_page_by_title($page_title);

  if (!$page_exists) {
    $page = array(
      'post_title' => $page_title,
      'post_content' => $page_content,
      'post_status' => 'publish',
      'post_type' => 'page'
    );

    wp_insert_post($page);
  }
}

register_activation_hook(__FILE__, 'create_gatsby_page');


function gatsby_template( $original_template ) {
	global $post;

  if ($post->post_title == 'WooCommerce Developer Docs') {
		return dirname(__FILE__) . '/gatsby.php';    
  } 
  
	return $original_template;
}

add_filter( 'template_include', 'gatsby_template' );

function enqueue_gatsby_assets() {
  $gatsby_assets_dir = dirname(__FILE__) . '/site/public'; // Replace with actual path to Gatsby public directory
  $gatsby_js_files = glob( $gatsby_assets_dir . '/*.js' );
  
  foreach ( $gatsby_js_files as $js_file ) {
    $js_file_url = str_replace( $gatsby_assets_dir, get_site_url(), $js_file );    
    $js_file_handle = 'gatsby-' . basename( $js_file, '.js' );
    wp_register_script( $js_file_handle, $js_file_url, array(), null, true );
    wp_enqueue_script( basename( $js_file ), $js_file_url, array(), null, true );
  }
}

add_action( 'wp_enqueue_scripts', 'enqueue_gatsby_assets' );

