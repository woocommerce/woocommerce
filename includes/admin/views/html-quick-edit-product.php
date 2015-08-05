<?php
/**
 * Admin View: Quick Edit Product
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

?>

<fieldset class="inline-edit-col-left">
	<div id="woocommerce-fields" class="inline-edit-col">

		<h4><?php _e( 'Product Data', 'woocommerce' ); ?></h4>

		<?php do_action( 'woocommerce_product_quick_edit_start' ); ?>

		<?php if ( wc_product_sku_enabled() ) : ?>

			<label>
				<span class="title"><?php _e( 'SKU', 'woocommerce' ); ?></span>
				<span class="input-text-wrap">
					<input type="text" name="_sku" class="text sku" value="">
				</span>
			</label>
			<br class="clear" />

		<?php endif; ?>

		<div class="price_fields">
			<label>
				<span class="title"><?php _e( 'Price', 'woocommerce' ); ?></span>
				<span class="input-text-wrap">
					<input type="text" name="_regular_price" class="text regular_price" placeholder="<?php esc_attr_e( 'Regular Price', 'woocommerce' ); ?>" value="">
				</span>
			</label>
			<br class="clear" />
			<label>
				<span class="title"><?php _e( 'Sale', 'woocommerce' ); ?></span>
				<span class="input-text-wrap">
					<input type="text" name="_sale_price" class="text sale_price" placeholder="<?php esc_attr_e( 'Sale Price', 'woocommerce' ); ?>" value="">
				</span>
			</label>
			<br class="clear" />
		</div>

		<label class="alignleft">
			<span class="title"><?php _e( 'Tax Status', 'woocommerce' ); ?></span>
			<span class="input-text-wrap">
				<select class="tax_status" name="_tax_status">
				<?php
					$options = array(
						'taxable'  => __( 'Taxable', 'woocommerce' ),
						'shipping' => __( 'Shipping only', 'woocommerce' ),
						'none'     => _x( 'None', 'Tax status', 'woocommerce' )
					);
					foreach ( $options as $key => $value ) {
						echo '<option value="' . esc_attr( $key ) . '">' . $value . '</option>';
					}
				?>
				</select>
			</span>
		</label>
		<br class="clear" />
		<label class="alignleft">
			<span class="title"><?php _e( 'Tax Class', 'woocommerce' ); ?></span>
			<span class="input-text-wrap">
				<select class="tax_class" name="_tax_class">
				<?php
					$options = array(
						'' => __( 'Standard', 'woocommerce' )
					);

					$tax_classes = WC_Tax::get_tax_classes();

					if ( $tax_classes )
						foreach ( $tax_classes as $class ) {
							$options[ sanitize_title( $class ) ] = esc_html( $class );
						}

					foreach ( $options as $key => $value ) {
						echo '<option value="' . esc_attr( $key ) . '">' . $value . '</option>';
					}
				?>
				</select>
			</span>
		</label>
		<br class="clear" />

		<?php if ( wc_product_weight_enabled() || wc_product_dimensions_enabled() ) : ?>
		<div class="dimension_fields">

			<?php if ( wc_product_weight_enabled() ) : ?>
				<label>
					<span class="title"><?php _e( 'Weight', 'woocommerce' ); ?></span>
					<span class="input-text-wrap">
						<input type="text" name="_weight" class="text weight" placeholder="<?php echo wc_format_localized_decimal( 0 ); ?>" value="">
					</span>
				</label>
				<br class="clear" />
			<?php endif; ?>

			<?php if ( wc_product_dimensions_enabled() ) : ?>
				<div class="inline-edit-group dimensions">
					<div>
						<span class="title"><?php _e( 'L/W/H', 'woocommerce' ); ?></span>
						<span class="input-text-wrap">
							<input type="text" name="_length" class="text length" placeholder="<?php esc_attr_e( 'Length', 'woocommerce' ); ?>" value="">
							<input type="text" name="_width" class="text width" placeholder="<?php esc_attr_e( 'Width', 'woocommerce' ); ?>" value="">
							<input type="text" name="_height" class="text height" placeholder="<?php esc_attr_e( 'Height', 'woocommerce' ); ?>" value="">
						</span>
					</div>
				</div>
			<?php endif; ?>

		</div>
		<?php endif; ?>

		<label class="alignleft">
			<span class="title"><?php _e( 'Shipping class', 'woocommerce' ); ?></span>
			<span class="input-text-wrap">
				<select class="shipping_class" name="_shipping_class">
					<option value="_no_shipping_class"><?php _e( 'No shipping class', 'woocommerce' ); ?></option>
				<?php
					foreach ( $shipping_class as $key => $value ) {
						echo '<option value="' . esc_attr( $value->slug ) . '">'. $value->name .'</option>';
					}
				?>
				</select>
			</span>
		</label>
		<br class="clear" />

		<label class="alignleft">
			<span class="title"><?php _e( 'Visibility', 'woocommerce' ); ?></span>
			<span class="input-text-wrap">
				<select class="visibility" name="_visibility">
				<?php
					$options = apply_filters( 'woocommerce_product_visibility_options', array(
						'visible' => __( 'Catalog &amp; search', 'woocommerce' ),
						'catalog' => __( 'Catalog', 'woocommerce' ),
						'search'  => __( 'Search', 'woocommerce' ),
						'hidden'  => __( 'Hidden', 'woocommerce' )
					) );
					foreach ( $options as $key => $value ) {
						echo '<option value="' . esc_attr( $key ) . '">'. $value .'</option>';
					}
				?>
				</select>
			</span>
		</label>
		<label class="alignleft featured">
			<input type="checkbox" name="_featured" value="1">
			<span class="checkbox-title"><?php _e( 'Featured', 'woocommerce' ); ?></span>
		</label>
		<br class="clear" />
		<label class="alignleft">
			<span class="title"><?php _e( 'In stock?', 'woocommerce' ); ?></span>
			<span class="input-text-wrap">
				<select class="stock_status" name="_stock_status">
				<?php
					$options = array(
						'instock'    => __( 'In stock', 'woocommerce' ),
						'outofstock' => __( 'Out of stock', 'woocommerce' )
					);
					foreach ( $options as $key => $value ) {
						echo '<option value="' . esc_attr( $key ) .'">'. $value .'</option>';
					}
				?>
				</select>
			</span>
		</label>

		<div class="stock_fields">

			<?php if (get_option('woocommerce_manage_stock')=='yes') : ?>
				<label class="alignleft manage_stock">
					<input type="checkbox" name="_manage_stock" value="1">
					<span class="checkbox-title"><?php _e( 'Manage stock?', 'woocommerce' ); ?></span>
				</label>
				<br class="clear" />
				<label class="stock_qty_field">
					<span class="title"><?php _e( 'Stock Qty', 'woocommerce' ); ?></span>
					<span class="input-text-wrap">
						<input type="number" name="_stock" class="text stock" step="any" value="">
					</span>
				</label>
			<?php endif; ?>

		</div>

		<label class="alignleft">
			<span class="title"><?php _e( 'Backorders?', 'woocommerce' ); ?></span>
			<span class="input-text-wrap">
				<select class="backorders" name="_backorders">
				<?php
					$options = array(
						'no'     => __( 'Do not allow', 'woocommerce' ),
						'notify' => __( 'Allow, but notify customer', 'woocommerce' ),
						'yes'    => __( 'Allow', 'woocommerce' )
					);
					foreach ( $options as $key => $value ) {
						echo '<option value="' . esc_attr( $key ) . '">'. $value .'</option>';
					}
				?>
				</select>
			</span>
		</label>

		<?php do_action( 'woocommerce_product_quick_edit_end' ); ?>

		<input type="hidden" name="woocommerce_quick_edit" value="1" />
		<input type="hidden" name="woocommerce_quick_edit_nonce" value="<?php echo wp_create_nonce( 'woocommerce_quick_edit_nonce' ); ?>" />
	</div>
</fieldset>
