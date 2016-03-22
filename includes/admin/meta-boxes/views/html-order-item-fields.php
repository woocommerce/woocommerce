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
	<h2><?php echo esc_html( $field['field_title'] ); ?></h2>
	<input type="hidden" name="add_order_item" value="<?php echo $_product->id; ?>"/>
	<div class="order-item-field order-item-field-<?php echo esc_attr( $field['field_id'] ); ?>">
		<?php echo $field['field_html']; ?>
	</div>
	<?php
}
