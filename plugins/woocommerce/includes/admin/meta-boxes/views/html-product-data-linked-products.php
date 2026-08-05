<?php
/**
 * Linked product options.
 *
 * @package WooCommerce\Admin
 */

use Automattic\WooCommerce\Enums\ProductType;

defined( 'ABSPATH' ) || exit;

// In WP 7.0, we made the inputs wider.
// @see https://github.com/woocommerce/woocommerce/pull/63779/changes#diff-dfef13b204157e98982fb3af978fbabfaa7f6bedd31bf02f2c9070718d59642eR8402-R8408.
$version = get_bloginfo( 'version' );

if ( $version ) {
	$version_parts = explode( '-', $version );
	$version       = count( $version_parts ) > 1 ? $version_parts[0] : $version;
}

$width = $version && version_compare( $version, '7.0', '>=' ) ? 'width: 55%;' : 'width: 50%;';
?>
<div id="linked_product_data" class="panel woocommerce_options_panel hidden">

	<div class="options_group show_if_grouped">
		<p class="form-field">
			<label for="grouped_products"><?php esc_html_e( 'Grouped products', 'woocommerce' ); ?></label>
			<select class="wc-product-search" multiple="multiple" style="<?php echo esc_attr( $width ); ?>" id="grouped_products" name="grouped_products[]" data-sortable="true" data-placeholder="<?php esc_attr_e( 'Search for a product&hellip;', 'woocommerce' ); ?>" data-action="woocommerce_json_search_products" data-exclude="<?php echo intval( $post->ID ); ?>">
				<?php
				$product_ids = $product_object->is_type( ProductType::GROUPED ) ? $product_object->get_children( 'edit' ) : array();

				if ( ! empty( $product_ids ) ) {
					// Prime caches to reduce future queries.
					_prime_post_caches( $product_ids );
				}

				foreach ( $product_ids as $product_id ) {
					$product = wc_get_product( $product_id );
					if ( is_object( $product ) ) {
						echo '<option value="' . esc_attr( $product_id ) . '"' . selected( true, true, false ) . '>' . esc_html( wp_strip_all_tags( $product->get_formatted_name() ) ) . '</option>';
					}
				}
				?>
			</select> <?php echo wc_help_tip( __( 'This lets you choose which products are part of this group.', 'woocommerce' ) ); // WPCS: XSS ok. ?>
		</p>
	</div>

	<div class="options_group">
		<p class="form-field">
			<label for="upsell_ids"><?php esc_html_e( 'Upsells', 'woocommerce' ); ?></label>
			<select class="wc-product-search" multiple="multiple" style="<?php echo esc_attr( $width ); ?>" id="upsell_ids" name="upsell_ids[]" data-placeholder="<?php esc_attr_e( 'Search for a product&hellip;', 'woocommerce' ); ?>" data-action="woocommerce_json_search_products_and_variations" data-exclude="<?php echo intval( $post->ID ); ?>">
				<?php
				$product_ids = $product_object->get_upsell_ids( 'edit' );

				if ( ! empty( $product_ids ) ) {
					// Prime caches to reduce future queries.
					_prime_post_caches( $product_ids );
				}

				foreach ( $product_ids as $product_id ) {
					$product = wc_get_product( $product_id );
					if ( is_object( $product ) ) {
						echo '<option value="' . esc_attr( $product_id ) . '"' . selected( true, true, false ) . '>' . esc_html( wp_strip_all_tags( $product->get_formatted_name() ) ) . '</option>';
					}
				}
				?>
			</select> <?php echo wc_help_tip( __( 'Upsells are products which you recommend instead of the currently viewed product, for example, products that are more profitable or better quality or more expensive.', 'woocommerce' ) ); // WPCS: XSS ok. ?>
		</p>

		<p class="form-field hide_if_grouped hide_if_external">
			<label for="crosssell_ids"><?php esc_html_e( 'Cross-sells', 'woocommerce' ); ?></label>
			<select class="wc-product-search" multiple="multiple" style="<?php echo esc_attr( $width ); ?>" id="crosssell_ids" name="crosssell_ids[]" data-placeholder="<?php esc_attr_e( 'Search for a product&hellip;', 'woocommerce' ); ?>" data-action="woocommerce_json_search_products_and_variations" data-exclude="<?php echo intval( $post->ID ); ?>">
				<?php
				$product_ids = $product_object->get_cross_sell_ids( 'edit' );

				if ( ! empty( $product_ids ) ) {
					// Prime caches to reduce future queries.
					_prime_post_caches( $product_ids );
				}

				foreach ( $product_ids as $product_id ) {
					$product = wc_get_product( $product_id );
					if ( is_object( $product ) ) {
						echo '<option value="' . esc_attr( $product_id ) . '"' . selected( true, true, false ) . '>' . esc_html( wp_strip_all_tags( $product->get_formatted_name() ) ) . '</option>';
					}
				}
				?>
			</select> <?php echo wc_help_tip( __( 'Cross-sells are products which you promote in the cart, based on the current product.', 'woocommerce' ) ); // WPCS: XSS ok. ?>
		</p>
	</div>

	<?php do_action( 'woocommerce_product_options_related' ); ?>
</div>
