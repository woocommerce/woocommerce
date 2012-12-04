<?php
/**
 * Result Count
 *
 * Shows text: Showing x - x of x results
 *
 * @author 		WooThemes
 * @package 	WooCommerce/Templates
 * @version     2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

global $woocommerce, $wp_query;

if ( ! woocommerce_products_will_display() )
	return;
?>
<p class="woocommerce_result_count">
	<?php
	$paged 		= max( 1, $wp_query->get( 'paged' ) );
	$per_page 	= $wp_query->get( 'posts_per_page' );
	$max		= $wp_query->found_posts;
	$first 		= ( $per_page * $paged ) - $per_page + 1;
	$last 		= $wp_query->get( 'posts_per_page' ) * $paged;

	if ( $last > $max )
		$last = $max;

	printf( __( 'Showing %s - %s of %s results', 'woocommerce' ), $first, $last, $max );
	?>
</p>