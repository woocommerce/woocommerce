<div id="linked_product_data" class="panel woocommerce_options_panel hidden">

	<div class="options_group">
		<p class="form-field">
			<label for="upsell_ids"><?php _e( 'Up-sells', 'woocommerce' ); ?></label>
			<input type="hidden" class="wc-product-search" style="width: 50%;" id="upsell_ids" name="upsell_ids" data-placeholder="<?php esc_attr_e( 'Search for a product&hellip;', 'woocommerce' ); ?>" data-action="woocommerce_json_search_products_and_variations" data-multiple="true" data-exclude="<?php echo intval( $post->ID ); ?>" data-selected="<?php
				$product_ids = $product_object->get_upsell_ids( 'edit' );
				$json_ids    = array();

				foreach ( $product_ids as $product_id ) {
					$product = wc_get_product( $product_id );
					if ( is_object( $product ) ) {
						$json_ids[ $product_id ] = wp_kses_post( html_entity_decode( $product->get_formatted_name(), ENT_QUOTES, get_bloginfo( 'charset' ) ) );
					}
				}

				echo esc_attr( json_encode( $json_ids ) );
			?>" value="<?php echo implode( ',', array_keys( $json_ids ) ); ?>" /> <?php echo wc_help_tip( __( 'Up-sells are products which you recommend instead of the currently viewed product, for example, products that are more profitable or better quality or more expensive.', 'woocommerce' ) ); ?>
		</p>

		<p class="form-field">
			<label for="crosssell_ids"><?php _e( 'Cross-sells', 'woocommerce' ); ?></label>
			<input type="hidden" class="wc-product-search" style="width: 50%;" id="crosssell_ids" name="crosssell_ids" data-placeholder="<?php esc_attr_e( 'Search for a product&hellip;', 'woocommerce' ); ?>" data-action="woocommerce_json_search_products_and_variations" data-multiple="true" data-exclude="<?php echo intval( $post->ID ); ?>" data-selected="<?php
				$product_ids = $product_object->get_cross_sell_ids( 'edit' );
				$json_ids    = array();

				foreach ( $product_ids as $product_id ) {
					$product = wc_get_product( $product_id );
					if ( is_object( $product ) ) {
						$json_ids[ $product_id ] = wp_kses_post( html_entity_decode( $product->get_formatted_name(), ENT_QUOTES, get_bloginfo( 'charset' ) ) );
					}
				}

				echo esc_attr( json_encode( $json_ids ) );
			?>" value="<?php echo implode( ',', array_keys( $json_ids ) ); ?>" /> <?php echo wc_help_tip( __( 'Cross-sells are products which you promote in the cart, based on the current product.', 'woocommerce' ) ); ?>
		</p>

		<p class="form-field show_if_grouped">
			<label for="grouped_products"><?php _e( 'Grouped products', 'woocommerce' ); ?></label>
			<input type="hidden" class="wc-product-search" style="width: 50%;" id="grouped_products" name="grouped_products" data-placeholder="<?php esc_attr_e( 'Search for a product&hellip;', 'woocommerce' ); ?>" data-action="woocommerce_json_search_products" data-multiple="true" data-exclude="<?php echo intval( $post->ID ); ?>" data-selected="<?php
			$product_ids = $product_object->get_children( 'edit' );
			$json_ids    = array();

			foreach ( $product_ids as $product_id ) {
				$product = wc_get_product( $product_id );
				if ( is_object( $product ) ) {
					$json_ids[ $product_id ] = wp_kses_post( html_entity_decode( $product->get_formatted_name(), ENT_QUOTES, get_bloginfo( 'charset' ) ) );
				}
			}

			echo esc_attr( json_encode( $json_ids ) );
			?>" value="<?php echo implode( ',', array_keys( $json_ids ) ); ?>" /> <?php echo wc_help_tip( __( 'This lets you choose which products are part of this group.', 'woocommerce' ) ); ?>
		</p>
	</div>

	<?php do_action( 'woocommerce_product_options_related' ); ?>
</div>
