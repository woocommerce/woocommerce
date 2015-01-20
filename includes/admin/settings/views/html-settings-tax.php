<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<h3><?php printf( __( 'Tax Rates for the "%s" Class', 'woocommerce' ), $current_class ? esc_html( $current_class ) : __( 'Standard', 'woocommerce' ) ); ?></h3>
<p><?php printf( __( 'Define tax rates for countries and states below. <a href="%s">See here</a> for available alpha-2 country codes.', 'woocommerce' ), 'http://en.wikipedia.org/wiki/ISO_3166-1#Current_codes' ); ?></p>
<table class="wc_tax_rates wc_input_table sortable widefat">
	<thead>
		<tr>
			<th class="sort">&nbsp;</th>

			<th width="8%"><?php _e( 'Country&nbsp;Code', 'woocommerce' ); ?>&nbsp;<span class="tips" data-tip="<?php _e('A 2 digit country code, e.g. US. Leave blank to apply to all.', 'woocommerce'); ?>">[?]</span></th>

			<th width="8%"><?php _e( 'State&nbsp;Code', 'woocommerce' ); ?>&nbsp;<span class="tips" data-tip="<?php _e('A 2 digit state code, e.g. AL. Leave blank to apply to all.', 'woocommerce'); ?>">[?]</span></th>

			<th><?php _e( 'ZIP/Postcode', 'woocommerce' ); ?>&nbsp;<span class="tips" data-tip="<?php _e('Postcode for this rule. Semi-colon (;) separate multiple values. Leave blank to apply to all areas. Wildcards (*) can be used. Ranges for numeric postcodes (e.g. 12345-12350) will be expanded into individual postcodes.', 'woocommerce'); ?>">[?]</span></th>

			<th><?php _e( 'City', 'woocommerce' ); ?>&nbsp;<span class="tips" data-tip="<?php _e('Cities for this rule. Semi-colon (;) separate multiple values. Leave blank to apply to all cities.', 'woocommerce'); ?>">[?]</span></th>

			<th width="8%"><?php _e( 'Rate&nbsp;%', 'woocommerce' ); ?>&nbsp;<span class="tips" data-tip="<?php _e( 'Enter a tax rate (percentage) to 4 decimal places.', 'woocommerce' ); ?>">[?]</span></th>

			<th width="8%"><?php _e( 'Tax&nbsp;Name', 'woocommerce' ); ?>&nbsp;<span class="tips" data-tip="<?php _e('Enter a name for this tax rate.', 'woocommerce'); ?>">[?]</span></th>

			<th width="8%"><?php _e( 'Priority', 'woocommerce' ); ?>&nbsp;<span class="tips" data-tip="<?php _e('Choose a priority for this tax rate. Only 1 matching rate per priority will be used. To define multiple tax rates for a single area you need to specify a different priority per rate.', 'woocommerce'); ?>">[?]</span></th>

			<th width="8%"><?php _e( 'Compound', 'woocommerce' ); ?>&nbsp;<span class="tips" data-tip="<?php _e('Choose whether or not this is a compound rate. Compound tax rates are applied on top of other tax rates.', 'woocommerce'); ?>">[?]</span></th>

			<th width="8%"><?php _e( 'Shipping', 'woocommerce' ); ?>&nbsp;<span class="tips" data-tip="<?php _e('Choose whether or not this tax rate also gets applied to shipping.', 'woocommerce'); ?>">[?]</span></th>

		</tr>
	</thead>
	<tbody id="rates">
		<?php
			$rates = $wpdb->get_results( $wpdb->prepare(
				"SELECT * FROM {$wpdb->prefix}woocommerce_tax_rates
				WHERE tax_rate_class = %s
				ORDER BY tax_rate_order
				LIMIT %d, %d
				" ,
				sanitize_title( $current_class ),
				( $page - 1 ) * $limit,
				$limit
			) );

			foreach ( $rates as $rate ) {
				?>
				<tr class="tips" data-tip="<?php echo __( 'Tax rate ID', 'woocommerce' ) . ': ' . $rate->tax_rate_id; ?>">
					<td class="sort"><input type="hidden" class="remove_tax_rate" name="remove_tax_rate[<?php echo $rate->tax_rate_id ?>]" value="0" /></td>

					<td class="country" width="8%">
						<input type="text" value="<?php echo esc_attr( $rate->tax_rate_country ) ?>" placeholder="*" name="tax_rate_country[<?php echo $rate->tax_rate_id ?>]" class="wc_input_country_iso" />
					</td>

					<td class="state" width="8%">
						<input type="text" value="<?php echo esc_attr( $rate->tax_rate_state ) ?>" placeholder="*" name="tax_rate_state[<?php echo $rate->tax_rate_id ?>]" />
					</td>

					<td class="postcode">
						<input type="text" value="<?php
							$locations = $wpdb->get_col( $wpdb->prepare( "SELECT location_code FROM {$wpdb->prefix}woocommerce_tax_rate_locations WHERE location_type='postcode' AND tax_rate_id = %d ORDER BY location_code", $rate->tax_rate_id ) );

							echo esc_attr( implode( '; ', $locations ) );
						?>" placeholder="*" data-name="tax_rate_postcode[<?php echo $rate->tax_rate_id ?>]" />
					</td>

					<td class="city">
						<input type="text" value="<?php
							$locations = $wpdb->get_col( $wpdb->prepare( "SELECT location_code FROM {$wpdb->prefix}woocommerce_tax_rate_locations WHERE location_type='city' AND tax_rate_id = %d ORDER BY location_code", $rate->tax_rate_id ) );
							echo esc_attr( implode( '; ', $locations ) );
						?>" placeholder="*" data-name="tax_rate_city[<?php echo $rate->tax_rate_id ?>]" />
					</td>

					<td class="rate" width="8%">
						<input type="number" step="any" min="0" value="<?php echo esc_attr( $rate->tax_rate ) ?>" placeholder="0" name="tax_rate[<?php echo $rate->tax_rate_id ?>]" />
					</td>

					<td class="name" width="8%">
						<input type="text" value="<?php echo esc_attr( $rate->tax_rate_name ) ?>" name="tax_rate_name[<?php echo $rate->tax_rate_id ?>]" />
					</td>

					<td class="priority" width="8%">
						<input type="number" step="1" min="1" value="<?php echo esc_attr( $rate->tax_rate_priority ) ?>" name="tax_rate_priority[<?php echo $rate->tax_rate_id ?>]" />
					</td>

					<td class="compound" width="8%">
						<input type="checkbox" class="checkbox" name="tax_rate_compound[<?php echo $rate->tax_rate_id ?>]" <?php checked( $rate->tax_rate_compound, '1' ); ?> />
					</td>

					<td class="apply_to_shipping" width="8%">
						<input type="checkbox" class="checkbox" name="tax_rate_shipping[<?php echo $rate->tax_rate_id ?>]" <?php checked($rate->tax_rate_shipping, '1' ); ?> />
					</td>
				</tr>
				<?php
			}
		?>
	</tbody>
	<tfoot>
		<tr>
			<th colspan="10">
				<a href="#" class="button plus insert"><?php _e( 'Insert row', 'woocommerce' ); ?></a>
				<a href="#" class="button minus remove_tax_rates"><?php _e( 'Remove selected row(s)', 'woocommerce' ); ?></a>

				<div class="pagination">
					<?php
						echo str_replace( 'page-numbers', 'page-numbers button', paginate_links( array(
							'base'      => esc_url_raw( add_query_arg( 'p', '%#%', remove_query_arg( 'p' ) ) ),
							'format'    => '',
							'add_args'  => '',
							'type'      => 'plain',
							'prev_text' => '&laquo;',
							'next_text' => '&raquo;',
							'current'   => $page,
							'total'     => ceil( absint( $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(tax_rate_id) FROM {$wpdb->prefix}woocommerce_tax_rates WHERE tax_rate_class = %s;", sanitize_title( $current_class ) ) ) ) / $limit )
						) ) );
					?>
				</div>

				<a href="#" download="tax_rates.csv" class="button export"><?php _e( 'Export CSV', 'woocommerce' ); ?></a>
				<a href="<?php echo admin_url( 'admin.php?import=woocommerce_tax_rate_csv' ); ?>" class="button import"><?php _e( 'Import CSV', 'woocommerce' ); ?></a>
			</th>
		</tr>
	</tfoot>
</table>
<script type="text/javascript">
	jQuery( function() {
		jQuery('.wc_tax_rates .remove_tax_rates').click(function() {
			var $tbody = jQuery('.wc_tax_rates').find('tbody');
			if ( $tbody.find('tr.current').size() > 0 ) {
				$current = $tbody.find('tr.current');
				$current.find('input').val('');
				$current.find('input.remove_tax_rate').val('1');

				$current.each(function(){
					if ( jQuery(this).is('.new') )
						jQuery(this).remove();
					else
						jQuery(this).hide();
				});
			} else {
				alert('<?php echo esc_js( __( 'No row(s) selected', 'woocommerce' ) ); ?>');
			}
			return false;
		});

		jQuery('.wc_tax_rates .export').click(function() {

			var csv_data = "data:application/csv;charset=utf-8,<?php _e( 'Country Code', 'woocommerce' ); ?>,<?php _e( 'State Code', 'woocommerce' ); ?>,<?php _e( 'ZIP/Postcode', 'woocommerce' ); ?>,<?php _e( 'City', 'woocommerce' ); ?>,<?php _e( 'Rate %', 'woocommerce' ); ?>,<?php _e( 'Tax Name', 'woocommerce' ); ?>,<?php _e( 'Priority', 'woocommerce' ); ?>,<?php _e( 'Compound', 'woocommerce' ); ?>,<?php _e( 'Shipping', 'woocommerce' ); ?>,<?php _e( 'Tax Class', 'woocommerce' ); ?>\n";

			jQuery('#rates tr:visible').each(function() {
				var row = '';
				jQuery(this).find('td:not(.sort) input').each(function() {

					if ( jQuery(this).is('.checkbox') ) {

						if ( jQuery(this).is(':checked') ) {
							val = 1;
						} else {
							val = 0;
						}

					} else {

						var val = jQuery(this).val();

						if ( ! val )
							val = jQuery(this).attr('placeholder');
					}

					row = row + val + ',';
				});
				row = row + '<?php echo $current_class; ?>';
				//row.substring( 0, row.length - 1 );
				csv_data = csv_data + row + "\n";
			});

			jQuery(this).attr( 'href', encodeURI( csv_data ) );

			return true;
		});

		jQuery('.wc_tax_rates .insert').click(function() {
			var $tbody = jQuery('.wc_tax_rates').find('tbody');
			var size = $tbody.find('tr').size();
			var code = '<tr class="new">\
					<td class="sort">&nbsp;</td>\
					<td class="country" width="8%">\
						<input type="text" placeholder="*" name="tax_rate_country[new-' + size + ']" class="wc_input_country_iso" />\
					</td>\
					<td class="state" width="8%">\
						<input type="text" placeholder="*" name="tax_rate_state[new-' + size + ']" />\
					</td>\
					<td class="postcode">\
						<input type="text" placeholder="*" name="tax_rate_postcode[new-' + size + ']" />\
					</td>\
					<td class="city">\
						<input type="text" placeholder="*" name="tax_rate_city[new-' + size + ']" />\
					</td>\
					<td class="rate" width="8%">\
						<input type="number" step="any" min="0" placeholder="0" name="tax_rate[new-' + size + ']" />\
					</td>\
					<td class="name" width="8%">\
						<input type="text" name="tax_rate_name[new-' + size + ']" />\
					</td>\
					<td class="priority" width="8%">\
						<input type="number" step="1" min="1" value="1" name="tax_rate_priority[new-' + size + ']" />\
					</td>\
					<td class="compound" width="8%">\
						<input type="checkbox" class="checkbox" name="tax_rate_compound[new-' + size + ']" />\
					</td>\
					<td class="apply_to_shipping" width="8%">\
						<input type="checkbox" class="checkbox" name="tax_rate_shipping[new-' + size + ']" checked="checked" />\
					</td>\
				</tr>';

			if ( $tbody.find('tr.current').size() > 0 ) {
				$tbody.find('tr.current').after( code );
			} else {
				$tbody.append( code );
			}

			jQuery( "td.country input" ).autocomplete({
				source: availableCountries,
				minLength: 3
			});

			jQuery( "td.state input" ).autocomplete({
				source: availableStates,
				minLength: 3
			});

			return false;
		});

		jQuery('.wc_tax_rates td.postcode, .wc_tax_rates td.city').find('input').change(function() {
			jQuery(this).attr( 'name', jQuery(this).attr( 'data-name' ) );
		});

		var availableCountries = [<?php
			$countries = array();
			foreach ( WC()->countries->get_allowed_countries() as $value => $label )
				$countries[] = '{ label: "' . esc_attr( $label ) . '", value: "' . $value . '" }';
			echo implode( ', ', $countries );
		?>];

		var availableStates = [<?php
			$countries = array();
			foreach ( WC()->countries->get_allowed_country_states() as $value => $label )
				foreach ( $label as $code => $state )
					$countries[] = '{ label: "' . esc_attr( $state ) . '", value: "' . $code . '" }';
			echo implode( ', ', $countries );
		?>];

		jQuery( "td.country input" ).autocomplete({
			source: availableCountries,
			minLength: 3
		});

		jQuery( "td.state input" ).autocomplete({
			source: availableStates,
			minLength: 3
		});
	});
</script>
