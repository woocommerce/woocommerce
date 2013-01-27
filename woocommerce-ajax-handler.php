<?php
/**
 * Mimics wp_ajax, but loads only the essential components for speed.
 *
 * @author 		WooThemes
 * @category 	Core
 * @package 	WooCommerce/Functions/AJAX
 * @version     2.0.0
 */

define( 'DOING_AJAX', true );

/** Load WordPress Bootstrap */
$parse_uri = explode( 'wp-content', $_SERVER['SCRIPT_FILENAME'] );
require_once( $parse_uri[0] . 'wp-load.php' );

// Require an action parameter
if ( empty( $_REQUEST['action'] ) )
	die( '0' );

// Headers
@header( 'Content-Type: text/html; charset=' . get_option( 'blog_charset' ) );
@header( 'X-Robots-Tag: noindex' );
send_nosniff_header();
nocache_headers();

// Only allow specific actions via this handler
$allowed_actions = array(
    'woocommerce_get_widget_shopping_cart'
);

if ( ! in_array( $_REQUEST['action'], $allowed_actions ) )
	die( '0' );

if ( is_user_logged_in() )
	do_action( 'wp_ajax_' . $_REQUEST['action'] ); // Authenticated actions
else
	do_action( 'wp_ajax_nopriv_' . $_REQUEST['action'] ); // Non-admin actions

// Default status
die( '0' );