<?php
/**
 * Email Styles
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/emails/email-styles.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see     https://docs.woocommerce.com/document/template-structure/
 * @package WooCommerce/Templates/Emails
 * @version 4.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Load colors.
$bg           = get_option( 'woocommerce_email_background_color' );
$body         = get_option( 'woocommerce_email_body_background_color' );
$base         = get_option( 'woocommerce_email_base_color' );
$base_text    = wc_light_or_dark( $base, '#202020', '#ffffff' );
$text         = get_option( 'woocommerce_email_text_color' );
$bg_dk        = get_option( 'woocommerce_email_background_color_dk' );
$body_dk      = get_option( 'woocommerce_email_body_background_color_dk' );
$base_dk      = get_option( 'woocommerce_email_base_color_dk' );
$base_text_dk = wc_light_or_dark( $base_dk, '#202020', '#ffffff' );
$text_dk      = get_option( 'woocommerce_email_text_color_dk' );
$image_dk_src = get_option( 'woocommerce_email_header_image_dk' );

// Pick a contrasting color for links.
$link_color = wc_hex_is_light( $base ) ? $base : $base_text;

if ( wc_hex_is_light( $body ) ) {
	$link_color = wc_hex_is_light( $base ) ? $base_text : $base;
}

$bg_darker_10    = wc_hex_darker( $bg, 10 );
$body_darker_10  = wc_hex_darker( $body, 10 );
$base_lighter_20 = wc_hex_lighter( $base, 20 );
$base_lighter_40 = wc_hex_lighter( $base, 40 );
$text_lighter_20 = wc_hex_lighter( $text, 20 );
$text_lighter_40 = wc_hex_lighter( $text, 40 );

$bg_dk_lighter_10   = wc_hex_lighter( $bg_dk, 10 );
$body_dk_lighter_10 = wc_hex_lighter( $body_dk, 10 );
$base_dk_darker_20  = wc_hex_darker( $base_dk, 20 );
$base_dk_darker_40  = wc_hex_darker( $base_dk, 40 );
$text_dk_darker_20  = wc_hex_darker( $text_dk, 20 );
$text_dk_darker_40  = wc_hex_darker( $text_dk, 40 );

// !important; is a gmail hack to prevent styles being stripped if it doesn't like something.
// body{padding: 0;} ensures proper scale/positioning of the email in the iOS native email app.
?>
body {
	padding: 0;
}

#wrapper {
	background-color: <?php echo esc_attr( $bg ); ?>;
	margin: 0;
	padding: 70px 0;
	-webkit-text-size-adjust: none !important;
	width: 100%;
}

#template_container {
	box-shadow: 0 1px 4px rgba(0, 0, 0, 0.1) !important;
	background-color: <?php echo esc_attr( $body ); ?>;
	border: 1px solid <?php echo esc_attr( $bg_darker_10 ); ?>;
	border-radius: 3px !important;
}

#template_header {
	background-color: <?php echo esc_attr( $base ); ?>;
	border-radius: 3px 3px 0 0 !important;
	color: <?php echo esc_attr( $base_text ); ?>;
	border-bottom: 0;
	font-weight: bold;
	line-height: 100%;
	vertical-align: middle;
	font-family: "Helvetica Neue", Helvetica, Roboto, Arial, sans-serif;
}

#template_header h1,
#template_header h1 a {
	color: <?php echo esc_attr( $base_text ); ?>;
}

#template_header_image img {
	margin-left: 0;
	margin-right: 0;
}

#template_footer td {
	padding: 0;
	border-radius: 6px;
}

#template_footer #credit {
	border: 0;
	color: <?php echo esc_attr( $text_lighter_40 ); ?>;
	font-family: "Helvetica Neue", Helvetica, Roboto, Arial, sans-serif;
	font-size: 12px;
	line-height: 150%;
	text-align: center;
	padding: 24px 0;
}

#template_footer #credit p {
	margin: 0 0 16px;
}

#body_content {
	background-color: <?php echo esc_attr( $body ); ?>;
}

#body_content table td {
	padding: 48px 48px 32px;
}

#body_content table td td {
	padding: 12px;
}

#body_content table td th {
	padding: 12px;
}

#body_content td ul.wc-item-meta {
	font-size: small;
	margin: 1em 0 0;
	padding: 0;
	list-style: none;
}

#body_content td ul.wc-item-meta li {
	margin: 0.5em 0 0;
	padding: 0;
}

#body_content td ul.wc-item-meta li p {
	margin: 0;
}

#body_content p {
	margin: 0 0 16px;
}

#body_content_inner {
	color: <?php echo esc_attr( $text_lighter_20 ); ?>;
	font-family: "Helvetica Neue", Helvetica, Roboto, Arial, sans-serif;
	font-size: 14px;
	line-height: 150%;
	text-align: <?php echo is_rtl() ? 'right' : 'left'; ?>;
}

.td {
	color: <?php echo esc_attr( $text_lighter_20 ); ?>;
	border: 1px solid <?php echo esc_attr( $body_darker_10 ); ?>;
	vertical-align: middle;
}

.address {
	padding: 12px;
	color: <?php echo esc_attr( $text_lighter_20 ); ?>;
	border: 1px solid <?php echo esc_attr( $body_darker_10 ); ?>;
}

.text {
	color: <?php echo esc_attr( $text ); ?>;
	font-family: "Helvetica Neue", Helvetica, Roboto, Arial, sans-serif;
}

.link {
	color: <?php echo esc_attr( $base ); ?>;
}

#header_wrapper {
	padding: 36px 48px;
	display: block;
}

h1 {
	color: <?php echo esc_attr( $base ); ?>;
	font-family: "Helvetica Neue", Helvetica, Roboto, Arial, sans-serif;
	font-size: 30px;
	font-weight: 300;
	line-height: 150%;
	margin: 0;
	text-align: <?php echo is_rtl() ? 'right' : 'left'; ?>;
	text-shadow: 0 1px 0 <?php echo esc_attr( $base_lighter_20 ); ?>;
}

h2 {
	color: <?php echo esc_attr( $base ); ?>;
	display: block;
	font-family: "Helvetica Neue", Helvetica, Roboto, Arial, sans-serif;
	font-size: 18px;
	font-weight: bold;
	line-height: 130%;
	margin: 0 0 18px;
	text-align: <?php echo is_rtl() ? 'right' : 'left'; ?>;
}

h3 {
	color: <?php echo esc_attr( $base ); ?>;
	display: block;
	font-family: "Helvetica Neue", Helvetica, Roboto, Arial, sans-serif;
	font-size: 16px;
	font-weight: bold;
	line-height: 130%;
	margin: 16px 0 8px;
	text-align: <?php echo is_rtl() ? 'right' : 'left'; ?>;
}

a {
	color: <?php echo esc_attr( $link_color ); ?>;
	font-weight: normal;
	text-decoration: underline;
}

img {
	border: none;
	display: inline-block;
	font-size: 14px;
	font-weight: bold;
	height: auto;
	outline: none;
	text-decoration: none;
	text-transform: capitalize;
	vertical-align: middle;
	margin-<?php echo is_rtl() ? 'left' : 'right'; ?>: 10px;
	max-width: 100%;
	height: auto;
}

// important flag is necessary as long as styles are inlined on each element as these styles are written to the style tag in the document head.
:root {
	color-scheme: light dark;
	supported-color-schemes: light dark;
}
@media (prefers-color-scheme: dark) {
	#wrapper,
	[data-ogsc] #wrapper,
	[data-ogsb] #wrapper {
		background-color: <?php echo esc_attr( $bg_dk ); ?> !important;
	}

	#template_container,
	[data-ogsb] #template_container,
	[data-ogsc] #template_container {
		background-color: <?php echo esc_attr( $body_dk ); ?> !important;
		border: 1px solid <?php echo esc_attr( $bg_dk_lighter_10 ); ?> !important;
	}

	#template_header,
	[data-ogsc] #template_header,
	[data-ogsb] #template_header {
		background-color: <?php echo esc_attr( $base_dk ); ?> !important;
		color: <?php echo esc_attr( $base_text_dk ); ?> !important;
	}

	#template_header_image p img,
	[data-ogsc] #template_header_image p img {
		content:url('<?php echo esc_attr( $image_dk_src ); ?>') !important;
	}

	#template_header h1,
	[data-ogsc] #template_header h1,
	#template_header h1 a,
	[data-ogsc] #template_header h1 a {
		color: <?php echo esc_attr( $base_text ); ?> !important;
	}

	#template_footer #credit,
	[data-ogsc] #template_footer #credit {
		color: <?php echo esc_attr( $text_dk_darker_40 ); ?> !important;
	}

	#body_content,
	[data-ogsb] #body_content,
	[data-ogsc] #body_content {
		background-color: <?php echo esc_attr( $body_dk ); ?> !important;
	}

	#body_content_inner,
	[data-ogsc] #body_content_inner {
		color: <?php echo esc_attr( $text_dk_darker_20 ); ?> !important;
	}

	.td,
	[data-ogsc] .td {
		color: <?php echo esc_attr( $text_dk_darker_20 ); ?> !important;
		border: 1px solid <?php echo esc_attr( $body_dk_lighter_10 ); ?> !important;
	}

	.address,
	[data-ogsc] .address {
		color: <?php echo esc_attr( $text_dk_darker_20 ); ?> !important;
		border: 1px solid <?php echo esc_attr( $body_dk_lighter_10 ); ?> !important;
	}

	.text,
	[data-ogsc] .text {
		color: <?php echo esc_attr( $text_dk ); ?> !important;
	}

	.link,
	[data-ogsc] .link {
		color: <?php echo esc_attr( $base_dk ); ?> !important;
	}

	h1,
	[data-ogsc] h1 {
		color: <?php echo esc_attr( $base_dk ); ?> !important;
		text-shadow: 0 1px 0 <?php echo esc_attr( $base_dk_darker_20 ); ?> !important;
	}

	h2,
	[data-ogsc] h2 {
		color: <?php echo esc_attr( $base_dk ); ?> !important;
	}

	h3,
	[data-ogsc] h3 {
		color: <?php echo esc_attr( $base_dk ); ?> !important;
	}

	a,
	[data-ogsc] a {
		color: <?php echo esc_attr( $link_color_dk ); ?> !important;
	}
}
<?php
