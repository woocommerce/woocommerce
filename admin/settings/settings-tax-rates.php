<?php
/**
 * Additional tax settings
 *
 * @author 		WooThemes
 * @category 	Admin
 * @package 	WooCommerce/Admin/Settings
 * @version     1.6.4
 */

/**
 * Output tax rate settings.
 *
 * @access public
 * @return void
 */
function woocommerce_tax_rates_setting() {
	global $woocommerce;

	$tax_classes 		= array_filter(array_map('trim', explode("\n", get_option('woocommerce_tax_classes'))));
	$tax_rates 			= get_option('woocommerce_tax_rates');
	$local_tax_rates 	= get_option('woocommerce_local_tax_rates');

	?><tr valign="top">
		<th scope="row" class="titledesc"><?php _e('Tax Rates', 'woocommerce') ?></th>
	    <td class="forminp">
	    	<!--<a class="button export_rates"><?php _e('Export rates', 'woocommerce'); ?></a>
	    	<a class="button import_rates"><?php _e('Import rates', 'woocommerce'); ?></a>
	    	<p style="margin-top:0;" class="description"><?php printf(__('Define tax rates for countries and states below, or alternatively upload a CSV file containing your rates to <code>wp-content/woocommerce_tax_rates.csv</code> instead. <a href="%s">Download sample csv.</a>', 'woocommerce'), ''); ?></p>-->
	    	<table class="taxrows widefat" cellspacing="0">
	    		<thead>
	    			<tr>
	    				<th class="check-column"><input type="checkbox"></th>
	    				<th class="country"><?php _e('Countries/states', 'woocommerce'); ?></th>
	    				<th><?php _e('Tax Class', 'woocommerce'); ?></th>
	    				<th><?php _e('Label', 'woocommerce'); ?> <a class="tips" data-tip="<?php _e('Optionally, enter a label for this rate - this will appear in the totals table', 'woocommerce'); ?>">[?]</a></th>
	    				<th><?php _e('Rate', 'woocommerce'); ?> <a class="tips" data-tip="<?php _e('Enter a tax rate (percentage) to 4 decimal places.', 'woocommerce'); ?>">[?]</a></th>
	    				<th><?php _e('Compound', 'woocommerce'); ?>&nbsp;<a class="tips" data-tip="<?php _e('Choose whether or not this is a compound rate. Compound tax rates are applied on top of other tax rates.', 'woocommerce'); ?>">[?]</a></th>
	    				<th><?php _e('Shipping', 'woocommerce'); ?>&nbsp;<a class="tips" data-tip="<?php _e('Choose whether or not this tax rate also gets applied to shipping.', 'woocommerce'); ?>">[?]</a></th>
	    			</tr>
	    		</thead>
	    		<tfoot>
	    			<tr>
	    				<th colspan="2"><a href="#" class="add_tax_rate button"><?php _e('+ Add Tax Rate', 'woocommerce'); ?></a></th>
	    				<th colspan="6">
	    					<small><?php _e('All matching rates will be applied, and non-compound rates will be summed.', 'woocommerce'); ?></small>
	    					<a href="#" class="dupe button"><?php _e('Duplicate selected rows', 'woocommerce'); ?></a> <a href="#" class="remove button"><?php _e('Delete selected rows', 'woocommerce'); ?></a>
	    				</th>
	    			</tr>
	    		</tfoot>
	    		<tbody id="tax_rates">

	    			<?php $i = -1; if ($tax_rates && is_array($tax_rates)) foreach( $tax_rates as $rate ) : $i++; ?>
	    			<tr class="tax_rate">
						<td class="check-column"><input type="checkbox" name="select" /></td>
	        			<td class="country">
	        				<p class="edit"><button class="edit_options button"><?php _e('Edit', 'woocommerce') ?></button> <label><?php echo woocommerce_tax_row_label( $rate['countries'] ); ?></label></p>
	        				<div class="options" style="display:none">
	        					<select name="tax_country[<?php echo $i; ?>][]" data-placeholder="<?php _e('Select countries/states&hellip;', 'woocommerce'); ?>" class="tax_chosen_select select" size="10" multiple="multiple">
			                   		<?php echo $woocommerce->countries->country_multiselect_options( $rate['countries'] ); ?>
			                	</select>
	                			<?php echo '<p><button class="select_all button">'.__('All', 'woocommerce').'</button><button class="select_none button">'.__('None', 'woocommerce').'</button><button class="button select_us_states">'.__('US States', 'woocommerce').'</button><button class="button select_europe">'.__('EU States', 'woocommerce').'</button></p>'; ?>
	                		</div>
	               		</td>
	               		<td class="tax_class">
	               			<select name="tax_class[<?php echo $i; ?>]" title="Tax Class" class="select">
				                <option value=""><?php _e('Standard Rate', 'woocommerce'); ?></option>
				                <?php
		                    		if ($tax_classes) foreach ($tax_classes as $class) :
				                        echo '<option value="'.sanitize_title($class).'"';
				                        selected($rate['class'], sanitize_title($class));
				                        echo '>'.$class.'</option>';
				                    endforeach;
			                    ?>
		                    </select>
	               		</td>
	               		<td class="label">
	               			<input type="text" class="text" value="<?php if (isset($rate['label'])) echo esc_attr( $rate['label'] ); ?>" name="tax_label[<?php echo $i; ?>]" title="<?php _e('Label', 'woocommerce'); ?>" size="16" />
	               		</td>
	    				<td class="rate">
	    					<input type="text" class="text" value="<?php echo esc_attr( $rate['rate'] ); ?>" name="tax_rate[<?php echo $i; ?>]" title="<?php _e('Rate', 'woocommerce'); ?>" placeholder="<?php _e('Rate', 'woocommerce'); ?>" maxlength="8" size="4" />%
	    				</td>
	    				<td class="compound">
	    					<input type="checkbox" class="checkbox" name="tax_compound[<?php echo $i; ?>]" <?php  if (isset($rate['compound'])) checked($rate['compound'], 'yes'); ?> />
	    				</td>
	    				<td class="apply_to_shipping">
	    					<input type="checkbox" class="checkbox" name="tax_shipping[<?php echo $i; ?>]" <?php  if (isset($rate['shipping'])) checked($rate['shipping'], 'yes'); ?> />
	    				</td>
	    			</tr>
	    			<?php endforeach; ?>

	    		</tbody>
	    	</table>
	    </td>
	</tr>
	<tr valign="top">
		<th scope="row" class="titledesc"><?php _e('Local Tax Rates', 'woocommerce') ?></th>
	    <td class="forminp">
	    	<table class="taxrows widefat" cellspacing="0">
	    		<thead>
	    			<tr>
	    				<th class="check-column"><input type="checkbox"></th>
	    				<th class="country"><?php _e('Post/zip codes', 'woocommerce'); ?> <a class="tips" data-tip="<?php _e('List postcodes/zips this rate applies to separated by semi-colons. You may also enter ranges for numeric zip codes. e.g. 12345-12349;23456;', 'woocommerce'); ?>">[?]</a></th>
	    				<th><?php _e('Tax Class', 'woocommerce'); ?></th>
	    				<th><?php _e('Label', 'woocommerce'); ?> <a class="tips" data-tip="<?php _e('Optionally, enter a label for this rate - this will appear in the totals table', 'woocommerce'); ?>">[?]</a></th>
	    				<th><?php _e('Rate', 'woocommerce'); ?> <a class="tips" data-tip="<?php _e('Enter a tax rate (percentage) to 4 decimal places.', 'woocommerce'); ?>">[?]</a></th>
	    				<th><?php _e('Compound', 'woocommerce'); ?>&nbsp;<a class="tips" data-tip="<?php _e('Choose whether or not this is a compound rate. Compound tax rates are applied on top of other tax rates.', 'woocommerce'); ?>">[?]</a></th>
	    				<th><?php _e('Shipping', 'woocommerce'); ?>&nbsp;<a class="tips" data-tip="<?php _e('Choose whether or not this tax rate also gets applied to shipping.', 'woocommerce'); ?>">[?]</a></th>
	    			</tr>
	    		</thead>
	    		<tfoot>
	    			<tr>
	    				<th colspan="2"><a href="#" class="add_local_tax_rate button"><?php _e('+ Add Tax Rate', 'woocommerce'); ?></a></th>
	    				<th colspan="5">
	    					<small><?php _e('All matching rates will be applied, and non-compound rates will be summed.', 'woocommerce'); ?></small>
	    					<a href="#" class="dupe button"><?php _e('Duplicate selected rows', 'woocommerce'); ?></a> <a href="#" class="remove button"><?php _e('Delete selected rows', 'woocommerce'); ?></a>
	    				</th>
	    			</tr>
	    		</tfoot>
	    		<tbody id="local_tax_rates">

	    			<?php $i = -1; if ($local_tax_rates && is_array($local_tax_rates)) foreach( $local_tax_rates as $rate ) : $i++; ?>
	    			<tr class="tax_rate">
						<td class="check-column"><input type="checkbox" name="select" /></td>
	        			<td class="local_country">
	        				<select name="local_tax_country[<?php echo $i; ?>]" class="select">
	        					<option value=""><?php _e('Select a country/state&hellip;', 'woocommerce'); ?></option>
			                   	<?php echo $woocommerce->countries->country_dropdown_options( $rate['country'], $rate['state'] ); ?>
			                </select>
	               			<textarea type="text" placeholder="<?php _e('Post/zip codes', 'woocommerce'); ?>" class="text" name="local_tax_postcode[<?php echo $i; ?>]"><?php if (isset($rate['postcode'])) echo implode(';', $rate['postcode']); ?></textarea>
	               		</td>
	               		<td class="tax_class">
	               			<select name="local_tax_class[<?php echo $i; ?>]" title="Tax Class" class="select">
				                <option value=""><?php _e('Standard Rate', 'woocommerce'); ?></option>
				                <?php
		                    		if ($tax_classes) foreach ($tax_classes as $class) :
				                        echo '<option value="'.sanitize_title($class).'"';
				                        selected($rate['class'], sanitize_title($class));
				                        echo '>'.$class.'</option>';
				                    endforeach;
			                    ?>
		                    </select>
	               		</td>
	               		<td class="label">
	               			<input type="text" class="text" value="<?php if (isset($rate['label'])) echo esc_attr( $rate['label'] ); ?>" name="local_tax_label[<?php echo $i; ?>]" title="<?php _e('Label', 'woocommerce'); ?>" size="16" />
	               		</td>
	    				<td class="rate">
	    					<input type="text" class="text" value="<?php echo esc_attr( $rate['rate'] ); ?>" name="local_tax_rate[<?php echo $i; ?>]" title="<?php _e('Rate', 'woocommerce'); ?>" placeholder="<?php _e('Rate', 'woocommerce'); ?>" maxlength="8" size="4" />%
	    				</td>
	    				<td class="compound">
	    					<input type="checkbox" class="checkbox" name="local_tax_compound[<?php echo $i; ?>]" <?php  if (isset($rate['compound'])) checked($rate['compound'], 'yes'); ?> />
	    				</td>
	    				<td class="apply_to_shipping">
	    					<input type="checkbox" class="checkbox" name="local_tax_shipping[<?php echo $i; ?>]" <?php  if (isset($rate['shipping'])) checked($rate['shipping'], 'yes'); ?> />
	    				</td>
	    			</tr>
	    			<?php endforeach; ?>

	    		</tbody>
	    	</table>
	    </td>
	</tr>

	<script type="text/javascript">
	jQuery(function() {

		jQuery('tr.tax_rate .edit_options').live('click', function(){
			jQuery(this).closest('td').find('.options').slideToggle();
			if (jQuery(this).text()=='<?php _e('Edit', 'woocommerce'); ?>') {

				jQuery(this).closest('tr').find("select.tax_chosen_select").chosen();

				jQuery(this).text('<?php _e('Done', 'woocommerce'); ?>');

			} else {
				jQuery(this).text('<?php _e('Edit', 'woocommerce'); ?>');
			}
			return false;
		});

		jQuery('tr.tax_rate .select_all').live('click', function(){
			jQuery(this).closest('td').find('select option').attr("selected","selected");
			jQuery(this).closest('td').find('select.tax_chosen_select').trigger("change");
			return false;
		});

		jQuery('tr.tax_rate .select_none').live('click', function(){
			jQuery(this).closest('td').find('select option').removeAttr("selected");
			jQuery(this).closest('td').find('select.tax_chosen_select').trigger("change");
			return false;
		});

		jQuery('tr.tax_rate .select_us_states').live('click', function(){
			jQuery(this).closest('td').find('option[value=\"US:AK\"], option[value=\"US:AL\"], option[value=\"US:AZ\"], option[value=\"US:AR\"], option[value=\"US:CA\"], option[value=\"US:CO\"], option[value=\"US:CT\"], option[value=\"US:DE\"], option[value=\"US:DC\"], option[value=\"US:FL\"], option[value=\"US:GA\"], option[value=\"US:HI\"], option[value=\"US:ID\"], option[value=\"US:IL\"], option[value=\"US:IN\"], option[value=\"US:IA\"], option[value=\"US:KS\"], option[value=\"US:KY\"], option[value=\"US:LA\"], option[value=\"US:ME\"], option[value=\"US:MD\"], option[value=\"US:MA\"], option[value=\"US:MI\"], option[value=\"US:MN\"], option[value=\"US:MS\"], option[value=\"US:MO\"], option[value=\"US:MT\"], option[value=\"US:NE\"], option[value=\"US:NV\"], option[value=\"US:NH\"], option[value=\"US:NJ\"], option[value=\"US:NM\"], option[value=\"US:NY\"], option[value=\"US:NC\"], option[value=\"US:ND\"], option[value=\"US:OH\"], option[value=\"US:OK\"], option[value=\"US:OR\"], option[value=\"US:PA\"], option[value=\"US:RI\"], option[value=\"US:SC\"], option[value=\"US:SD\"], option[value=\"US:TN\"], option[value=\"US:TX\"], option[value=\"US:UT\"], option[value=\"US:VT\"], option[value=\"US:VA\"], option[value=\"US:WA\"], option[value=\"US:WV\"], option[value=\"US:WI\"], option[value=\"US:WY\"]').attr( "selected", "selected");
			jQuery(this).closest('td').find('select.tax_chosen_select').trigger('liszt:updated');
			return false;
		});

		jQuery('tr.tax_rate .options select').live('change', function(){
			jQuery(this).trigger("liszt:updated");
			jQuery(this).closest('td').find('label').text( jQuery(":selected", this).length + ' ' + '<?php _e('countries/states selected', 'woocommerce') ?>' );
		});

		jQuery('tr.tax_rate .select_europe').live('click', function(){
			jQuery(this).closest('td').find('option[value="BE"],option[value="FR"],option[value="DE"],option[value="IT"],option[value="LU"],option[value="NL"],option[value="DK"],option[value="IE"],option[value="GR"],option[value="PT"],option[value="ES"],option[value="AT"],option[value="FI"],option[value="SE"],option[value="CY"],option[value="CZ"],option[value="EE"],option[value="HU"],option[value="LV"],option[value="LT"],option[value="MT"],option[value="PL"],option[value="SK"],option[value="SI"],option[value="RO"],option[value="BG"],option[value="IM"],option[value="GB"]').attr("selected","selected");
			jQuery(this).closest('td').find('select.tax_chosen_select').trigger("change");
			return false;
		});

		jQuery('.taxrows a.add_tax_rate').live('click', function(){
			var size = jQuery('#tax_rates tr').size();

			// Add the row
			jQuery('<tr class="tax_rate new_rate">\
				<td class="check-column"><input type="checkbox" name="select" /></td>\
				<td class="country">\
					<p class="edit"><button class="edit_options button"><?php _e('Edit', 'woocommerce') ?></button> <label><?php _e('No countries selected', 'woocommerce'); ?></label></p>\
					<div class="options" style="display:none">\
						<select name="tax_country[' + size + '][]" data-placeholder="<?php _e('Select countries/states&hellip;', 'woocommerce'); ?>" class="tax_chosen_select select" size="10" multiple="multiple">\
	                   		<?php echo $woocommerce->countries->country_multiselect_options(); ?>\
	                	</select>\
	        			<?php echo '<p><button class="select_all button">'.__('All', 'woocommerce').'</button><button class="select_none button">'.__('None', 'woocommerce').'</button><button class="button select_us_states">'.__('US States', 'woocommerce').'</button><button class="button select_europe">'.__('EU States', 'woocommerce').'</button></p>'; ?>\
	        		</div>\
	       		</td>\
	       		<td class="tax_class">\
	       			<select name="tax_class[' + size + ']" title="Tax Class" class="select">\
		                <option value=""><?php _e('Standard Rate', 'woocommerce'); ?></option>\
		                <?php
	                		if ($tax_classes) foreach ($tax_classes as $class) :
		                        echo '<option value="'.sanitize_title($class).'">'.$class.'</option>';
		                    endforeach;
	                    ?>
	                </select>\
	       		</td>\
	       		<td class="label">\
	       			<input type="text" class="text" name="tax_label[' + size + ']" title="<?php _e('Label', 'woocommerce'); ?>" size="16" />\
	       		</td>\
				<td class="rate">\
					<input type="text" class="text" name="tax_rate[' + size + ']" title="<?php _e('Rate', 'woocommerce'); ?>" placeholder="<?php _e('Rate', 'woocommerce'); ?>" maxlength="8" size="4" />%\
				</td>\
				<td class="compound">\
					<input type="checkbox" class="checkbox" name="tax_compound[' + size + ']" />\
				</td>\
				<td class="apply_to_shipping">\
					<input type="checkbox" class="checkbox" name="tax_shipping[' + size + ']" />\
				</td>\
			</tr>').appendTo('#tax_rates');

			jQuery(".new_rate select.tax_chosen_select").chosen();
			jQuery(".new_rate").removeClass('new_rate');

			return false;
		});

		jQuery('.taxrows a.add_local_tax_rate').live('click', function(){
			var size = jQuery('#local_tax_rates tr').size();

			// Add the row
			jQuery('<tr class="tax_rate new_rate">\
				<td class="check-column"><input type="checkbox" name="select" /></td>\
				<td class="local_country">\
					<select name="local_tax_country[' + size + ']" class="select">\
						<option value=""><?php _e('Select a country/state&hellip;', 'woocommerce'); ?></option>\
			       		<?php echo $woocommerce->countries->country_dropdown_options( '', '', true ); ?>\
			    	</select>\
					<textarea type="text" placeholder="<?php _e('Post/zip codes', 'woocommerce'); ?>" class="text" name="local_tax_postcode[' + size + ']"></textarea>\
				</td>\
		   		<td class="tax_class">\
		   			<select name="local_tax_class[' + size + ']" title="Tax Class" class="select">\
		                <option value=""><?php _e('Standard Rate', 'woocommerce'); ?></option>\
		                <?php
		            		if ($tax_classes) foreach ($tax_classes as $class) :
		                        echo '<option value="'.sanitize_title($class).'">'.$class.'</option>';
		                    endforeach;
		                ?>
		            </select>\
		   		</td>\
		   		<td class="label">\
		   			<input type="text" class="text" name="local_tax_label[' + size + ']" title="<?php _e('Label', 'woocommerce'); ?>" size="16" />\
		   		</td>\
				<td class="rate">\
					<input type="text" class="text" name="local_tax_rate[' + size + ']" title="<?php _e('Rate', 'woocommerce'); ?>" placeholder="<?php _e('Rate', 'woocommerce'); ?>" maxlength="8" size="4" />%\
				</td>\
				<td class="compound">\
					<input type="checkbox" class="checkbox" name="local_tax_compound[' + size + ']" />\
				</td>\
				<td class="apply_to_shipping">\
					<input type="checkbox" class="checkbox" name="local_tax_shipping[' + size + ']" />\
				</td>\
			</tr>').appendTo('#local_tax_rates');

			jQuery(".new_rate").removeClass('new_rate');

			return false;
		});

		// Remove row
		jQuery('.taxrows a.remove').live('click', function(){
			var answer = confirm("<?php _e('Delete the selected rates?', 'woocommerce'); ?>")
			if (answer) {
				var $rates = jQuery(this).closest('.taxrows').find('tbody');

				$rates.find('tr td.check-column input:checked').each(function(i, el){
					jQuery(el).closest('tr').find('input.text, input.checkbox, select.select').val('');
					jQuery(el).closest('tr').hide();
				});
			}
			return false;
		});

		// Dupe row
		jQuery('.taxrows a.dupe').live('click', function(){
			var answer = confirm("<?php _e('Duplicate the selected rates?', 'woocommerce'); ?>")
			if (answer) {

				var $rates = jQuery(this).closest('.taxrows').find('tbody');

				$rates.find('tr td.check-column input:checked').each(function(i, el){
					var dupe = jQuery(el).closest('tr').clone()

					// Remove chosen selector
					dupe.find('.chzn-done').removeClass('chzn-done').removeAttr('id').removeAttr('style');
					dupe.find('.chzn-container').remove();

					// Append
					$rates.append( dupe );
				});

				// Re-index keys
				var loop = 0;
				$rates.find('tr.tax_rate').each(function( index, row ){
					jQuery('input.text, input.checkbox, select.select', row).each(function( i, el ){

						var t = jQuery(el);
						t.attr('name', t.attr('name').replace(/\[([^[]*)\]/, "[" + loop + "]"));

					});
					loop++;
				});
			}
			return false;
		});

	});
	</script>
	<?php
}

add_action( 'woocommerce_admin_field_tax_rates', 'woocommerce_tax_rates_setting' );


/**
 * Show a tax label based on user selections.
 *
 * @access public
 * @param mixed $selected
 * @return void
 */
function woocommerce_tax_row_label( $selected ) {
	global $woocommerce;

	$return = '';

	// Get counts/countries
	$counties_array = array();
	$states_count = 0;

	if ($selected) foreach ($selected as $country => $value) :

		$country = woocommerce_clean($country);

		if (sizeof($value)>0 && $value[0]!=='*') :
			$states_count+=sizeof($value);
		endif;

		if (!in_array($country, $counties_array)) $counties_array[] = $woocommerce->countries->countries[$country];

	endforeach;

	$states_text = '';
	$countries_text = implode(', ', $counties_array);

	// Show label
	if (sizeof($counties_array)==0) :

		$return .= __('No countries selected', 'woocommerce');

	elseif ( sizeof($counties_array) < 6 ) :

		if ($states_count>0) $states_text = sprintf(_n('(1 state)', '(%s states)', $states_count, 'woocommerce'), $states_count);

		$return .= $countries_text . ' ' . $states_text;

	else :

		if ($states_count>0) $states_text = sprintf(_n('and 1 state', 'and %s states', $states_count, 'woocommerce'), $states_count);

		$return .= sprintf(_n('1 country', '%1$s countries', sizeof($counties_array), 'woocommerce'), sizeof($counties_array)) . ' ' . $states_text;

	endif;

	return $return;
}