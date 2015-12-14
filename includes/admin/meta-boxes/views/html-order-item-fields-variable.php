<?php
/**
 * Shows configuration fields for new order items.
 *
 * @var array $fields Fields for display
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

foreach ( $item_fields as $field ) {
	?>
	<h3><?php echo esc_html( $field['description'] ); ?></h3>
	<input type="hidden" name="add_order_item" value="<?php echo $_product->id; ?>"/>
	<div class="order-item-field-<?php echo esc_attr( $field['id'] ); ?>">
		<?php do_action( 'woocommerce_get_order_item_' . $field['id'] . '_fields', $_product, $order_id ); ?>
	</div>
	<?php
}
