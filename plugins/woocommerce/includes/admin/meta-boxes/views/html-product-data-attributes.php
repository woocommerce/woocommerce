<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div id="product_attributes" class="panel wc-metaboxes-wrapper hidden">
	<div class="toolbar toolbar-top">
		<?php
		global $wc_product_attributes;
		// Array of defined attribute taxonomies.
		$attribute_taxonomies = wc_get_attribute_taxonomies();
		// Product attributes - taxonomies and custom, ordered, with visibility and variation attributes set.
		$product_attributes = $product_object->get_attributes( 'edit' );

		if ( empty( $attribute_taxonomies ) && empty( $product_attributes ) ) :
			?>
			<div id="message" class="inline notice woocommerce-message">
				<p>
					<?php
					esc_html_e(
						'Add descriptive pieces of information that customers can use to search for this product on your store, such as “Material” or “Brand”.',
						'woocommerce'
					);
					?>
				</p>
			</div>
		<?php endif; ?>
		<span class="expand-close">
			<a href="#" class="expand_all"><?php esc_html_e( 'Expand', 'woocommerce' ); ?></a> / <a href="#" class="close_all"><?php esc_html_e( 'Close', 'woocommerce' ); ?></a>
		</span>

		<?php
		/**
		 * Filter for the attribute taxonomy filter dropdown threshold.
		 *
		 * @since 7.0.0
		 * @param number $threshold The threshold for showing the simple dropdown.
		 */
		if ( count( $attribute_taxonomies ) <= apply_filters( 'woocommerce_attribute_taxonomy_filter_threshold', 20 ) ) :
			?>
			<select name="attribute_taxonomy" class="attribute_taxonomy">
				<option value=""><?php esc_html_e( 'Custom product attribute', 'woocommerce' ); ?></option>
				<?php
				if ( ! empty( $attribute_taxonomies ) ) {
					foreach ( $attribute_taxonomies as $attr_taxonomy ) {
						$attribute_taxonomy_name = wc_attribute_taxonomy_name( $attr_taxonomy->attribute_name );
						$label                   = $attr_taxonomy->attribute_label ? $attr_taxonomy->attribute_label : $attr_taxonomy->attribute_name;
						echo '<option value="' . esc_attr( $attribute_taxonomy_name ) . '">' . esc_html( $label ) . '</option>';
					}
				}
				?>
			</select>
			<button type="button" class="button add_attribute"><?php esc_html_e( 'Add', 'woocommerce' ); ?></button>
		<?php else : ?>
			<button type="button" class="button add_custom_attribute"><?php esc_html_e( 'Add custom attribute', 'woocommerce' ); ?></button>
			<select class="wc-attribute-search attribute_taxonomy" id="attribute_taxonomy" name="attribute_taxonomy" data-placeholder="<?php esc_attr_e( 'Add existing attribute', 'woocommerce' ); ?>" data-minimum-input-length="0">
			</select>
		<?php endif; ?>
	</div>
	<div class="product_attributes wc-metaboxes">
		<?php
		$i = -1;

		foreach ( $product_attributes as $attribute ) {
			$i++;
			$metabox_class = array();

			if ( $attribute->is_taxonomy() ) {
				$metabox_class[] = 'taxonomy';
				$metabox_class[] = $attribute->get_name();
			}

			include __DIR__ . '/html-product-attribute.php';
		}
		?>
	</div>
	<div class="toolbar">
		<span class="expand-close">
			<a href="#" class="expand_all"><?php esc_html_e( 'Expand', 'woocommerce' ); ?></a> / <a href="#" class="close_all"><?php esc_html_e( 'Close', 'woocommerce' ); ?></a>
		</span>
		<button type="button" class="button save_attributes button-primary"><?php esc_html_e( 'Save attributes', 'woocommerce' ); ?></button>
	</div>
	<?php do_action( 'woocommerce_product_options_attributes' ); ?>
</div>
