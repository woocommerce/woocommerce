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
    		
    		// Tate rates saving
    		$tax_classes = array();
    		$tax_countries = array();
    		$tax_rate = array();
    		$tax_rates = array();
    		$tax_shipping = array();
    		
			if (isset($_POST['tax_class'])) $tax_classes = $_POST['tax_class'];
			if (isset($_POST['tax_country'])) $tax_countries = $_POST['tax_country'];
			if (isset($_POST['tax_rate'])) $tax_rate = $_POST['tax_rate'];
			if (isset($_POST['tax_shipping'])) $tax_shipping = $_POST['tax_shipping'];
			
			for ($i=0; $i<sizeof($tax_classes); $i++) :
			
				if (isset($tax_classes[$i]) && isset($tax_countries[$i]) && isset($tax_rate[$i]) && is_numeric($tax_rate[$i])) :
					
					$rate = number_format(woocommerce_clean($tax_rate[$i]), 4);
					$class = woocommerce_clean($tax_classes[$i]);
					
					if (isset($tax_shipping[$i]) && $tax_shipping[$i]) $shipping = 'yes'; else $shipping = 'no';
					
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
						'class' => $class
					); 
					
				endif;

			endfor;
			
			update_option($value['id'], $tax_rates);
		
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

    foreach ($options as $value) :
        switch($value['type']) :
            case 'title':
            	if (isset($value['name']) && $value['name']) echo '<h3>'.$value['name'].'</h3>'; 
            	if (isset($value['desc']) && $value['desc']) echo wpautop(wptexturize($value['desc']));
            	echo '<table class="form-table">'. "\n\n";
            	if (isset($value['id']) && $value['id']) do_action('woocommerce_settings_'.sanitize_title($value['id']));
            break;
            case 'sectionend':
            	if (isset($value['id']) && $value['id']) do_action('woocommerce_settings_'.sanitize_title($value['id']).'_end');
            	echo '</table>';
            break;
            case 'text':
            	?><tr valign="top">
					<th scope="row" class="titledesc"><?php echo $value['name']; ?></th>
                    <td class="forminp"><input name="<?php echo esc_attr( $value['id'] ); ?>" id="<?php echo esc_attr( $value['id'] ); ?>" type="<?php echo esc_attr( $value['type'] ); ?>" style="<?php echo esc_attr( $value['css'] ); ?>" value="<?php if ( get_option( $value['id'] ) !== false && get_option( $value['id'] ) !== null ) { echo esc_attr( stripslashes( get_option($value['id'] ) ) ); } else { echo esc_attr( $value['std'] ); } ?>" /> <span class="description"><?php echo $value['desc']; ?></span></td>
                </tr><?php
            break;
            case 'image_width' :
            	?><tr valign="top">
					<th scope="row" class="titledesc"><?php echo $value['name'] ?></th>
                    <td class="forminp">
                    	
                    	<?php _e('Width'); ?> <input name="<?php echo esc_attr( $value['id'] ); ?>_width" id="<?php echo esc_attr( $value['id'] ); ?>_width" type="text" size="3" value="<?php if ( $size = get_option( $value['id'].'_width') ) echo stripslashes($size); else echo $value['std']; ?>" /> 
                    	
                    	<?php _e('Height'); ?> <input name="<?php echo esc_attr( $value['id'] ); ?>_height" id="<?php echo esc_attr( $value['id'] ); ?>_height" type="text" size="3" value="<?php if ( $size = get_option( $value['id'].'_height') ) echo stripslashes($size); else echo $value['std']; ?>" /> 
                    	
                    	<label><?php _e('Hard Crop', 'woothemes'); ?> <input name="<?php echo esc_attr( $value['id'] ); ?>_crop" id="<?php echo esc_attr( $value['id'] ); ?>_crop" type="checkbox" <?php if (get_option( $value['id'].'_crop')!='') checked(get_option( $value['id'].'_crop'), 1); else checked(1); ?> /></label> 
                    	
                    	<span class="description"><?php echo $value['desc'] ?></span></td>
                </tr><?php
            break;
            case 'select':
            	?><tr valign="top">
					<th scope="row" class="titledesc"><?php echo $value['name'] ?></th>
                    <td class="forminp"><select name="<?php echo esc_attr( $value['id'] ); ?>" id="<?php echo esc_attr( $value['id'] ); ?>" style="<?php echo esc_attr( $value['css'] ); ?>">
                        <?php
                        foreach ($value['options'] as $key => $val) {
                        ?>
                            <option value="<?php echo esc_attr( $key ); ?>" <?php if (get_option($value['id']) == $key) { ?> selected="selected" <?php } ?>><?php echo ucfirst($val) ?></option>
                        <?php
                        }
                        ?>
                       </select> <span class="description"><?php echo $value['desc'] ?></span>
                    </td>
                </tr><?php
            break;
            case 'checkbox' :
            
            	if (!isset($value['checkboxgroup']) || (isset($value['checkboxgroup']) && $value['checkboxgroup']=='start')) :
            		?>
            		<tr valign="top">
					<th scope="row" class="titledesc"><?php echo $value['name'] ?></th>
					<td class="forminp">
					<?php
            	endif;
            	
            	?>
	            <fieldset><legend class="screen-reader-text"><span><?php echo $value['name'] ?></span></legend>
					<label for="<?php echo $value['id'] ?>">
					<input name="<?php echo esc_attr( $value['id'] ); ?>" id="<?php echo esc_attr( $value['id'] ); ?>" type="checkbox" value="1" <?php checked(get_option($value['id']), 'yes'); ?> />
					<?php echo $value['desc'] ?></label><br>
				</fieldset>
				<?php
				
				if (!isset($value['checkboxgroup']) || (isset($value['checkboxgroup']) && $value['checkboxgroup']=='end')) :
					?>
						</td>
					</tr>
					<?php
				endif;
				
            break;
            case 'textarea':
            	?><tr valign="top">
					<th scope="row" class="titledesc"><?php echo $value['name'] ?></th>
                    <td class="forminp">
                        <textarea <?php if ( isset($value['args']) ) echo $value['args'] . ' '; ?>name="<?php echo esc_attr( $value['id'] ); ?>" id="<?php echo esc_attr( $value['id'] ); ?>" style="<?php echo esc_attr( $value['css'] ); ?>"><?php if (get_option($value['id'])) echo esc_textarea(stripslashes(get_option($value['id']))); else echo esc_textarea( $value['std'] ); ?></textarea> <span class="description"><?php echo $value['desc'] ?></span>
                    </td>
                </tr><?php
            break;
            case 'single_select_page' :
            	$page_setting = (int) get_option($value['id']);
            	
            	$args = array( 'name'	=> $value['id'],
            				   'id'		=> $value['id']. '" style="width: 200px;',
            				   'sort_column' 	=> 'menu_order',
            				   'sort_order'		=> 'ASC',
            				   'selected'		=> $page_setting);
            	
            	if( isset($value['args']) ) $args = wp_parse_args($value['args'], $args);
            	
            	?><tr valign="top" class="single_select_page">
                    <th scope="row" class="titledesc"><?php echo $value['name'] ?></th>
                    <td class="forminp">
			        	<?php wp_dropdown_pages($args); ?> <span class="description"><?php echo $value['desc'] ?></span>        
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
            	?><tr valign="top" class="multi_select_countries">
                    <th scope="rpw" class="titledesc"><?php echo $value['name'] ?></th>
                    <td class="forminp"><select name="<?php echo esc_attr( $value['id'] ); ?>" title="Country" style="width: 175px;">	
			        	<?php echo $woocommerce->countries->country_dropdown_options($country, $state); ?>          
			        </select> <span class="description"><?php echo $value['desc'] ?></span>
               		</td>
               	</tr><?php	
            break;
            case 'multi_select_countries' :
            	$countries = $woocommerce->countries->countries;
            	asort($countries);
            	$selections = (array) get_option($value['id']);
            	?><tr valign="top" class="multi_select_countries">
					<th scope="row" class="titledesc"><?php echo $value['name'] ?></th>
                    <td class="forminp">
                    	<div class="multi_select_countries"><ul><?php
	            			if ($countries) foreach ($countries as $key=>$val) :
                    			                    			
	            				echo '<li><label><input type="checkbox" name="'. $value['id'] .'[]" value="'. $key .'" ';
	            				if (in_array($key, $selections)) echo 'checked="checked"';
	            				echo ' />'. $val .'</label></li>';
 
                    		endforeach;
               			?></ul></div>
               		</td>
               	</tr><?php		            	
            break;
            case 'tax_rates' :
            	$_tax = new woocommerce_tax();
            	$tax_classes = $_tax->get_tax_classes();
            	$tax_rates = get_option('woocommerce_tax_rates');
            	
            	?><tr valign="top">
					<th scope="row" class="titledesc"><?php echo $value['name'] ?></th>
                    <td class="forminp" id="tax_rates">
                    	<div class="taxrows">
							
							<?php $i = -1; if ($tax_rates && is_array($tax_rates) && sizeof($tax_rates)>0) foreach( $tax_rates as $rate ) : $i++; ?>
							<div class="taxrow">
	               				<select name="tax_country[<?php echo $i; ?>][]" title="Country" class="country_multiselect" size="10" multiple="multiple">
				                   <?php echo $woocommerce->countries->country_multiselect_options( $rate['countries'] ); ?>
				                </select>
				                <select name="tax_class[<?php echo $i; ?>]" title="Tax Class">
					                <option value=""><?php _e('Standard Rate', 'woothemes'); ?></option>
					                <?php
			                    		if ($tax_classes) foreach ($tax_classes as $class) :
					                        echo '<option value="'.sanitize_title($class).'"';
					                        selected($rate['class'], sanitize_title($class));
					                        echo '>'.$class.'</option>';
					                    endforeach;
				                    ?>
			                    </select>
			                    <input type="text" class="text" value="<?php echo esc_attr( $rate['rate'] ); ?>" name="tax_rate[<?php echo $i; ?>]" title="<?php _e('Rate', 'woothemes'); ?>" placeholder="<?php _e('Rate', 'woothemes'); ?>" maxlength="8" />% 
			                    <label class="checkbox"><input type="checkbox" name="tax_shipping[<?php echo $i; ?>]" <?php  if (isset($rate['shipping'])) checked($rate['shipping'], 'yes'); ?> /> <?php _e('Apply to shipping', 'woothemes'); ?></label><a href="#" class="remove button">&times;</a>
               				</div>
               				<?php endforeach; ?>
               				
                        </div>
                        <p><a href="#" class="add button"><?php _e('+ Add Tax Rule', 'woothemes'); ?></a></p>
                        
                        <script type="text/javascript">
                        	jQuery(function() {
                        		// Tax
								jQuery('#tax_rates a.add').live('click', function(){
									var size = jQuery('.taxrows .taxrow').size();
									
									// Add the row
									jQuery('<div class="taxrow">\
						   				<select name="tax_country[' + size + '][]" title="Country" class="country_multiselect" size="10" multiple="multiple"><?php echo $woocommerce->countries->country_multiselect_options('',true); ?></select>\
						                <select name="tax_class[' + size + ']" title="Tax Class"><option value=""><?php _e('Standard Rate', 'woothemes'); ?></option><?php
						                		if ($tax_classes) foreach ($tax_classes as $class) :
							                        echo '<option value="'.esc_attr( sanitize_title($class) ).'">'.$class.'</option>';
							                    endforeach;
						                ?></select>\
						                <input type="text" class="text" name="tax_rate[' + size + ']" title="<?php _e('Rate', 'woothemes'); ?>" placeholder="<?php _e('Rate', 'woothemes'); ?>" maxlength="8" />% \
						                <label class="checkbox"><input type="checkbox" name="tax_shipping[' + size + ']" checked="checked" /> <?php _e('Apply to shipping', 'woothemes'); ?></label><a href="#" class="remove button">&times;</a>\
										</div>').appendTo('#tax_rates div.taxrows');
										
										// Multiselect
										jQuery(".country_multiselect").multiselect({
										noneSelectedText: '<?php _e('Select countries/states', 'woothemes'); ?>',
										selectedList: 4
									});
										
									return false;
								});
								
								jQuery('#tax_rates a.remove').live('click', function(){
									var answer = confirm("<?php _e('Delete this rule?', 'woothemes'); ?>");
									if (answer) {
										jQuery('input', jQuery(this).parent()).val('');
										jQuery(this).parent().hide();
									}
									return false;
								});
                        	});
                        </script>
                    </td>
                </tr>
                <?php
            break;
        endswitch;
    endforeach;
}