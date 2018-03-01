<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div id="inventory_product_data" class="panel woocommerce_options_panel hidden">

	<div class="options_group">
		<?php
			if ( wc_product_sku_enabled() ) {
				woocommerce_wp_text_input( array(
					'id'          => '_sku',
					'value'       => $product_object->get_sku( 'edit' ),
					'label'       => '<abbr title="' . esc_attr__( 'Stock Keeping Unit', 'woocommerce' ) . '">' . esc_html__( 'SKU', 'woocommerce' ) . '</abbr>',
					'desc_tip'    => true,
					'description' => __( 'SKU refers to a Stock-keeping unit, a unique identifier for each distinct product and service that can be purchased.', 'woocommerce' ),
				) );
			}

			do_action( 'woocommerce_product_options_sku' );

			if ( 'yes' === get_option( 'woocommerce_manage_stock' ) ) {

				woocommerce_wp_checkbox( array(
					'id'            => '_manage_stock',
					'value'         => $product_object->get_manage_stock( 'edit' ) ? 'yes' : 'no',
					'wrapper_class' => 'show_if_simple show_if_variable',
					'label'         => __( 'Manage stock?', 'woocommerce' ),
					'description'   => __( 'Enable stock management at product level', 'woocommerce' ),
				) );

				do_action( 'woocommerce_product_options_stock' );

				echo '<div class="stock_fields show_if_simple show_if_variable">';

				woocommerce_wp_text_input( array(
					'id'                => '_stock',
					'value'             => wc_stock_amount( $product_object->get_stock_quantity( 'edit' ) ),
					'label'             => __( 'Stock quantity', 'woocommerce' ),
					'desc_tip'          => true,
					'description'       => __( 'Stock quantity. If this is a variable product this value will be used to control stock for all variations, unless you define stock at variation level.', 'woocommerce' ),
					'type'              => 'number',
					'custom_attributes' => array(
						'step'          => 'any',
					),
					'data_type'         => 'stock',
				) );

				echo '<input type="hidden" name="_original_stock" value="' . esc_attr( wc_stock_amount( $product_object->get_stock_quantity( 'edit' ) ) ) . '" />';

				woocommerce_wp_select( array(
					'id'          => '_backorders',
					'value'       => $product_object->get_backorders( 'edit' ),
					'label'       => __( 'Allow backorders?', 'woocommerce' ),
					'options'     => wc_get_product_backorder_options(),
					'desc_tip'    => true,
					'description' => __( 'If managing stock, this controls whether or not backorders are allowed. If enabled, stock quantity can go below 0.', 'woocommerce' ),
				) );

				do_action( 'woocommerce_product_options_stock_fields' );

				echo '</div>';
			}

			woocommerce_wp_select( array(
				'id'             => '_stock_status',
				'value'          => $product_object->get_stock_status( 'edit' ),
				'wrapper_class'  => 'stock_status_field hide_if_variable hide_if_external',
				'label'          => __( 'Stock status', 'woocommerce' ),
				'options'        => wc_get_product_stock_status_options(),
				'desc_tip'       => true,
				'description'    => __( 'Controls whether or not the product is listed as "in stock" or "out of stock" on the frontend.', 'woocommerce' ),
			) );

			do_action( 'woocommerce_product_options_stock_status' );
		?>
	</div>

	<div class="options_group show_if_simple show_if_variable">
		<?php
			woocommerce_wp_checkbox( array(
				'id'            => '_sold_individually',
				'value'         => $product_object->get_sold_individually( 'edit' ) ? 'yes' : 'no',
				'wrapper_class' => 'show_if_simple show_if_variable',
				'label'         => __( 'Sold individually', 'woocommerce' ),
				'description'   => __( 'Enable this to only allow one of this item to be bought in a single order', 'woocommerce' ),
			) );

			do_action( 'woocommerce_product_options_sold_individually' );
		?>
	</div>

	<?php do_action( 'woocommerce_product_options_inventory_product_data' ); ?>
</div>
