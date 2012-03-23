<?php
/**
 * Functions for outputting and updating settings pages
 * 
 * @author 		WooThemes
 * @category 	Admin
 * @package 	WooCommerce
 */
 
/**
 * Update options
 * 
 * Updates the options on the woocommerce settings pages. Returns true if saved.
 */
function woocommerce_update_options($options) {
    
    if(!isset($_POST) || !$_POST) return false;
    
    foreach ($options as $value) {
    	if (isset($value['id']) && $value['id']=='woocommerce_tax_rates') :
    		
    		// Tax rates saving
    		$tax_rates 			= array();
    		$tax_classes 		= (isset($_POST['tax_class'])) ? $_POST['tax_class'] : array();
    		$tax_countries 		= (isset($_POST['tax_country'])) ? $_POST['tax_country'] : array();
    		$tax_rate 			= (isset($_POST['tax_rate'])) ? $_POST['tax_rate'] : array();
    		$tax_shipping 		= (isset($_POST['tax_shipping'])) ? $_POST['tax_shipping'] : array();
    		$tax_postcode 		= (isset($_POST['tax_postcode'])) ? $_POST['tax_postcode'] : array();
    		$tax_compound 		= (isset($_POST['tax_compound'])) ? $_POST['tax_compound'] : array();
    		$tax_label 			= (isset($_POST['tax_label'])) ? $_POST['tax_label'] : array();
			
			for ($i=0; $i<sizeof($tax_classes); $i++) :
				
				if (isset($tax_classes[$i]) && isset($tax_countries[$i]) && isset($tax_rate[$i]) && is_numeric($tax_rate[$i])) :
					
					$rate = esc_attr(trim($tax_rate[$i]));
					$rate = number_format($rate, 4, '.', '');
					
					$class = woocommerce_clean($tax_classes[$i]);
					
					if (isset($tax_shipping[$i]) && $tax_shipping[$i]) $shipping = 'yes'; else $shipping = 'no';
					if (isset($tax_compound[$i]) && $tax_compound[$i]) $compound = 'yes'; else $compound = 'no';
					
					// Handle countries
					$counties_array = array();
					$countries = $tax_countries[$i];
					if ($countries) foreach ($countries as $country) :
						
						$country = woocommerce_clean($country);
						$state = '*';
						
						if (strstr($country, ':')) :
							$cr = explode(':', $country);
							$country = current($cr);
							$state = end($cr);
						endif;
					
						$counties_array[trim($country)][] = trim($state);
						
					endforeach;
					
					$tax_rates[] = array(
						'countries' => $counties_array,
						'rate' => $rate,
						'shipping' => $shipping,
						'compound' => $compound,
						'class' => $class,
						'label' => esc_attr($tax_label[$i])
					); 
					
				endif;

			endfor;
			
			update_option( 'woocommerce_tax_rates', $tax_rates );

    		// Local tax rates saving
    		$local_tax_rates 	= array();
    		$tax_classes 		= (isset($_POST['local_tax_class'])) ? $_POST['local_tax_class'] : array();
    		$tax_countries 		= (isset($_POST['local_tax_country'])) ? $_POST['local_tax_country'] : array();
    		$tax_postcode		= (isset($_POST['local_tax_postcode'])) ? $_POST['local_tax_postcode'] : array();
    		$tax_rate 			= (isset($_POST['local_tax_rate'])) ? $_POST['local_tax_rate'] : array();
    		$tax_shipping 		= (isset($_POST['local_tax_shipping'])) ? $_POST['local_tax_shipping'] : array();
    		$tax_postcode 		= (isset($_POST['local_tax_postcode'])) ? $_POST['local_tax_postcode'] : array();
    		$tax_compound 		= (isset($_POST['local_tax_compound'])) ? $_POST['local_tax_compound'] : array();
    		$tax_label 			= (isset($_POST['local_tax_label'])) ? $_POST['local_tax_label'] : array();
			
			for ($i=0; $i<sizeof($tax_classes); $i++) :
			
				if (isset($tax_classes[$i]) && isset($tax_countries[$i]) && isset($tax_rate[$i]) && is_numeric($tax_rate[$i])) :
					
					$rate = esc_attr(trim($tax_rate[$i]));
					$rate = number_format($rate, 4, '.', '');
					
					$class = woocommerce_clean($tax_classes[$i]);
					
					if (isset($tax_shipping[$i]) && $tax_shipping[$i]) $shipping = 'yes'; else $shipping = 'no';
					if (isset($tax_compound[$i]) && $tax_compound[$i]) $compound = 'yes'; else $compound = 'no';
					
					// Handle country
					$country = woocommerce_clean($tax_countries[$i]);
					$state = '*';
					
					if (strstr($country, ':')) :
						$cr = explode(':', $country);
						$country = current($cr);
						$state = end($cr);
					endif;
					
					// Handle postcodes
					$postcodes = explode(';', $tax_postcode[$i]);
					$postcodes = array_filter(array_map('trim', $postcodes));
					
					$local_tax_rates[] = array(
						'country' => $country,
						'state' => $state,
						'postcode' => $postcodes,
						'rate' => $rate,
						'shipping' => $shipping,
						'compound' => $compound,
						'class' => $class,
						'label' => esc_attr($tax_label[$i])
					); 
					
				endif;

			endfor;
			
			update_option( 'woocommerce_local_tax_rates', $local_tax_rates );
		
		elseif (isset($value['type']) && $value['type']=='multi_select_countries') :
		
			// Get countries array
			if (isset($_POST[$value['id']])) $selected_countries = $_POST[$value['id']]; else $selected_countries = array();
			update_option($value['id'], $selected_countries);
		
		elseif ( isset($value['id']) && ( $value['id'] == 'woocommerce_price_thousand_sep' || $value['id'] == 'woocommerce_price_decimal_sep' ) ):
			
			// price separators get a special treatment as they should allow a spaces (don't trim)
			if( isset( $_POST[ $value['id'] ] )  ) {
				update_option($value['id'], $_POST[$value['id']] );
			} else {
                delete_option($value['id']);
            }
            
        elseif (isset($value['type']) && $value['type']=='checkbox') :
            
            if(isset($value['id']) && isset($_POST[$value['id']])) {
            	update_option($value['id'], 'yes');
            } else {
                update_option($value['id'], 'no');
            }
            
        elseif (isset($value['type']) && $value['type']=='image_width') :
            	
            if(isset($value['id']) && isset($_POST[$value['id'].'_width'])) {
              	update_option($value['id'].'_width', woocommerce_clean($_POST[$value['id'].'_width']));
            	update_option($value['id'].'_height', woocommerce_clean($_POST[$value['id'].'_height']));
				if (isset($_POST[$value['id'].'_crop'])) :
					update_option($value['id'].'_crop', 1);
				else :
					update_option($value['id'].'_crop', 0);
				endif;
            } else {
                update_option($value['id'].'_width', $value['std']);
            	update_option($value['id'].'_height', $value['std']);
            	update_option($value['id'].'_crop', 1);
            }	
            	
    	else :
		    
    		if(isset($value['id']) && isset($_POST[$value['id']])) {
            	update_option($value['id'], woocommerce_clean($_POST[$value['id']]));
            } else {
                delete_option($value['id']);
            }
        
        endif;
        
    }
    return true;
}

/**
 * Admin fields
 * 
 * Loops though the woocommerce options array and outputs each field.
 */
function woocommerce_admin_fields($options) {
	global $woocommerce;

    foreach ( $options as $value ) {
    	if ( ! isset( $value['name'] ) ) $value['name'] = '';
    	if ( ! isset( $value['class'] ) ) $value['class'] = '';
    	if ( ! isset( $value['css'] ) ) $value['css'] = '';
    	if ( ! isset( $value['std'] ) ) $value['std'] = '';
    	if ( ! isset( $value['desc'] ) ) $value['desc'] = '';
    	if ( ! isset( $value['desc_tip'] ) ) $value['desc_tip'] = false;
    	
    	if ( $value['desc_tip'] ) {
    		$description = '<img class="help_tip" data-tip="' . esc_attr( $value['desc'] ) . '" src="' . $woocommerce->plugin_url() . '/assets/images/help.png" />';
    	} else {
    		$description = '<span class="description">' . $value['desc'] . '</span>';
    	}
    	
        switch($value['type']) {
            case 'title':
            	if ( isset($value['name'] ) && $value['name'] ) echo '<h3>' . $value['name'] . '</h3>'; 
            	if ( isset($value['desc'] ) && $value['desc'] ) echo wpautop( wptexturize( $value['desc'] ) );
            	echo '<table class="form-table">'. "\n\n";
            	if ( isset($value['id'] ) && $value['id'] ) do_action( 'woocommerce_settings_' . sanitize_title($value['id'] ) );
            break;
            case 'sectionend':
            	if ( isset($value['id'] ) && $value['id'] ) do_action( 'woocommerce_settings_' . sanitize_title( $value['id'] ) . '_end' );
            	echo '</table>';
            	if ( isset($value['id'] ) && $value['id'] ) do_action( 'woocommerce_settings_' . sanitize_title( $value['id'] ) . '_after' );
            break;
            case 'text':
            	?><tr valign="top">
					<th scope="row" class="titledesc"><?php echo $value['name']; ?></th>
                    <td class="forminp"><input name="<?php echo esc_attr( $value['id'] ); ?>" id="<?php echo esc_attr( $value['id'] ); ?>" type="<?php echo esc_attr( $value['type'] ); ?>" style="<?php echo esc_attr( $value['css'] ); ?>" value="<?php if ( get_option( $value['id'] ) !== false && get_option( $value['id'] ) !== null ) { echo esc_attr( stripslashes( get_option($value['id'] ) ) ); } else { echo esc_attr( $value['std'] ); } ?>" /> <?php echo $description; ?></td>
                </tr><?php
            break;
            case 'color' :
            	?><tr valign="top">
					<th scope="row" class="titledesc"><?php echo $value['name']; ?></th>
                    <td class="forminp"><input name="<?php echo esc_attr( $value['id'] ); ?>" id="<?php echo esc_attr( $value['id'] ); ?>" type="text" style="<?php echo esc_attr( $value['css'] ); ?>" value="<?php if ( get_option( $value['id'] ) !== false && get_option( $value['id'] ) !== null ) { echo esc_attr( stripslashes( get_option($value['id'] ) ) ); } else { echo esc_attr( $value['std'] ); } ?>" class="colorpick" /> <?php echo $description; ?> <div id="colorPickerDiv_<?php echo esc_attr( $value['id'] ); ?>" class="colorpickdiv" style="z-index: 100;background:#eee;border:1px solid #ccc;position:absolute;display:none;"></div></td>
                </tr><?php
            break;
            case 'image_width' :
            	?><tr valign="top">
					<th scope="row" class="titledesc"><?php echo $value['name'] ?></th>
                    <td class="forminp">
                    	
                    	<?php _e('Width', 'woocommerce'); ?> <input name="<?php echo esc_attr( $value['id'] ); ?>_width" id="<?php echo esc_attr( $value['id'] ); ?>_width" type="text" size="3" value="<?php if ( $size = get_option( $value['id'].'_width') ) echo stripslashes($size); else echo $value['std']; ?>" /> 
                    	
                    	<?php _e('Height', 'woocommerce'); ?> <input name="<?php echo esc_attr( $value['id'] ); ?>_height" id="<?php echo esc_attr( $value['id'] ); ?>_height" type="text" size="3" value="<?php if ( $size = get_option( $value['id'].'_height') ) echo stripslashes($size); else echo $value['std']; ?>" /> 
                    	
                    	<label><?php _e('Hard Crop', 'woocommerce'); ?> <input name="<?php echo esc_attr( $value['id'] ); ?>_crop" id="<?php echo esc_attr( $value['id'] ); ?>_crop" type="checkbox" <?php if (get_option( $value['id'].'_crop')!='') checked(get_option( $value['id'].'_crop'), 1); else checked(1); ?> /></label> 
                    	
                    	<?php echo $description; ?></td>
                </tr><?php
            break;
            case 'select':
            	?><tr valign="top">
					<th scope="row" class="titledesc"><?php echo $value['name'] ?></th>
                    <td class="forminp"><select name="<?php echo esc_attr( $value['id'] ); ?>" id="<?php echo esc_attr( $value['id'] ); ?>" style="<?php echo esc_attr( $value['css'] ); ?>" class="<?php if (isset($value['class'])) echo $value['class']; ?>">
                        <?php
                        foreach ($value['options'] as $key => $val) {
                        ?>
                            <option value="<?php echo esc_attr( $key ); ?>" <?php if (get_option($value['id']) == $key) { ?> selected="selected" <?php } ?>><?php echo $val ?></option>
                        <?php
                        }
                        ?>
                       </select> <?php echo $description; ?>
                    </td>
                </tr><?php
            break;
            case 'checkbox' :
            	
            	if (!isset($value['hide_if_checked'])) $value['hide_if_checked'] = false;
            	if (!isset($value['show_if_checked'])) $value['show_if_checked'] = false;
            	
            	if (!isset($value['checkboxgroup']) || (isset($value['checkboxgroup']) && $value['checkboxgroup']=='start')) :
            		?>
            		<tr valign="top" class="<?php 
            			if ($value['hide_if_checked']=='yes' || $value['show_if_checked']=='yes') echo 'hidden_option'; 
            			if ($value['hide_if_checked']=='option') echo 'hide_options_if_checked';
            			if ($value['show_if_checked']=='option') echo 'show_options_if_checked';
            		?>">
					<th scope="row" class="titledesc"><?php echo $value['name'] ?></th>
					<td class="forminp">
						<fieldset>
					<?php
            	else :
            		?>
            		<fieldset class="<?php 
            			if ($value['hide_if_checked']=='yes' || $value['show_if_checked']=='yes') echo 'hidden_option'; 
            			if ($value['hide_if_checked']=='option') echo 'hide_options_if_checked';
            			if ($value['show_if_checked']=='option') echo 'show_options_if_checked';
            		?>">
            		<?php
            	endif;
            	
            	?>
	            <legend class="screen-reader-text"><span><?php echo $value['name'] ?></span></legend>
					<label for="<?php echo $value['id'] ?>">
					<input name="<?php echo esc_attr( $value['id'] ); ?>" id="<?php echo esc_attr( $value['id'] ); ?>" type="checkbox" value="1" <?php checked(get_option($value['id']), 'yes'); ?> />
					<?php echo $value['desc'] ?></label><br>
				<?php
				
				if (!isset($value['checkboxgroup']) || (isset($value['checkboxgroup']) && $value['checkboxgroup']=='end')) :
					?>
						</fieldset>
					</td>
					</tr>
					<?php
				else :
					?>
					</fieldset>
					<?php
				endif;
				
            break;
            case 'textarea':
            	?><tr valign="top">
					<th scope="row" class="titledesc"><?php echo $value['name'] ?></th>
                    <td class="forminp">
                        <textarea <?php if ( isset($value['args']) ) echo $value['args'] . ' '; ?>name="<?php echo esc_attr( $value['id'] ); ?>" id="<?php echo esc_attr( $value['id'] ); ?>" style="<?php echo esc_attr( $value['css'] ); ?>"><?php if (false !== get_option($value['id'])) echo esc_textarea(stripslashes(get_option($value['id']))); else echo esc_textarea( $value['std'] ); ?></textarea> <?php echo $description; ?>
                    </td>
                </tr><?php
            break;
            case 'single_select_page' :
            	$page_setting = (int) get_option($value['id']);
            	
            	$args = array( 'name'				=> $value['id'],
            				   'id'					=> $value['id'],
            				   'sort_column' 		=> 'menu_order',
            				   'sort_order'			=> 'ASC',
            				   'show_option_none' 	=> ' ',
            				   'class'				=> $value['class'],
            				   'echo' 				=> false,
            				   'selected'			=> $page_setting);
            	
            	if( isset($value['args']) ) $args = wp_parse_args($value['args'], $args);
            	
            	?><tr valign="top" class="single_select_page">
                    <th scope="row" class="titledesc"><?php echo $value['name'] ?></th>
                    <td class="forminp">
			        	<?php echo str_replace(' id=', " data-placeholder='".__('Select a page...', 'woocommerce')."' style='".$value['css']."' class='".$value['class']."' id=", wp_dropdown_pages($args)); ?> <?php echo $description; ?>      
			        </td>
               	</tr><?php	
            break;
            case 'single_select_country' :
            	$countries = $woocommerce->countries->countries;
            	$country_setting = (string) get_option($value['id']);
            	if (strstr($country_setting, ':')) :
            		$country = current(explode(':', $country_setting));
            		$state = end(explode(':', $country_setting));
            	else :
            		$country = $country_setting;
            		$state = '*';
            	endif;
            	?><tr valign="top">
                    <th scope="rpw" class="titledesc"><?php echo $value['name'] ?></th>
                    <td class="forminp"><select name="<?php echo esc_attr( $value['id'] ); ?>" style="<?php echo esc_attr( $value['css'] ); ?>" data-placeholder="<?php _e('Choose a country&hellip;', 'woocommerce'); ?>" title="Country" class="chosen_select">	
			        	<?php echo $woocommerce->countries->country_dropdown_options($country, $state); ?>          
			        </select> <?php echo $description; ?>
               		</td>
               	</tr><?php	
            break;
            case 'multi_select_countries' :
            	$countries = $woocommerce->countries->countries;
            	asort($countries);
            	$selections = (array) get_option($value['id']);
            	?><tr valign="top">
					<th scope="row" class="titledesc"><?php echo $value['name'] ?></th>
                    <td class="forminp">
	                    <select multiple="multiple" name="<?php echo esc_attr( $value['id'] ); ?>[]" style="width:450px;" data-placeholder="<?php _e('Choose countries&hellip;', 'woocommerce'); ?>" title="Country" class="chosen_select">	
				        	<?php
				        		if ($countries) foreach ($countries as $key=>$val) :
	                    			echo '<option value="'.$key.'" '.selected( in_array($key, $selections), true, false ).'>'.$val.'</option>';   			
	                    		endforeach;   
	                    	?>     
				        </select>
               		</td>
               	</tr><?php		            	
            break;
            case 'tax_rates' :
            	$tax_classes = array_filter(array_map('trim', explode("\n", get_option('woocommerce_tax_classes'))));
            	$tax_rates = get_option('woocommerce_tax_rates');
            	$local_tax_rates = get_option('woocommerce_local_tax_rates');
            	
            	?><tr valign="top">
					<th scope="row" class="titledesc"><?php _e('Tax Rates', 'woocommerce') ?></th>
                    <td class="forminp">
                    	<!--<a class="button export_rates"><?php _e('Export rates', 'woocommerce'); ?></a>
                    	<a class="button import_rates"><?php _e('Import rates', 'woocommerce'); ?></a>
                    	<p style="margin-top:0;" class="description"><?php echo sprintf(__('Define tax rates for countries and states below, or alternatively upload a CSV file containing your rates to <code>wp-content/woocommerce_tax_rates.csv</code> instead. <a href="%s">Download sample csv.</a>', 'woocommerce'), ''); ?></p>-->
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
				    	<!--<a class="button export_rates"><?php _e('Export rates', 'woocommerce'); ?></a>
				    	<a class="button import_rates"><?php _e('Import rates', 'woocommerce'); ?></a>
				    	<p style="margin-top:0;" class="description"><?php echo sprintf(__('Define local tax rates below, or alternatively upload a CSV file containing your rates to <code>wp-content/woocommerce_local_tax_rates.csv</code> instead. <a href="%s">Download sample csv.</a>', 'woocommerce'), ''); ?></p>-->
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
						jQuery(this).closest('td').find('select optgroup[label="United States"] option').attr("selected","selected");
						jQuery(this).closest('td').find('select.tax_chosen_select').trigger("change");
						return false;
					});
					
					jQuery('tr.tax_rate .options select').live('change', function(){
						jQuery(this).trigger("liszt:updated");
						jQuery(this).closest('td').find('label').text( jQuery(":selected", this).length + '<?php _e(' countries/states selected', 'woocommerce') ?>' );
					});
					
					jQuery('tr.tax_rate .select_europe').live('click', function(){
						jQuery(this).closest('td').find('option[value="AL"], option[value="AD"], option[value="AM"], option[value="AT"], option[value="BY"], option[value="BE"], option[value="BA"], option[value="BG"], option[value="CH"], option[value="CY"], option[value="CZ"], option[value="DE"], option[value="DK"], option[value="EE"], option[value="ES"], option[value="FO"], option[value="FI"], option[value="FR"], option[value="GB"], option[value="GE"], option[value="GI"], option[value="GR"], option[value="HU"], option[value="HR"], option[value="IE"], option[value="IS"], option[value="IT"], option[value="LT"], option[value="LU"], option[value="LV"], option[value="MC"], option[value="MK"], option[value="MT"], option[value="NO"], option[value="NL"], option[value="PO"], option[value="PT"], option[value="RO"], option[value="RU"], option[value="SE"], option[value="SI"], option[value="SK"], option[value="SM"], option[value="TR"], option[value="UA"], option[value="VA"]').attr("selected","selected");
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
            break;
            default:
            	do_action( 'woocommerce_admin_field_'.$value['type'], $value );
            break;
    	}
	}
}


/**
 * Tax Row Label
 * 
 * Show a label based on user selections
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