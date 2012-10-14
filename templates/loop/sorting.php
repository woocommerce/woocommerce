<?php
/**
 * Sorting
 *
 * @author 		WooThemes
 * @package 	WooCommerce/Templates
 * @version     1.7
 */
global $woocommerce;

if ( ! woocommerce_products_will_display() )
	return;
?>
<form class="woocommerce_ordering" method="post">
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
				echo '<option value="' . $id . '" ' . selected( $woocommerce->session->orderby, $id, false ) . '>' . $name . '</option>';
		?>
	</select>
</form>
