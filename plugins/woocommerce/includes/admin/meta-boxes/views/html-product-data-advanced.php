<?php
/**
 * Product advanced data panel.
 *
 * @package WooCommerce\Admin
 * @var WC_Product $product_object
 */

use Automattic\WooCommerce\Internal\ProductFeed\Integrations\POSCatalog\POSProductVisibilitySync;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div id="advanced_product_data" class="panel woocommerce_options_panel hidden">

	<div class="options_group hide_if_external hide_if_grouped">
		<?php
		woocommerce_wp_textarea_input(
			array(
				'id'          => '_purchase_note',
				'value'       => $product_object->get_purchase_note( 'edit' ),
				'label'       => __( 'Purchase note', 'woocommerce' ),
				'desc_tip'    => true,
				'description' => __( 'Enter an optional note to send the customer after purchase.', 'woocommerce' ),
			)
		);
		?>
	</div>

	<div class="options_group">
		<?php
		woocommerce_wp_text_input(
			array(
				'id'                => 'menu_order',
				'value'             => $product_object->get_menu_order( 'edit' ),
				'label'             => __( 'Menu order', 'woocommerce' ),
				'desc_tip'          => true,
				'description'       => __( 'Custom ordering position.', 'woocommerce' ),
				'type'              => 'number',
				'custom_attributes' => array(
					'step' => '1',
				),
			)
		);
		?>
	</div>

	<?php if ( post_type_supports( 'product', 'comments' ) ) : ?>
		<div class="options_group reviews">
			<?php
				woocommerce_wp_checkbox(
					array(
						'id'      => 'comment_status',
						'value'   => $product_object->get_reviews_allowed( 'edit' ) ? 'open' : 'closed',
						'label'   => __( 'Enable reviews', 'woocommerce' ),
						'cbvalue' => 'open',
					)
				);
				do_action( 'woocommerce_product_options_reviews' );
			?>
		</div>
	<?php endif; ?>
	<?php $is_pos_supported = wc_get_container()->get( POSProductVisibilitySync::class )->is_product_supported( $product_object ); ?>
	<div class="options_group" id="pos_visibility_supported" <?php echo $is_pos_supported ? '' : 'style="display: none;"'; ?>>
		<?php
		$visible_in_pos = ! has_term( 'pos-hidden', 'pos_product_visibility', $product_object->get_id() );
		woocommerce_wp_checkbox(
			array(
				'id'          => '_visible_in_pos',
				'value'       => $visible_in_pos ? 'yes' : 'no',
				'label'       => __( 'Available for POS', 'woocommerce' ),
				'desc_tip'    => true,
				'description' => __( 'Controls whether this product appears in the Point of Sale system.', 'woocommerce' ),
			)
		);
		?>
	</div>
	<div class="options_group" id="pos_visibility_unsupported" <?php echo $is_pos_supported ? 'style="display: none;"' : ''; ?>>
		<?php
		woocommerce_wp_note(
			array(
				'id'      => '_pos_visibility_note',
				'label'   => __( 'Point of Sale', 'woocommerce' ),
				'message' => __( 'This product type is not currently supported.', 'woocommerce' ),
			)
		);
		?>
	</div>

	<?php do_action( 'woocommerce_product_options_advanced' ); ?>
</div>
