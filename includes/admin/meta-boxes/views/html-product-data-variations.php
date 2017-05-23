<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div id="variable_product_options" class="panel wc-metaboxes-wrapper hidden">
	<div id="variable_product_options_inner">

		<?php if ( ! count( $variation_attributes ) ) : ?>

			<div id="message" class="inline notice woocommerce-message">
				<p><?php _e( 'Before you can add a variation you need to add some variation attributes on the <strong>Attributes</strong> tab.', 'woocommerce' ); ?></p>
				<p><a class="button-primary" href="<?php echo esc_url( apply_filters( 'woocommerce_docs_url', 'https://docs.woocommerce.com/document/variable-product/', 'product-variations' ) ); ?>" target="_blank"><?php _e( 'Learn more', 'woocommerce' ); ?></a></p>
			</div>

		<?php else : ?>

			<div class="toolbar toolbar-variations-defaults">
				<div class="variations-defaults">
					<strong><?php _e( 'Default Form Values', 'woocommerce' ); ?>: <?php echo wc_help_tip( __( 'These are the attributes that will be pre-selected on the frontend.', 'woocommerce' ) ); ?></strong>
					<?php
						foreach ( $variation_attributes as $attribute ) {
							$selected_value = isset( $default_attributes[ sanitize_title( $attribute->get_name() ) ] ) ? $default_attributes[ sanitize_title( $attribute->get_name() ) ] : '';
							?>
							<select name="default_attribute_<?php echo sanitize_title( $attribute->get_name() ); ?>" data-current="<?php echo esc_attr( $selected_value ); ?>">
								<option value=""><?php printf( esc_html__( 'No default %s&hellip;', 'woocommerce' ), wc_attribute_label( $attribute->get_name() ) ); ?></option>
								<?php if ( $attribute->is_taxonomy() ) : ?>
									<?php foreach ( $attribute->get_terms() as $option ) : ?>
										<option <?php selected( $selected_value, $option->slug ); ?> value="<?php echo esc_attr( $option->slug ); ?>"><?php echo esc_html( apply_filters( 'woocommerce_variation_option_name', $option->name ) ); ?></option>
									<?php endforeach; ?>
								<?php else : ?>
									<?php foreach ( $attribute->get_options() as $option ) : ?>
										<option <?php selected( $selected_value, $option ); ?> value="<?php echo esc_attr( $option ); ?>"><?php echo esc_html( apply_filters( 'woocommerce_variation_option_name', $option ) ); ?></option>
									<?php endforeach; ?>
								<?php endif; ?>
							</select>
							<?php
						}
					?>
				</div>
				<div class="clear"></div>
			</div>

			<div class="toolbar toolbar-top">
				<select id="field_to_edit" class="variation_actions">
					<option data-global="true" value="add_variation"><?php _e( 'Add variation', 'woocommerce' ); ?></option>
					<option data-global="true" value="link_all_variations"><?php _e( 'Create variations from all attributes', 'woocommerce' ); ?></option>
					<option value="delete_all"><?php _e( 'Delete all variations', 'woocommerce' ); ?></option>
					<optgroup label="<?php esc_attr_e( 'Status', 'woocommerce' ); ?>">
						<option value="toggle_enabled"><?php _e( 'Toggle &quot;Enabled&quot;', 'woocommerce' ); ?></option>
						<option value="toggle_downloadable"><?php _e( 'Toggle &quot;Downloadable&quot;', 'woocommerce' ); ?></option>
						<option value="toggle_virtual"><?php _e( 'Toggle &quot;Virtual&quot;', 'woocommerce' ); ?></option>
					</optgroup>
					<optgroup label="<?php esc_attr_e( 'Pricing', 'woocommerce' ); ?>">
						<option value="variable_regular_price"><?php _e( 'Set regular prices', 'woocommerce' ); ?></option>
						<option value="variable_regular_price_increase"><?php _e( 'Increase regular prices (fixed amount or percentage)', 'woocommerce' ); ?></option>
						<option value="variable_regular_price_decrease"><?php _e( 'Decrease regular prices (fixed amount or percentage)', 'woocommerce' ); ?></option>
						<option value="variable_sale_price"><?php _e( 'Set sale prices', 'woocommerce' ); ?></option>
						<option value="variable_sale_price_increase"><?php _e( 'Increase sale prices (fixed amount or percentage)', 'woocommerce' ); ?></option>
						<option value="variable_sale_price_decrease"><?php _e( 'Decrease sale prices (fixed amount or percentage)', 'woocommerce' ); ?></option>
						<option value="variable_sale_schedule"><?php _e( 'Set scheduled sale dates', 'woocommerce' ); ?></option>
					</optgroup>
					<optgroup label="<?php esc_attr_e( 'Inventory', 'woocommerce' ); ?>">
						<option value="toggle_manage_stock"><?php _e( 'Toggle &quot;Manage stock&quot;', 'woocommerce' ); ?></option>
						<option value="variable_stock"><?php _e( 'Stock', 'woocommerce' ); ?></option>
                        <option value="variable_stock_status_instock"><?php _e( 'Set Status - In stock', 'woocommerce' ); ?></option>
                        <option value="variable_stock_status_outofstock"><?php _e( 'Set Status - Out of stock', 'woocommerce' ); ?></option>
					</optgroup>
					<optgroup label="<?php esc_attr_e( 'Shipping', 'woocommerce' ); ?>">
						<option value="variable_length"><?php _e( 'Length', 'woocommerce' ); ?></option>
						<option value="variable_width"><?php _e( 'Width', 'woocommerce' ); ?></option>
						<option value="variable_height"><?php _e( 'Height', 'woocommerce' ); ?></option>
						<option value="variable_weight"><?php _e( 'Weight', 'woocommerce' ); ?></option>
					</optgroup>
					<optgroup label="<?php esc_attr_e( 'Downloadable products', 'woocommerce' ); ?>">
						<option value="variable_download_limit"><?php _e( 'Download limit', 'woocommerce' ); ?></option>
						<option value="variable_download_expiry"><?php _e( 'Download expiry', 'woocommerce' ); ?></option>
					</optgroup>
					<?php do_action( 'woocommerce_variable_product_bulk_edit_actions' ); ?>
				</select>
				<a class="button bulk_edit do_variation_action"><?php _e( 'Go', 'woocommerce' ); ?></a>

				<div class="variations-pagenav">
					<span class="displaying-num"><?php printf( _n( '%s item', '%s items', $variations_count, 'woocommerce' ), $variations_count ); ?></span>
					<span class="expand-close">
						(<a href="#" class="expand_all"><?php _e( 'Expand', 'woocommerce' ); ?></a> / <a href="#" class="close_all"><?php _e( 'Close', 'woocommerce' ); ?></a>)
					</span>
					<span class="pagination-links">
						<a class="first-page disabled" title="<?php esc_attr_e( 'Go to the first page', 'woocommerce' ); ?>" href="#">&laquo;</a>
						<a class="prev-page disabled" title="<?php esc_attr_e( 'Go to the previous page', 'woocommerce' ); ?>" href="#">&lsaquo;</a>
						<span class="paging-select">
							<label for="current-page-selector-1" class="screen-reader-text"><?php _e( 'Select Page', 'woocommerce' ); ?></label>
							<select class="page-selector" id="current-page-selector-1" title="<?php esc_attr_e( 'Current page', 'woocommerce' ); ?>">
								<?php for ( $i = 1; $i <= $variations_total_pages; $i++ ) : ?>
									<option value="<?php echo $i; ?>"><?php echo $i; ?></option>
								<?php endfor; ?>
							</select>
							 <?php _ex( 'of', 'number of pages', 'woocommerce' ); ?> <span class="total-pages"><?php echo $variations_total_pages; ?></span>
						</span>
						<a class="next-page" title="<?php esc_attr_e( 'Go to the next page', 'woocommerce' ); ?>" href="#">&rsaquo;</a>
						<a class="last-page" title="<?php esc_attr_e( 'Go to the last page', 'woocommerce' ); ?>" href="#">&raquo;</a>
					</span>
				</div>
				<div class="clear"></div>
			</div>

			<div class="woocommerce_variations wc-metaboxes" data-attributes="<?php
				// esc_attr does not double encode - htmlspecialchars does
				echo htmlspecialchars( json_encode( wc_list_pluck( $variation_attributes, 'get_data' ) ) );
			?>" data-total="<?php echo $variations_count; ?>" data-total_pages="<?php echo $variations_total_pages; ?>" data-page="1" data-edited="false">
			</div>

			<div class="toolbar">
				<button type="button" class="button-primary save-variation-changes" disabled="disabled"><?php _e( 'Save changes', 'woocommerce' ); ?></button>
				<button type="button" class="button cancel-variation-changes" disabled="disabled"><?php _e( 'Cancel', 'woocommerce' ); ?></button>

				<div class="variations-pagenav">
					<span class="displaying-num"><?php printf( _n( '%s item', '%s items', $variations_count, 'woocommerce' ), $variations_count ); ?></span>
					<span class="expand-close">
						(<a href="#" class="expand_all"><?php _e( 'Expand', 'woocommerce' ); ?></a> / <a href="#" class="close_all"><?php _e( 'Close', 'woocommerce' ); ?></a>)
					</span>
					<span class="pagination-links">
						<a class="first-page disabled" title="<?php esc_attr_e( 'Go to the first page', 'woocommerce' ); ?>" href="#">&laquo;</a>
						<a class="prev-page disabled" title="<?php esc_attr_e( 'Go to the previous page', 'woocommerce' ); ?>" href="#">&lsaquo;</a>
						<span class="paging-select">
							<label for="current-page-selector-1" class="screen-reader-text"><?php _e( 'Select Page', 'woocommerce' ); ?></label>
							<select class="page-selector" id="current-page-selector-1" title="<?php esc_attr_e( 'Current page', 'woocommerce' ); ?>">
								<?php for ( $i = 1; $i <= $variations_total_pages; $i++ ) : ?>
									<option value="<?php echo $i; ?>"><?php echo $i; ?></option>
								<?php endfor; ?>
							</select>
							 <?php _ex( 'of', 'number of pages', 'woocommerce' ); ?> <span class="total-pages"><?php echo $variations_total_pages; ?></span>
						</span>
						<a class="next-page" title="<?php esc_attr_e( 'Go to the next page', 'woocommerce' ); ?>" href="#">&rsaquo;</a>
						<a class="last-page" title="<?php esc_attr_e( 'Go to the last page', 'woocommerce' ); ?>" href="#">&raquo;</a>
					</span>
				</div>
				<div class="clear"></div>
			</div>

		<?php endif; ?>
	</div>
</div>
