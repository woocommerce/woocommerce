<?php
/**
 * Sorting
 *
 * @author 		WooThemes
 * @package 	WooCommerce/Templates
 * @version     2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

global $woocommerce, $wp_query;

if ( ! woocommerce_products_will_display() || $wp_query->post_count == 1 )
	return;
?>
<form class="woocommerce-ordering" method="post">
	<select name="sort" class="orderby">
		<?php
			$catalog_orderby = apply_filters( 'woocommerce_catalog_orderby', array(
				'menu_order' 	=> __( 'Default sorting', 'woocommerce' ),
				'title' 		=> __( 'Sort alphabetically', 'woocommerce' ),
				'date' 			=> __( 'Sort by most recent', 'woocommerce' ),
				'price' 		=> __( 'Sort by price - low to high', 'woocommerce' ),
				'high_price' 	=> __( 'Sort by price - high to low', 'woocommerce' )
			) );

			foreach ( $catalog_orderby as $id => $name )
				echo '<option value="' . esc_attr( $id ) . '" ' . selected( $woocommerce->session->orderby, $id, false ) . '>' . esc_attr( $name ) . '</option>';
		?>
	</select>
</form>
