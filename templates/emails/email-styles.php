<?php
/**
 * Email Styles
 *
 * @author  WooThemes
 * @package WooCommerce/Templates/Emails
 * @version 2.3.0
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

// Load colours
$bg              = get_option( 'woocommerce_email_background_color' );
$body            = get_option( 'woocommerce_email_body_background_color' );
$base            = get_option( 'woocommerce_email_base_color' );
$base_text       = wc_light_or_dark( $base, '#202020', '#ffffff' );
$text            = get_option( 'woocommerce_email_text_color' );

$bg_darker_10    = wc_hex_darker( $bg, 10 );
$base_lighter_20 = wc_hex_lighter( $base, 20 );
$text_lighter_20 = wc_hex_lighter( $text, 20 );

// !important; is a gmail hack to prevent styles being stripped if it doesn't like something.
?>
#wrapper {
    background-color: <?php echo esc_attr( $bg ); ?>;
    margin: 0;
    padding: 70px 0 70px 0;
    -webkit-text-size-adjust: none !important;
    width: 100%;
}
#template_container {
    box-shadow: 0 0 0 3px rgba(0,0,0,0.025) !important;
    border-radius: 6px !important;
    background-color: <?php echo esc_attr( $body ); ?>;
    border: 1px solid $bg_darker_10;
    border-radius: 6px !important;
}
#template_header {
    background-color: <?php echo esc_attr( $base ); ?>;
    color: <?php echo $base_text; ?>;
    border-top-left-radius:6px !important;
    border-top-right-radius:6px !important;
    border-bottom: 0;
    font-family:Arial;
    font-weight:bold;
    line-height:100%;
    vertical-align:middle;
}
#template_header h1 {
    color: <?php echo $base_text; ?>;
}
#body_content {
    background-color: <?php echo esc_attr( $body ); ?>;
    border-radius:6px !important;
}
#body_content_inner {
    color: <?php echo $text_lighter_20;?>;
    font-family:Arial;
    font-size:14px;
    line-height:150%;
    text-align: <?php echo is_rtl() ? 'right' : 'left'; ?>;
}
h1 {
    color: <?php echo esc_attr( $base ); ?>;
    display: block;
    font-family: Arial;
    font-size: 30px;
    font-weight: bold;
    line-height: 150%;
    margin: 10px 0;
    padding: 28px 24px;
    text-align: <?php echo is_rtl() ? 'right' : 'left'; ?>;
    text-shadow: 0 1px 0 <?php echo $base_lighter_20; ?>;
}
h2 {
    color: <?php echo esc_attr( $base ); ?>;
    display: block;
    font-family: Arial;
    font-size: 30px;
    font-weight: bold;
    line-height: 150%;
    margin: 10px 0;
    text-align: <?php echo is_rtl() ? 'right' : 'left'; ?>;
}
h3 {
    color: <?php echo esc_attr( $base ); ?>;
    display: block;
    font-family: Arial;
    font-size: 26px;
    font-weight: bold;
    line-height: 150%;
    margin: 10px 0;
    text-align: <?php echo is_rtl() ? 'right' : 'left'; ?>;
}
a {
    color: <?php echo esc_attr( $base ); ?>;
    font-weight: normal;
    text-decoration: underline;
}
img {
    border: non;
    display: inline;
    font-size: 14px;
    font-weight: bold;
    height: auto;
    line-height: 100%;
    outline: none;
    text-decoration: none;
    text-transform: capitalize;
}
<?php
