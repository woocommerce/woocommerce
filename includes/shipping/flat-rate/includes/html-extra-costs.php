<?php
/**
 * Admin View: Extra costs table
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<tr valign="top">
	<th scope="row" class="titledesc"><?php _e( 'Costs', 'woocommerce' ); ?></th>
	<td class="forminp" id="<?php echo $this->id; ?>_flat_rates">
		<table class="shippingrows widefat" cellspacing="0">
			<thead>
				<tr>
					<th class="check-column"><input type="checkbox"></th>
					<th class="shipping_class"><?php _e( 'Shipping Class', 'woocommerce' ); ?></th>
					<th><?php _e( 'Cost', 'woocommerce' ); ?> <a class="tips" data-tip="<?php _e( 'Cost, excluding tax.', 'woocommerce' ); ?>">[?]</a></th>
					<th><?php _e( 'Handling Fee', 'woocommerce' ); ?> <a class="tips" data-tip="<?php _e( 'Fee excluding tax. Enter an amount, e.g. 2.50, or a percentage, e.g. 5%.', 'woocommerce' ); ?>">[?]</a></th>
				</tr>
			</thead>
			<tbody class="flat_rates">
				<tr>
					<td></td>
					<td class="flat_rate_class"><?php _e( 'Any class', 'woocommerce' ); ?></td>
					<td><input type="text" value="<?php echo esc_attr( wc_format_localized_price( $this->cost ) ); ?>" name="default_cost" placeholder="<?php _e( 'N/A', 'woocommerce' ); ?>" size="4" class="wc_input_price" /></td>
					<td><input type="text" value="<?php echo esc_attr( wc_format_localized_price( $this->fee ) ); ?>" name="default_fee" placeholder="<?php _e( 'N/A', 'woocommerce' ); ?>" size="4" class="wc_input_price" /></td>
				</tr>
				<?php
				$i = -1;
				if ( $this->flat_rates ) {
					foreach ( $this->flat_rates as $class => $rate ) {
						$i++;

						echo '<tr class="flat_rate">
							<th class="check-column"><input type="checkbox" name="select" /></th>
							<td class="flat_rate_class">
									<select name="' . esc_attr( $this->id . '_class[' . $i . ']' ) . '" class="select">';

						if ( WC()->shipping->get_shipping_classes() ) {
							foreach ( WC()->shipping->get_shipping_classes() as $shipping_class ) {
								echo '<option value="' . esc_attr( $shipping_class->slug ) . '" '.selected($shipping_class->slug, $class, false).'>'.$shipping_class->name.'</option>';
							}
						} else {
							echo '<option value="">'.__( 'Select a class&hellip;', 'woocommerce' ).'</option>';
						}

						echo '</select>
					   		</td>
							<td><input type="text" value="' . esc_attr( $rate['cost'] ) . '" name="' . esc_attr( $this->id .'_cost[' . $i . ']' ) . '" placeholder="' . wc_format_localized_price( 0 ) . '" size="4" class="wc_input_price" /></td>
							<td><input type="text" value="' . esc_attr( $rate['fee'] ) . '" name="' . esc_attr( $this->id .'_fee[' . $i . ']' ) . '" placeholder="' . wc_format_localized_price( 0 ) . '" size="4" class="wc_input_price" /></td>
						</tr>';
					}
				}
				?>
			</tbody>
			<tfoot>
				<tr>
					<th colspan="4"><a href="#" class="add button"><?php _e( 'Add Cost', 'woocommerce' ); ?></a> <a href="#" class="remove button"><?php _e( 'Delete selected costs', 'woocommerce' ); ?></a></th>
				</tr>
			</tfoot>
		</table>
	   	<script type="text/javascript">
			jQuery(function() {

				jQuery('#<?php echo $this->id; ?>_flat_rates').on( 'click', 'a.add', function(){

					var size = jQuery('#<?php echo $this->id; ?>_flat_rates tbody .flat_rate').size();

					jQuery('<tr class="flat_rate">\
						<th class="check-column"><input type="checkbox" name="select" /></th>\
						<td class="flat_rate_class">\
							<select name="<?php echo $this->id; ?>_class[' + size + ']" class="select">\
				   				<?php
				   				if (WC()->shipping->get_shipping_classes()) :
									foreach (WC()->shipping->get_shipping_classes() as $class) :
										echo '<option value="' . esc_attr( $class->slug ) . '">' . esc_js( $class->name ) . '</option>';
									endforeach;
								else :
									echo '<option value="">'.__( 'Select a class&hellip;', 'woocommerce' ).'</option>';
								endif;
				   				?>\
				   			</select>\
				   		</td>\
						<td><input type="text" name="<?php echo $this->id; ?>_cost[' + size + ']" placeholder="<?php echo wc_format_localized_price( 0 ); ?>" size="4" class="wc_input_price" /></td>\
						<td><input type="text" name="<?php echo $this->id; ?>_fee[' + size + ']" placeholder="<?php echo wc_format_localized_price( 0 ); ?>" size="4" class="wc_input_price" /></td>\
					</tr>').appendTo('#<?php echo $this->id; ?>_flat_rates table tbody');

					return false;
				});

				// Remove row
				jQuery('#<?php echo $this->id; ?>_flat_rates').on( 'click', 'a.remove', function(){
					var answer = confirm("<?php _e( 'Delete the selected rates?', 'woocommerce' ); ?>");
					if (answer) {
						jQuery('#<?php echo $this->id; ?>_flat_rates table tbody tr th.check-column input:checked').each(function(i, el){
							jQuery(el).closest('tr').remove();
						});
					}
					return false;
				});

			});
		</script>
	</td>
</tr>
