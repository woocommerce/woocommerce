<div id="advanced_product_data" class="panel woocommerce_options_panel hidden">

	<div class="options_group hide_if_external">
		<?php
			woocommerce_wp_textarea_input( array(
				'id'          => '_purchase_note',
				'value'       => $product_object->get_purchase_note( 'edit' ),
				'label'       => __( 'Purchase note', 'woocommerce' ),
				'desc_tip'    => true,
				'description' => __( 'Enter an optional note to send the customer after purchase.', 'woocommerce' ),
			) );
		?>
	</div>

	<div class="options_group">
		<?php
			woocommerce_wp_text_input( array(
				'id'                => 'menu_order',
				'value'             => $product_object->get_menu_order( 'edit' ),
				'label'             => __( 'Menu order', 'woocommerce' ),
				'desc_tip'          => true,
				'description'       => __( 'Custom ordering position.', 'woocommerce' ),
				'type'              => 'number',
				'custom_attributes' => array(
					'step' 	=> '1',
				),
			) );
		?>
	</div>

	<div class="options_group reviews">
		<?php
			woocommerce_wp_checkbox( array(
				'id'      => '_reviews_allowed',
				'value'   => $product_object->get_reviews_allowed( 'edit' ) ? 'open' : 'closed',
				'label'   => __( 'Enable reviews', 'woocommerce' ),
				'cbvalue' => 'open',
			) );
			do_action( 'woocommerce_product_options_reviews' );
		?>
	</div>

	<?php do_action( 'woocommerce_product_options_advanced' ); ?>
</div>
