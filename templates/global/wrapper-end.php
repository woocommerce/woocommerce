<?php
/**
 * Content wrappers
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/global/wrapper-end.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see 	    https://docs.woothemes.com/document/template-structure/
 * @author 		WooThemes
 * @package 	WooCommerce/Templates
 * @version     1.6.4
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

$template = get_option( 'template' );

switch( $template ) {
	case 'twentyeleven' :
		echo '</div>';
		get_sidebar( 'shop' );
		echo '</div>';
		break;
	case 'twentytwelve' :
		echo '</div></div>';
		break;
	case 'twentythirteen' :
		echo '</div></div>';
		break;
	case 'twentyfourteen' :
		echo '</div></div></div>';
		get_sidebar( 'content' );
		break;
	case 'twentyfifteen' :
		echo '</div></div>';
		break;
	case 'twentysixteen' :
		echo '</main></div>';
		break;
	default :
		echo '</div></div>';
		break;
}
