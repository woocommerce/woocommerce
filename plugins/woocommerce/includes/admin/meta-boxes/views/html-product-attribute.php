<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
if ( ! isset( $attribute ) ) {
	return;
}
?>
<div data-taxonomy="<?php echo esc_attr( $attribute->get_taxonomy() ); ?>" class="woocommerce_attribute wc-metabox postbox closed <?php echo esc_attr( implode( ' ', $metabox_class ) ); ?>" rel="<?php echo esc_attr( $attribute->get_position() ); ?>">
	<h3>
		<div class="handlediv" title="<?php esc_attr_e( 'Click to toggle', 'woocommerce' ); ?>"></div>
		<div class="tips sort" data-tip="<?php esc_attr_e( 'Drag and drop to set admin attribute order', 'woocommerce' ); ?>"></div>
		<a href="#" class="remove_row delete"><?php esc_html_e( 'Remove', 'woocommerce' ); ?></a>
		<strong class="attribute_name<?php echo esc_attr( $attribute->get_name() === '' ? ' placeholder' : '' ); ?>"><?php echo esc_html( $attribute->get_name() !== '' ? wc_attribute_label( $attribute->get_name() ) : __( 'New attribute', 'woocommerce' ) ); ?></strong>
		<?php if ( $attribute->is_taxonomy() ) : ?>
			<?php
			/* translators: 'Global' refers to 'global attribute'. */
			$global_attribute_badge_label = __( 'Global', 'woocommerce' );
			?>
			<span class="woocommerce-attribute-global-badge"><?php echo esc_html( $global_attribute_badge_label ); ?></span>
		<?php endif; ?>
	</h3>
	<div class="woocommerce_attribute_data wc-metabox-content hidden">
		<?php require __DIR__ . '/html-product-attribute-inner.php'; ?>
	</div>
</div>
