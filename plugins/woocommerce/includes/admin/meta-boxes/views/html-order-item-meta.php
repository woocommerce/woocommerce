<?php
/**
 * Shows an order item meta
 *
 * @package WooCommerce\Admin
 * @var WC_Order_Item $item The item being displayed
 * @var int $item_id The id of the item being displayed
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$hidden_order_itemmeta = \Automattic\WooCommerce\Internal\Utilities\OrderItemMetaUtil::get_hidden_keys();
?><div class="view">
	<?php
	$meta_data = $item->get_all_formatted_meta_data( '' );
	if ( $meta_data ) :
		?>
		<table cellspacing="0" class="display_meta">
			<?php
			foreach ( $meta_data as $meta_id => $meta ) :
				if ( in_array( $meta->key, $hidden_order_itemmeta, true ) ) {
					continue;
				}
				?>
				<tr>
					<th><?php echo wp_kses_post( $meta->display_key ); ?>:</th>
					<td><?php echo wp_kses_post( force_balance_tags( $meta->display_value ) ); ?></td>
				</tr>
			<?php endforeach; ?>
		</table>
	<?php endif; ?>
</div>
<div class="edit" style="display: none;">
	<table class="meta" cellspacing="0">
		<tbody class="meta_items">
			<?php if ( $meta_data ) : ?>
				<?php
				// The edit fields are saved back as they are, so they must hold the stored key and value,
				// not the URL-decoded copies made for display.
				$raw_meta_data = array();
				foreach ( $item->get_meta_data() as $raw_meta ) {
					$raw_meta_data[ $raw_meta->id ] = $raw_meta;
				}

				foreach ( $meta_data as $meta_id => $meta ) :
					if ( in_array( $meta->key, $hidden_order_itemmeta, true ) ) {
						continue;
					}

					$meta_key   = isset( $raw_meta_data[ $meta_id ] ) ? $raw_meta_data[ $meta_id ]->key : $meta->key;
					$meta_value = isset( $raw_meta_data[ $meta_id ] ) ? $raw_meta_data[ $meta_id ]->value : $meta->value;
					?>
					<tr data-meta_id="<?php echo esc_attr( $meta_id ); ?>">
						<td>
							<input type="text" maxlength="255" placeholder="<?php esc_attr_e( 'Name (required)', 'woocommerce' ); ?>" name="meta_key[<?php echo esc_attr( $item_id ); ?>][<?php echo esc_attr( $meta_id ); ?>]" value="<?php echo esc_attr( $meta_key ); ?>" />
							<textarea placeholder="<?php esc_attr_e( 'Value (required)', 'woocommerce' ); ?>" name="meta_value[<?php echo esc_attr( $item_id ); ?>][<?php echo esc_attr( $meta_id ); ?>]"><?php echo esc_textarea( (string) $meta_value ); ?></textarea>
						</td>
						<td width="1%"><button class="remove_order_item_meta button">&times;</button></td>
					</tr>
				<?php endforeach; ?>
			<?php endif; ?>
		</tbody>
		<tfoot>
			<tr>
				<td colspan="4"><button class="add_order_item_meta button"><?php esc_html_e( 'Add&nbsp;meta', 'woocommerce' ); ?></button></td>
			</tr>
		</tfoot>
	</table>
</div>
