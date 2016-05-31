<?php
/**
 * Auth header
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/auth/header.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see 	    https://docs.woothemes.com/document/template-structure/
 * @author  WooThemes
 * @package WooCommerce/Templates/Auth
 * @version 2.4.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?><!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta name="viewport" content="width=device-width" />
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<meta name="robots" content="noindex, nofollow" />
	<title><?php _e( 'Application Authentication Request', 'woocommerce' ); ?></title>
	<?php wp_admin_css( 'install', true ); ?>
	<link rel="stylesheet" href="<?php echo esc_url( str_replace( array( 'http:', 'https:' ), '', WC()->plugin_url() ) . '/assets/css/auth.css' ); ?>" type="text/css" />
</head>
<body class="wc-auth wp-core-ui">
	<h1 id="wc-logo"><img src="<?php echo WC()->plugin_url(); ?>/assets/images/woocommerce_logo.png" alt="WooCommerce" /></h1>
	<div class="wc-auth-content">
