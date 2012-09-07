<?php
/**
 * Sorting
 *
 * @author 		WooThemes
 * @package 	WooCommerce/Templates
 * @version     1.7
 */
global $woocommerce;
?>
<form class="woocommerce_ordering" method="POST">
	<select name="sort" class="orderby">
		<?php
			$catalog_orderby = apply_filters('woocommerce_catalog_orderby', array(
				'menu_order' 	=> __('Default sorting', 'woocommerce'),
				'title' 		=> __('Sort alphabetically', 'woocommerce'),
				'date' 			=> __('Sort by most recent', 'woocommerce'),
				'price' 		=> __('Sort by price', 'woocommerce')
			));

			foreach ( $catalog_orderby as $id => $name )
				echo '<option value="' . $id . '" ' . selected( $woocommerce->session->orderby, $id, false ) . '>' . $name . '</option>';
		?>
	</select>
</form>