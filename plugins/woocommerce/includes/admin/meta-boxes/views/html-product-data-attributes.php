<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div id="product_attributes" class="panel wc-metaboxes-wrapper hidden">
	<div class="toolbar toolbar-top">
		<span class="expand-close">
			<a href="#" class="expand_all"><?php esc_html_e( 'Expand', 'woocommerce' ); ?></a> / <a href="#" class="close_all"><?php esc_html_e( 'Close', 'woocommerce' ); ?></a>
		</span>
		<button type="button" class="button add_custom_attribute"><?php esc_html_e( 'Add custom attribute', 'woocommerce' ); ?></button>

		<select class="wc-attribute-search attribute_taxonomy" id="attribute_taxonomy" name="attribute_taxonomy" data-placeholder="<?php esc_attr_e( 'Add existing attribute', 'woocommerce' ); ?>" data-minimum-input-length="0">
		</select>
		<button type="button" class="button add_attribute"><?php esc_html_e( 'Add', 'woocommerce' ); ?></button>
	</div>
	<div class="product_attributes wc-metaboxes">
		<?php
		// Product attributes - taxonomies and custom, ordered, with visibility and variation attributes set.
		$attributes = $product_object->get_attributes( 'edit' );
		$i          = -1;

		foreach ( $attributes as $attribute ) {
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
