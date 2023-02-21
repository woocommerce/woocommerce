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

function create_static_site_page() {
  $page_title = 'WooCommerce Developer Docs!';
  $page_content = '';
  $page_exists = get_page_by_title($page_title);

  if (!$page_exists) {
    $page = array(
      'post_title' => $page_title,
      'post_content' => $page_content,
      'post_status' => 'publish',
      'post_type' => 'page',
    );

    wp_insert_post($page);
  }
}

register_activation_hook(__FILE__, 'create_static_site_page');

// function gatsby_template( $original_template ) {
// 	global $post;

//   if ($post->post_title == 'WooCommerce Developer Docs') {
// 		return dirname(__FILE__) . '/gatsby.php';    
//   } 
  
// 	return $original_template;
// }

// add_filter( 'template_include', 'gatsby_template' );

function load_static_site_template( $original_template ) {
    $url = 'http' . (isset($_SERVER['HTTPS']) ? 's' : '') . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
    $url_parts = parse_url($url);

    if (isset($url_parts['path']) && strpos($url_parts['path'], '/docs') === 0) {
        return dirname(__FILE__) . '/static_site.php';
    }
    return $template;
}

// Load custom template for web requests going to "/docs" or "/docs/<..>/..."
add_filter( 'template_include', 'load_static_site_template' );
