<?php
/**
 * Outputs a variation
 *
 * @var int $variation_id
 * @var WP_POST $variation
 * @var array $variation_data array of variation data
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

extract( $variation_data );
?>
<div class="woocommerce_variation wc-metabox closed">
	<h3>
		<a href="#" class="remove_variation delete" rel="<?php echo esc_attr( $variation_id ); ?>"><?php _e( 'Remove', 'woocommerce' ); ?></a>
		<div class="handlediv" title="<?php esc_attr_e( 'Click to toggle', 'woocommerce' ); ?>"></div>
		<div class="tips sort" data-tip="<?php esc_attr_e( 'Drag and drop, or click to set admin variation order', 'woocommerce' ); ?>"></div>
		<strong>#<?php echo esc_html( $variation_id ); ?>: </strong>
		<?php
			foreach ( $parent_data['attributes'] as $attribute ) {

				// Only deal with attributes that are variations
				if ( ! $attribute['is_variation'] || 'false' === $attribute['is_variation'] ) {
					continue;
				}

				// Get current value for variation (if set)
				$variation_selected_value = isset( $variation_data[ 'attribute_' . sanitize_title( $attribute['name'] ) ] ) ? $variation_data[ 'attribute_' . sanitize_title( $attribute['name'] ) ] : '';

				// Name will be something like attribute_pa_color
				echo '<select name="attribute_' . sanitize_title( $attribute['name'] ) . '[' . $loop . ']"><option value="">' . __( 'Any', 'woocommerce' ) . ' ' . esc_html( wc_attribute_label( $attribute['name'] ) ) . '&hellip;</option>';

				// Get terms for attribute taxonomy or value if its a custom attribute
				if ( $attribute['is_taxonomy'] ) {

					$post_terms = wp_get_post_terms( $parent_data['id'], $attribute['name'] );

					foreach ( $post_terms as $term ) {
						echo '<option ' . selected( $variation_selected_value, $term->slug, false ) . ' value="' . esc_attr( $term->slug ) . '">' . apply_filters( 'woocommerce_variation_option_name', esc_html( $term->name ) ) . '</option>';
					}

				} else {

					$options = wc_get_text_attributes( $attribute['value'] );

					foreach ( $options as $option ) {
						$selected = sanitize_title( $variation_selected_value ) === $variation_selected_value ? selected( $variation_selected_value, sanitize_title( $option ), false ) : selected( $variation_selected_value, $option, false );
						echo '<option ' . $selected . ' value="' . esc_attr( $option ) . '">' . esc_html( apply_filters( 'woocommerce_variation_option_name', $option ) ) . '</option>';
					}

				}

				echo '</select>';
			}
		?>
		<input type="hidden" name="variable_post_id[<?php echo $loop; ?>]" value="<?php echo esc_attr( $variation_id ); ?>" />
		<input type="hidden" class="variation_menu_order" name="variation_menu_order[<?php echo $loop; ?>]" value="<?php echo isset( $menu_order ) ? absint( $menu_order ) : 0; ?>" />
	</h3>
	<div class="woocommerce_variable_attributes wc-metabox-content" style="display: none;">
		<div class="data">
			<p class="form-row form-row-first upload_image">
				<a href="#" class="upload_image_button tips <?php if ( $_thumbnail_id > 0 ) echo 'remove'; ?>" data-tip="<?php if ( $_thumbnail_id > 0 ) { echo __( 'Remove this image', 'woocommerce' ); } else { echo __( 'Upload an image', 'woocommerce' ); } ?>" rel="<?php echo esc_attr( $variation_id ); ?>"><img src="<?php if ( ! empty( $image ) ) echo esc_attr( $image ); else echo esc_attr( wc_placeholder_img_src() ); ?>" /><input type="hidden" name="upload_image_id[<?php echo $loop; ?>]" class="upload_image_id" value="<?php echo esc_attr( $_thumbnail_id ); ?>" /></a>
			</p>
			<?php if ( wc_product_sku_enabled() ) : ?>
				<p class="sku form-row form-row-last">
					<label><?php _e( 'SKU', 'woocommerce' ); ?>: <a class="tips" data-tip="<?php esc_attr_e( 'Enter a SKU for this variation or leave blank to use the parent product SKU.', 'woocommerce' ); ?>" href="#">[?]</a></label>
					<input type="text" size="5" name="variable_sku[<?php echo $loop; ?>]" value="<?php if ( isset( $_sku ) ) echo esc_attr( $_sku ); ?>" placeholder="<?php echo esc_attr( $parent_data['sku'] ); ?>" />
				</p>
			<?php else : ?>
				<input type="hidden" name="variable_sku[<?php echo $loop; ?>]" value="<?php if ( isset( $_sku ) ) echo esc_attr( $_sku ); ?>" />
			<?php endif; ?>

			<p class="form-row form-row-full options">
				<label><input type="checkbox" class="checkbox" name="variable_enabled[<?php echo $loop; ?>]" <?php checked( $variation->post_status, 'publish' ); ?> /> <?php _e( 'Enabled', 'woocommerce' ); ?></label>

				<label><input type="checkbox" class="checkbox variable_is_downloadable" name="variable_is_downloadable[<?php echo $loop; ?>]" <?php checked( isset( $_downloadable ) ? $_downloadable : '', 'yes' ); ?> /> <?php _e( 'Downloadable', 'woocommerce' ); ?> <a class="tips" data-tip="<?php esc_attr_e( 'Enable this option if access is given to a downloadable file upon purchase of a product', 'woocommerce' ); ?>" href="#">[?]</a></label>

				<label><input type="checkbox" class="checkbox variable_is_virtual" name="variable_is_virtual[<?php echo $loop; ?>]" <?php checked( isset( $_virtual ) ? $_virtual : '', 'yes' ); ?> /> <?php _e( 'Virtual', 'woocommerce' ); ?> <a class="tips" data-tip="<?php esc_attr_e( 'Enable this option if a product is not shipped or there is no shipping cost', 'woocommerce' ); ?>" href="#">[?]</a></label>

				<?php if ( get_option( 'woocommerce_manage_stock' ) == 'yes' ) : ?>

					<label><input type="checkbox" class="checkbox variable_manage_stock" name="variable_manage_stock[<?php echo $loop; ?>]" <?php checked( isset( $_manage_stock ) ? $_manage_stock : '', 'yes' ); ?> /> <?php _e( 'Manage stock?', 'woocommerce' ); ?> <a class="tips" data-tip="<?php esc_attr_e( 'Enable this option to enable stock management at variation level', 'woocommerce' ); ?>" href="#">[?]</a></label>

				<?php endif; ?>

				<?php do_action( 'woocommerce_variation_options', $loop, $variation_data, $variation ); ?>
			</p>

			<div class="variable_pricing">
				<p class="form-row form-row-first">
					<label><?php echo __( 'Regular Price:', 'woocommerce' ) . ' (' . get_woocommerce_currency_symbol() . ')'; ?></label>
					<input type="text" size="5" name="variable_regular_price[<?php echo $loop; ?>]" value="<?php if ( isset( $_regular_price ) ) echo esc_attr( $_regular_price ); ?>" class="wc_input_price" placeholder="<?php esc_attr_e( 'Variation price (required)', 'woocommerce' ); ?>" />
				</p>
				<p class="form-row form-row-last">
					<label><?php echo __( 'Sale Price:', 'woocommerce' ) . ' (' . get_woocommerce_currency_symbol() . ')'; ?> <a href="#" class="sale_schedule"><?php _e( 'Schedule', 'woocommerce' ); ?></a><a href="#" class="cancel_sale_schedule" style="display:none"><?php _e( 'Cancel schedule', 'woocommerce' ); ?></a></label>
					<input type="text" size="5" name="variable_sale_price[<?php echo $loop; ?>]" value="<?php if ( isset( $_sale_price ) ) echo esc_attr( $_sale_price ); ?>" class="wc_input_price" />
				</p>

				<div class="sale_price_dates_fields" style="display: none">
					<p class="form-row form-row-first">
						<label><?php _e( 'Sale start date:', 'woocommerce' ); ?></label>
						<input type="text" class="sale_price_dates_from" name="variable_sale_price_dates_from[<?php echo $loop; ?>]" value="<?php echo ! empty( $_sale_price_dates_from ) ? date_i18n( 'Y-m-d', $_sale_price_dates_from ) : ''; ?>" placeholder="<?php echo esc_attr_x( 'From&hellip;', 'placeholder', 'woocommerce' ) ?> YYYY-MM-DD" maxlength="10" pattern="[0-9]{4}-(0[1-9]|1[012])-(0[1-9]|1[0-9]|2[0-9]|3[01])" />
					</p>
					<p class="form-row form-row-last">
						<label><?php _e( 'Sale end date:', 'woocommerce' ); ?></label>
						<input type="text" class="sale_price_dates_to" name="variable_sale_price_dates_to[<?php echo $loop; ?>]" value="<?php echo ! empty( $_sale_price_dates_to ) ? date_i18n( 'Y-m-d', $_sale_price_dates_to ) : ''; ?>" placeholder="<?php echo esc_attr_x('To&hellip;', 'placeholder', 'woocommerce') ?> YYYY-MM-DD" maxlength="10" pattern="[0-9]{4}-(0[1-9]|1[012])-(0[1-9]|1[0-9]|2[0-9]|3[01])" />
					</p>
				</div>
			</div>

			<?php if ( 'yes' == get_option( 'woocommerce_manage_stock' ) ) : ?>
				<div class="show_if_variation_manage_stock" style="display: none;">
					<p class="form-row form-row-first">
						<label><?php _e( 'Stock Qty:', 'woocommerce' ); ?> <a class="tips" data-tip="<?php esc_attr_e( 'Enter a quantity to enable stock management at variation level, or leave blank to use the parent product\'s options.', 'woocommerce' ); ?>" href="#">[?]</a></label>
						<input type="number" size="5" name="variable_stock[<?php echo $loop; ?>]" value="<?php if ( isset( $_stock ) ) echo esc_attr( wc_stock_amount( $_stock ) ); ?>" step="any" />
					</p>
					<p class="form-row form-row-last">
						<label><?php _e( 'Allow Backorders?', 'woocommerce' ); ?></label>
						<select name="variable_backorders[<?php echo $loop; ?>]">
							<?php
								foreach ( $parent_data['backorder_options'] as $key => $value ) {
									echo '<option value="' . esc_attr( $key ) . '" ' . selected( $key === $_backorders, true, false ) . '>' . esc_html( $value ) . '</option>';
								}
							?>
						</select>
					</p>
				</div>
			<?php endif; ?>

			<div class="">
				<p class="form-row form-row-full">
					<label><?php _e( 'Stock status', 'woocommerce' ); ?> <a class="tips" data-tip="<?php esc_attr_e( 'Controls whether or not the product is listed as "in stock" or "out of stock" on the frontend.', 'woocommerce' ); ?>" href="#">[?]</a></label>
					<select name="variable_stock_status[<?php echo $loop; ?>]">
						<?php
							foreach ( $parent_data['stock_status_options'] as $key => $value ) {
								echo '<option value="' . esc_attr( $key === $_stock_status ? '' : $key ) . '" ' . selected( $key === $_stock_status, true, false ) . '>' . esc_html( $value ) . '</option>';
							}
						?>
					</select>
				</p>
			</div>

			<?php if ( wc_product_weight_enabled() || wc_product_dimensions_enabled() ) : ?>
				<div>
					<?php if ( wc_product_weight_enabled() ) : ?>
						<p class="form-row hide_if_variation_virtual form-row-first">
							<label><?php echo __( 'Weight', 'woocommerce' ) . ' (' . esc_html( get_option( 'woocommerce_weight_unit' ) ) . '):'; ?> <a class="tips" data-tip="<?php esc_attr_e( 'Enter a weight for this variation or leave blank to use the parent product weight.', 'woocommerce' ); ?>" href="#">[?]</a></label>
							<input type="text" size="5" name="variable_weight[<?php echo $loop; ?>]" value="<?php if ( isset( $_weight ) ) echo esc_attr( $_weight ); ?>" placeholder="<?php echo esc_attr( $parent_data['weight'] ); ?>" class="wc_input_decimal" />
						</p>
					<?php else : ?>
						<p>&nbsp;</p>
					<?php endif; ?>
					<?php if ( wc_product_dimensions_enabled() ) : ?>
						<p class="form-row dimensions_field hide_if_variation_virtual form-row-last">
							<label for="product_length"><?php echo __( 'Dimensions (L&times;W&times;H)', 'woocommerce' ) . ' (' . esc_html( get_option( 'woocommerce_dimension_unit' ) ) . '):'; ?></label>
							<input id="product_length" class="input-text wc_input_decimal" size="6" type="text" name="variable_length[<?php echo $loop; ?>]" value="<?php if ( isset( $_length ) ) echo esc_attr( $_length ); ?>" placeholder="<?php echo esc_attr( $parent_data['length'] ); ?>" />
							<input class="input-text wc_input_decimal" size="6" type="text" name="variable_width[<?php echo $loop; ?>]" value="<?php if ( isset( $_width ) ) echo esc_attr( $_width ); ?>" placeholder="<?php echo esc_attr( $parent_data['width'] ); ?>" />
							<input class="input-text wc_input_decimal last" size="6" type="text" name="variable_height[<?php echo $loop; ?>]" value="<?php if ( isset( $_height ) ) echo esc_attr( $_height ); ?>" placeholder="<?php echo esc_attr( $parent_data['height'] ); ?>" />
						</p>
					<?php else : ?>
						<p>&nbsp;</p>
					<?php endif; ?>
				</div>
			<?php endif; ?>
			<div>
				<p class="form-row hide_if_variation_virtual form-row-full"><label><?php _e( 'Shipping class:', 'woocommerce' ); ?></label> <?php
					$args = array(
						'taxonomy' 			=> 'product_shipping_class',
						'hide_empty'		=> 0,
						'show_option_none' 	=> __( 'Same as parent', 'woocommerce' ),
						'name' 				=> 'variable_shipping_class[' . $loop . ']',
						'id'				=> '',
						'selected'			=> isset( $shipping_class ) ? esc_attr( $shipping_class ) : '',
						'echo'				=> 0
					);

					echo wp_dropdown_categories( $args );
				?></p>

				<?php if ( wc_tax_enabled() ) : ?>
					<p class="form-row form-row-full">
						<label><?php _e( 'Tax class:', 'woocommerce' ); ?></label>
						<select name="variable_tax_class[<?php echo $loop; ?>]">
							<option value="parent" <?php selected( is_null( $_tax_class ), true ); ?>><?php _e( 'Same as parent', 'woocommerce' ); ?></option>
							<?php
							foreach ( $parent_data['tax_class_options'] as $key => $value )
								echo '<option value="' . esc_attr( $key ) . '" ' . selected( $key === $_tax_class, true, false ) . '>' . esc_html( $value ) . '</option>';
						?></select>
					</p>
				<?php endif; ?>

				<p class="form-row form-row-full">
					<label><?php _e( 'Variation Description:', 'woocommerce' ); ?></label>
					<textarea name="variable_description[<?php echo $loop; ?>]" rows="3" style="width:100%;"><?php echo isset( $variation_data['_variation_description'] ) ? esc_textarea( $variation_data['_variation_description'] ) : ''; ?></textarea>
				</p>
			</div>
			<div class="show_if_variation_downloadable" style="display: none;">
				<div class="form-row form-row-full downloadable_files">
					<label><?php _e( 'Downloadable Files', 'woocommerce' ); ?>:</label>
					<table class="widefat">
						<thead>
							<div>
								<th><?php _e( 'Name', 'woocommerce' ); ?> <span class="tips" data-tip="<?php esc_attr_e( 'This is the name of the download shown to the customer.', 'woocommerce' ); ?>">[?]</span></th>
								<th colspan="2"><?php _e( 'File URL', 'woocommerce' ); ?> <span class="tips" data-tip="<?php esc_attr_e( 'This is the URL or absolute path to the file which customers will get access to. URLs entered here should already be encoded.', 'woocommerce' ); ?>">[?]</span></th>
								<th>&nbsp;</th>
							</div>
						</thead>
						<tbody>
							<?php
							if ( $_downloadable_files ) {
								foreach ( $_downloadable_files as $key => $file ) {
									if ( ! is_array( $file ) ) {
										$file = array(
											'file' => $file,
											'name' => ''
										);
									}
									include( 'html-product-variation-download.php' );
								}
							}
							?>
						</tbody>
						<tfoot>
							<div>
								<th colspan="4">
									<a href="#" class="button insert" data-row="<?php
										$file = array(
											'file' => '',
											'name' => ''
										);
										ob_start();
										include( 'html-product-variation-download.php' );
										echo esc_attr( ob_get_clean() );
									?>"><?php _e( 'Add File', 'woocommerce' ); ?></a>
								</th>
							</div>
						</tfoot>
					</table>
				</div>
			</div>
			<div class="show_if_variation_downloadable" style="display: none;">
				<p class="form-row form-row-first">
					<label><?php _e( 'Download Limit:', 'woocommerce' ); ?> <a class="tips" data-tip="<?php esc_attr_e( 'Leave blank for unlimited re-downloads.', 'woocommerce' ); ?>" href="#">[?]</a></label>
					<input type="number" size="5" name="variable_download_limit[<?php echo $loop; ?>]" value="<?php if ( isset( $_download_limit ) ) echo esc_attr( $_download_limit ); ?>" placeholder="<?php esc_attr_e( 'Unlimited', 'woocommerce' ); ?>" step="1" min="0" />
				</p>
				<p class="form-row form-row-last">
					<label><?php _e( 'Download Expiry:', 'woocommerce' ); ?> <a class="tips" data-tip="<?php esc_attr_e( 'Enter the number of days before a download link expires, or leave blank.', 'woocommerce' ); ?>" href="#">[?]</a></label>
					<input type="number" size="5" name="variable_download_expiry[<?php echo $loop; ?>]" value="<?php if ( isset( $_download_expiry ) ) echo esc_attr( $_download_expiry ); ?>" placeholder="<?php esc_attr_e( 'Unlimited', 'woocommerce' ); ?>" step="1" min="0" />
				</p>
			</div>
			<?php do_action( 'woocommerce_product_after_variable_attributes', $loop, $variation_data, $variation ); ?>
		</div>
	</div>
</div>
