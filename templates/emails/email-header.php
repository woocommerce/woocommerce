<?php
/**
 * Email Header
 *
 * @author  WooThemes
 * @package WooCommerce/Templates/Emails
 * @version 2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

// Load colours
$bg_darker_10    = wc_hex_darker( $styles['bg'], 10 );
$base_lighter_20 = wc_hex_lighter( $styles['base_color'], 20 );
$text_lighter_20 = wc_hex_lighter( $styles['text_color'], 20 );

// For gmail compatibility, including CSS styles in head/body are stripped out therefore styles need to be inline. These variables contain rules which are added to the template inline. !important; is a gmail hack to prevent styles being stripped if it doesn't like something.
$wrapper = "
	background-color: " . $styles['bg'] . ";
	width:100%;
	-webkit-text-size-adjust:none !important;
	margin:0;
	padding: 70px 0 70px 0;
";
$template_container = "
	-webkit-box-shadow:" . $styles['box_shadow']['offset'] . ' ' . $styles['box_shadow']['blur'] . ' ' . $styles['box_shadow']['spread'] . ' ' . $styles['box_shadow']['color'] . " !important;
	box-shadow:" . $styles['box_shadow']['offset'] . ' ' . $styles['box_shadow']['blur'] . ' ' .  $styles['box_shadow']['spread'] . ' ' . $styles['box_shadow']['color'] . " !important;
	-webkit-border-radius:" . $styles['border_radius'] . " !important;
	border-radius:" . $styles['border_radius'] . " !important;
	background-color: " . $styles['body_bg'] . ";
	border: 1px solid " . $bg_darker_10 . ";
	-webkit-border-radius:" . $styles['border_radius'] . " !important;
	border-radius:" . $styles['border_radius'] . " !important;
";
$template_header = "
	background-color: " . $styles['base_color'] .";
	color: " . $styles['base_text_color'] . ";
	-webkit-border-top-left-radius:" . $styles['border_radius'] . " !important;
	-webkit-border-top-right-radius:" . $styles['border_radius'] . " !important;
	border-top-left-radius:" . $styles['border_radius'] . " !important;
	border-top-right-radius:" . $styles['border_radius'] . " !important;
	border-bottom: 0;
	font-family: " . $styles['font_family'] . ";
	font-weight: " . $styles['header_text_weight'] . ";
	line-height:100%;
	vertical-align:middle;
";
$body_content = "
	background-color: " . $styles['body_bg'] . ";
	-webkit-border-radius:" . $styles['border_radius'] . " !important;
	border-radius:" . $styles['border_radius'] . " !important;
";
$body_content_inner = "
	color: " . $text_lighter_20 . ";
	font-family: " . $styles['font_family'] . ";
	font-size: " . $styles['base_text_size'] . ";
	line-height:150%;
	text-align:left;
";
$header_content_h1 = "
	color: " . $styles['base_text_color'] . ";
	margin:0;
	padding: 28px 24px;
	text-shadow: 0 1px 0 " . $base_lighter_20 . ";
	display:block;
	font-family: " . $styles['font_family'] . ";
	font-size: " . $styles['header_text_size'] . ";
	font-weight: " . $styles['header_text_weight'] . ";
	text-align:left;
	line-height: 150%;
";
?>
<!DOCTYPE html>
<html>
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
        <title><?php echo get_bloginfo( 'name' ); ?></title>
	</head>
    <body leftmargin="0" marginwidth="0" topmargin="0" marginheight="0" offset="0">
    	<div style="<?php echo $wrapper; ?>">
        	<table border="0" cellpadding="0" cellspacing="0" height="100%" width="100%">
            	<tr>
                	<td align="center" valign="top">
						<div id="template_header_image">
	                		<?php
	                			if ( $img = get_option( 'woocommerce_email_header_image' ) ) {
	                				echo '<p style="margin-top:0;"><img src="' . esc_url( $img ) . '" alt="' . get_bloginfo( 'name' ) . '" /></p>';
	                			}
	                		?>
						</div>
                    	<table border="0" cellpadding="0" cellspacing="0" width="<?php echo $styles['width']; ?>" id="template_container" style="<?php echo $template_container; ?>">
                        	<tr>
                            	<td align="center" valign="top">
                                    <!-- Header -->
                                	<table border="0" cellpadding="0" cellspacing="0" width="<?php echo $styles['width']; ?>" id="template_header" style="<?php echo $template_header; ?>" bgcolor="<?php echo $styles['base_color']; ?>">
                                        <tr>
                                            <td>
                                            	<h1 style="<?php echo $header_content_h1; ?>"><?php echo $email_heading; ?></h1>

                                            </td>
                                        </tr>
                                    </table>
                                    <!-- End Header -->
                                </td>
                            </tr>
                        	<tr>
                            	<td align="center" valign="top">
                                    <!-- Body -->
                                	<table border="0" cellpadding="0" cellspacing="0" width="<?php echo $styles['width']; ?>" id="template_body">
                                    	<tr>
                                            <td valign="top" style="<?php echo $body_content; ?>">
                                                <!-- Content -->
                                                <table border="0" cellpadding="20" cellspacing="0" width="100%">
                                                    <tr>
                                                        <td valign="top">
                                                            <div style="<?php echo $body_content_inner; ?>">
