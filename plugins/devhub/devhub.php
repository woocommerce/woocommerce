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
 * Text Domain: woocommerce-beta-tester
 *
 * @package WC_Beta_Tester
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

    $page_id = wp_insert_post($page);

    if (! is_wp_error( $page_id ) ) {
      update_post_meta($page_id, '_wp_page_template', 'gatsby.php');
    }
  }
}

register_activation_hook(__FILE__, 'create_gatsby_page');
