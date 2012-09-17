<?php
/**
 * Email Header
 *
 * @author 		WooThemes
 * @package 	WooCommerce/Templates/Emails
 * @version     1.7.0
 */
 
// Load colours
$bg 		= get_option( 'woocommerce_email_background_color' );
$body		= get_option( 'woocommerce_email_body_background_color' );
$base 		= get_option( 'woocommerce_email_base_color' );
$base_text 	= woocommerce_light_or_dark( $base, '#202020', '#ffffff' );
$text 		= get_option( 'woocommerce_email_text_color' );

$bg_darker_10 = woocommerce_hex_darker( $bg, 10 );
$base_lighter_20 = woocommerce_hex_lighter( $base, 20 );
$text_lighter_20 = woocommerce_hex_lighter( $text, 20 );
$text_lighter_40 = woocommerce_hex_lighter( $text, 40 );
$text_darker_50 = woocommerce_hex_darker( $text, 50 );

// For gmail compatibility, including CSS styles in head/body are stripped out therefore styles need to be inline. These variables contain rules which are added to the template inline.
$wrapper = "
	background-image: url(data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAkAAAAJCAYAAADgkQYQAAAANklEQVQYV2NkIAIwEqFGkpAiSaAhzwkpAluESxHYBJhTsClCUYDPJBT/IJuEYQK6dTgVgBQCAG5EBSOuEsbJAAAAAElFTkSuQmCC);
	background-repeat: repeat-y no-repeat;
	background-position: top center;
	background-color: $bg;
	width:100% !important; 
	-webkit-text-size-adjust:none; 
	margin:0; 
	padding: 70px 0 70px 0;
";
$template_container = "
	-webkit-box-shadow:0 0 0 3px rgba(0,0,0,0.025); 
	-webkit-border-radius:6px;
	background-color: $body;
	border: 1px solid $bg_darker_10;
	-webkit-border-radius:6px;
";
$template_header = "
	background-color: $base; 
	color: $base_text; 
	-webkit-border-top-left-radius:6px; 
	-webkit-border-top-right-radius:6px; 
	border-bottom: 0;
	font-family:Arial; 
	font-weight:bold; 
	line-height:100%; 
	vertical-align:middle;
";
$body_content = "
	background-color: $body;
	-webkit-border-radius:6px;
";
$body_content_inner = "
	color: $text_lighter_20;
	font-family:Arial;
	font-size:14px;
	line-height:150%;
	text-align:left;
";
$header_content_h1 = "
	color: $base_text !important; 
	margin:0; 
	padding: 28px 24px;
	text-shadow: 0 1px 0 $base_lighter_20;
";
 
?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
        <title><?php echo get_bloginfo('name'); ?></title>
		<style type="text/css">
			/* Styles for elements that are added dyanmically - we can't show them inline */
			img{ border:none; font-size:14px; font-weight:bold; height:auto; line-height:100%; outline:none; text-decoration:none; text-transform:capitalize; }
			mark { background: transparent none; color: inherit; }
			h1, .h1,
			h2, .h2,
			h3, .h3 {
				color: <?php echo $text_darker_50; ?>;
				display:block;
				font-family:Arial;
				font-size:34px;
				font-weight:bold;
				line-height:150%;
				margin-top:0;
				margin-right:0;
				margin-bottom:10px;
				margin-left:0;
				text-align:left;
				line-height: 1.5;
			}
			h2, .h2{ font-size:30px; }
			h3, .h3{ font-size:26px; }
			
			.header_content a:link, .header_content a:visited {
				color:<?php echo $base_text; ?>;
				font-weight:normal;
				text-decoration:underline;
			}
			.body_content div a:link, .body_content div a:visited {
				color: <?php echo $text; ?>;
				font-weight:normal;
				text-decoration:underline;
			}
			.body_content img{
				display:inline;
				height:auto;
			}
			.footer_content div a:link, .footer_content div a:visited {
				color:<?php echo $text_lighter_40; ?>;
				font-weight:normal;
				text-decoration:underline;
			}
		</style>
	</head>
    <body leftmargin="0" marginwidth="0" topmargin="0" marginheight="0" offset="0">
    	<center style="<?php echo $wrapper; ?>">
        	<table border="0" cellpadding="0" cellspacing="0" height="100%" width="100%">
            	<tr>
                	<td align="center" valign="top">
                		<?php
                			if ( $img = get_option( 'woocommerce_email_header_image' ) ) {
                				echo '<p style="margin-top:0;"><img src="' . $img . '" alt="' . get_bloginfo( 'name' ) . '" /></p>';
                			}
                		?>
                    	<table border="0" cellpadding="0" cellspacing="0" width="600" id="template_container" style="<?php echo $template_container; ?>">
                        	<tr>
                            	<td align="center" valign="top">
                                    <!-- Header -->
                                	<table border="0" cellpadding="0" cellspacing="0" width="600" id="template_header" style="<?php echo $template_header; ?>" bgcolor="<?php echo $base; ?>">
                                        <tr>
                                            <td class="header_content">

                                            	<h1 class="h1" style="<?php echo $header_content_h1; ?>"><?php echo $email_heading; ?></h1>
                                            	
                                            </td>
                                        </tr>
                                    </table>
                                    <!-- End Header -->
                                </td>
                            </tr>
                        	<tr>
                            	<td align="center" valign="top">
                                    <!-- Body -->
                                	<table border="0" cellpadding="0" cellspacing="0" width="600" id="template_body">
                                    	<tr>
                                            <td valign="top" class="body_content" style="<?php echo $body_content; ?>">
                                                <!-- Content -->
                                                <table border="0" cellpadding="20" cellspacing="0" width="100%">
                                                    <tr>
                                                        <td valign="top">
                                                            <div style="<?php echo $body_content_inner; ?>">