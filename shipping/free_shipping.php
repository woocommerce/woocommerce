<?php
class free_shipping extends jigoshop_shipping_method {
	
	public function __construct() { 
        $this->id 			= 'free_shipping';
        $this->enabled		= get_option('jigoshop_free_shipping_enabled');
		$this->title 		= get_option('jigoshop_free_shipping_title');
		$this->min_amount 	= get_option('jigoshop_free_shipping_minimum_amount');
		$this->availability = get_option('jigoshop_free_shipping_availability');
		$this->countries 	= get_option('jigoshop_free_shipping_countries');
		if (isset($_SESSION['_chosen_method_id']) && $_SESSION['_chosen_method_id']==$this->id) $this->chosen = true;
		
		add_action('jigoshop_update_options', array(&$this, 'process_admin_options'));
		
		add_option('jigoshop_free_shipping_availability', 'all');
		add_option('jigoshop_free_shipping_title', 'Free Shipping');
    } 
    
    public function calculate_shipping() {
		$this->shipping_total 	= 0;
		$this->shipping_tax 	= 0;
		$this->shipping_label 	= $this->title;	    	
    }
    
    public function admin_options() {
    	?>
    	<thead><tr><th scope="col" width="200px"><?php _e('Free Shipping', 'jigoshop'); ?></th><th scope="col" class="desc">&nbsp;</th></tr></thead>
    	<tr>
	        <td class="titledesc"><?php _e('Enable Free Shipping', 'jigoshop') ?>:</td>
	        <td class="forminp">
		        <select name="jigoshop_free_shipping_enabled" id="jigoshop_free_shipping_enabled" style="min-width:100px;">
		            <option value="yes" <?php if (get_option('jigoshop_free_shipping_enabled') == 'yes') echo 'selected="selected"'; ?>><?php _e('Yes', 'jigoshop'); ?></option>
		            <option value="no" <?php if (get_option('jigoshop_free_shipping_enabled') == 'no') echo 'selected="selected"'; ?>><?php _e('No', 'jigoshop'); ?></option>
		        </select>
	        </td>
	    </tr>
	    <tr>
	        <td class="titledesc"><a href="#" tip="<?php _e('This controls the title which the user sees during checkout.','jigoshop') ?>" class="tips" tabindex="99"></a><?php _e('Method Title', 'jigoshop') ?>:</td>
	        <td class="forminp">
		        <input type="text" name="jigoshop_free_shipping_title" id="jigoshop_free_shipping_title" style="min-width:50px;" value="<?php if ($value = get_option('jigoshop_free_shipping_title')) echo $value; else echo 'Free Shipping'; ?>" />
	        </td>
	    </tr>
	    <tr>
	        <td class="titledesc"><a href="#" tip="<?php _e('Users will need to spend this amount to get free shipping. Leave blank to disable.','jigoshop') ?>" class="tips" tabindex="99"></a><?php _e('Minimum Order Amount', 'jigoshop') ?>:</td>
	        <td class="forminp">
		        <input type="text" name="jigoshop_free_shipping_minimum_amount" id="jigoshop_free_shipping_minimum_amount" style="min-width:50px;" value="<?php if ($value = get_option('jigoshop_free_shipping_minimum_amount')) echo $value; else echo 'Free Shipping'; ?>" />
	        </td>
	    </tr>
	    <tr>
	        <td class="titledesc"><?php _e('Method available for', 'jigoshop') ?>:</td>
	        <td class="forminp">
		        <select name="jigoshop_free_shipping_availability" id="jigoshop_free_shipping_availability" style="min-width:100px;">
		            <option value="all" <?php if (get_option('jigoshop_free_shipping_availability') == 'all') echo 'selected="selected"'; ?>><?php _e('All allowed countries', 'jigoshop'); ?></option>
		            <option value="specific" <?php if (get_option('jigoshop_free_shipping_availability') == 'specific') echo 'selected="selected"'; ?>><?php _e('Specific Countries', 'jigoshop'); ?></option>
		        </select>
	        </td>
	    </tr>
	    <?php
    	$countries = jigoshop_countries::$countries;
    	$selections = get_option('jigoshop_free_shipping_countries', array());
    	?><tr class="multi_select_countries">
            <td class="titledesc"><?php _e('Specific Countries', 'jigoshop'); ?>:</td>
            <td class="forminp">
            	<div class="multi_select_countries"><ul><?php
        			if ($countries) foreach ($countries as $key=>$val) :
            			                    			
        				echo '<li><label><input type="checkbox" name="jigoshop_free_shipping_countries[]" value="'. $key .'" ';
        				if (in_array($key, $selections)) echo 'checked="checked"';
        				echo ' />'. __($val, 'jigoshop') .'</label></li>';

            		endforeach;
       			?></ul></div>
       		</td>
       	</tr>
       	<script type="text/javascript">
		jQuery(function() {
			jQuery('select#jigoshop_free_shipping_availability').change(function(){
				if (jQuery(this).val()=="specific") {
					jQuery(this).parent().parent().next('tr.multi_select_countries').show();
				} else {
					jQuery(this).parent().parent().next('tr.multi_select_countries').hide();
				}
			}).change();
		});
		</script>
    	<?php
    }
    
    public function process_admin_options() {
   		
   		if(isset($_POST['jigoshop_free_shipping_enabled'])) update_option('jigoshop_free_shipping_enabled', jigowatt_clean($_POST['jigoshop_free_shipping_enabled'])); else @delete_option('jigoshop_free_shipping_enabled');
   		if(isset($_POST['jigoshop_free_shipping_title'])) update_option('jigoshop_free_shipping_title', jigowatt_clean($_POST['jigoshop_free_shipping_title'])); else @delete_option('jigoshop_free_shipping_title');
   		if(isset($_POST['jigoshop_free_shipping_minimum_amount'])) update_option('jigoshop_free_shipping_minimum_amount', jigowatt_clean($_POST['jigoshop_free_shipping_minimum_amount'])); else @delete_option('jigoshop_free_shipping_minimum_amount');
   		if(isset($_POST['jigoshop_free_shipping_availability'])) update_option('jigoshop_free_shipping_availability', jigowatt_clean($_POST['jigoshop_free_shipping_availability'])); else @delete_option('jigoshop_free_shipping_availability');
	    
	    if (isset($_POST['jigoshop_free_shipping_countries'])) $selected_countries = $_POST['jigoshop_free_shipping_countries']; else $selected_countries = array();
	    update_option('jigoshop_free_shipping_countries', $selected_countries);
   		
    }
    	
}

function add_free_shipping_method( $methods ) {
	$methods[] = 'free_shipping'; return $methods;
}

add_filter('jigoshop_shipping_methods', 'add_free_shipping_method' );