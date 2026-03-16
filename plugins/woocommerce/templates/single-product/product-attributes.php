<?php
/**
 * Product attributes
 *
 * Used by list_attributes() in the products class.
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/single-product/product-attributes.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 9.3.0
 */

defined( 'ABSPATH' ) || exit;

if ( ! $product_attributes ) {
	return;
}
?>
<table class="woocommerce-product-attributes shop_attributes" aria-label="<?php esc_attr_e( 'Product Details', 'woocommerce' ); ?>">
	<?php foreach ( $product_attributes as $product_attribute_key => $product_attribute ) { ?>
		<?php
		$attribute_item_classes = "woocommerce-product-attributes-item--$product_attribute_key";

		if ( 0 === strpos( $product_attribute_key, 'attribute_' ) ) {
			$label_based_attribute_key = sanitize_html_class( sanitize_title( $product_attribute['label'] ) );
			$resolved_attribute_key    = "attribute_$label_based_attribute_key";

			/**
			 * Add a compatibility class when the resolved class differs from the current class.
			 *
			 * See details in https://github.com/woocommerce/woocommerce/issues/31086.
			 */
			if ( $product_attribute_key !== $resolved_attribute_key ) {
				$attribute_item_classes .= " woocommerce-product-attributes-item--$resolved_attribute_key";
			}
		}
		?>
		<tr class="woocommerce-product-attributes-item <?php echo esc_attr( $attribute_item_classes ); ?>">
			<th class="woocommerce-product-attributes-item__label" scope="row"><?php echo wp_kses_post( $product_attribute['label'] ); ?></th>
			<td class="woocommerce-product-attributes-item__value"><?php echo wp_kses_post( $product_attribute['value'] ); ?></td>
		</tr>
	<?php } ?>
</table>
