<?php
class flat_rate extends jigoshop_shipping_method {
	
	public function __construct() { 
        $this->id 			= 'flat_rate';
        $this->enabled		= get_option('jigoshop_flat_rate_enabled');
		$this->title 		= get_option('jigoshop_flat_rate_title');
		$this->availability = get_option('jigoshop_flat_rate_availability');
		$this->countries 	= get_option('jigoshop_flat_rate_countries');
		$this->type 		= get_option('jigoshop_flat_rate_type');
		$this->tax_status	= get_option('jigoshop_flat_rate_tax_status');
		$this->cost 		= get_option('jigoshop_flat_rate_cost');
		$this->fee 			= get_option('jigoshop_flat_rate_handling_fee'); 
		
		add_action('jigoshop_update_options', array(&$this, 'process_admin_options'));
		add_option('jigoshop_flat_rate_availability', 'all');
		add_option('jigoshop_flat_rate_title', 'Flat Rate');
		add_option('jigoshop_flat_rate_tax_status', 'taxable');
    } 
    
    public function calculate_shipping() {
    	
    	$_tax = &new jigoshop_tax();
    	
    	$this->shipping_total 	= 0;
		$this->shipping_tax 	= 0;
    	
    	if ($this->type=='order') :
			// Shipping for whole order
			$this->shipping_total = $this->cost + $this->get_fee( $this->fee, jigoshop_cart::$cart_contents_total );
			
			if ( get_option('jigoshop_calc_taxes')=='yes' && $this->tax_status=='taxable' ) :
				
				$rate = $_tax->get_shipping_tax_rate();
				if ($rate>0) :
					$tax_amount = $_tax->calc_shipping_tax( $this->shipping_total, $rate );

					$this->shipping_tax = $this->shipping_tax + $tax_amount;
				endif;
			endif;
		else :
			// Shipping per item
			if (sizeof(jigoshop_cart::$cart_contents)>0) : foreach (jigoshop_cart::$cart_contents as $item_id => $values) :
				$_product = $values['data'];
				if ($_product->exists() && $values['quantity']>0) :
					
					$item_shipping_price = ($this->cost + $this->get_fee( $this->fee, $_product->get_price() )) * $values['quantity'];
					
					$this->shipping_total = $this->shipping_total + $item_shipping_price;

					if ( $_product->is_shipping_taxable() && $this->tax_status=='taxable' ) :
					
						$rate = $_tax->get_shipping_tax_rate( $_product->data['tax_class'] );
						
						if ($rate>0) :
						
							$tax_amount = $_tax->calc_shipping_tax( $item_shipping_price, $rate );
						
							$this->shipping_tax = $this->shipping_tax + $tax_amount;
						
						endif;
					
					endif;
					
				endif;
			endforeach; endif;
		endif;			
    } 
    
    public function admin_options() {
    	?>
    	<thead><tr><th scope="col" width="200px"><?php _e('Flat Rates', 'jigoshop'); ?></th><th scope="col" class="desc"><?php _e('Flat rates let you define a standard rate per item, or per order.', 'jigoshop'); ?>&nbsp;</th></tr></thead>
    	<tr>
	        <td class="titledesc"><?php _e('Enable Flat Rate', 'jigoshop') ?>:</td>
	        <td class="forminp">
		        <select name="jigoshop_flat_rate_enabled" id="jigoshop_flat_rate_enabled" style="min-width:100px;">
		            <option value="yes" <?php if (get_option('jigoshop_flat_rate_enabled') == 'yes') echo 'selected="selected"'; ?>><?php _e('Yes', 'jigoshop'); ?></option>
		            <option value="no" <?php if (get_option('jigoshop_flat_rate_enabled') == 'no') echo 'selected="selected"'; ?>><?php _e('No', 'jigoshop'); ?></option>
		        </select>
	        </td>
	    </tr>
	    <tr>
	        <td class="titledesc"><a href="#" tip="<?php _e('This controls the title which the user sees during checkout.','jigoshop') ?>" class="tips" tabindex="99"></a><?php _e('Method Title', 'jigoshop') ?>:</td>
	        <td class="forminp">
		        <input type="text" name="jigoshop_flat_rate_title" id="jigoshop_flat_rate_title" style="min-width:50px;" value="<?php if ($value = get_option('jigoshop_flat_rate_title')) echo $value; else echo 'Flat Rate'; ?>" />
	        </td>
	    </tr>
	    <tr>
	        <td class="titledesc"><?php _e('Type', 'jigoshop') ?>:</td>
	        <td class="forminp">
		        <select name="jigoshop_flat_rate_type" id="jigoshop_flat_rate_type" style="min-width:100px;">
		            <option value="order" <?php if (get_option('jigoshop_flat_rate_type') == 'order') echo 'selected="selected"'; ?>><?php _e('Per Order', 'jigoshop'); ?></option>
		            <option value="item" <?php if (get_option('jigoshop_flat_rate_type') == 'item') echo 'selected="selected"'; ?>><?php _e('Per Item', 'jigoshop'); ?></option>
		        </select>
	        </td>
	    </tr>
	    <?php $_tax = new jigoshop_tax(); ?>
	    <tr>
	        <td class="titledesc"><?php _e('Tax Status', 'jigoshop') ?>:</td>
	        <td class="forminp">
	        	<select name="jigoshop_flat_rate_tax_status">
	        		<option value="taxable" <?php if (get_option('jigoshop_flat_rate_tax_status')=='taxable') echo 'selected="selected"'; ?>><?php _e('Taxable', 'jigoshop'); ?></option>
	        		<option value="none" <?php if (get_option('jigoshop_flat_rate_tax_status')=='none') echo 'selected="selected"'; ?>><?php _e('None', 'jigoshop'); ?></option>
	        	</select>
	        </td>
	    </tr>
	    <tr>
	        <td class="titledesc"><a href="#" tip="<?php _e('Cost excluding tax. Enter an amount, e.g. 2.50.', 'jigoshop') ?>" class="tips" tabindex="99"></a><?php _e('Cost', 'jigoshop') ?>:</td>
	        <td class="forminp">
		        <input type="text" name="jigoshop_flat_rate_cost" id="jigoshop_flat_rate_cost" style="min-width:50px;" value="<?php if ($value = get_option('jigoshop_flat_rate_cost')) echo $value; ?>" />
	        </td>
	    </tr>
	    <tr>
	        <td class="titledesc"><a href="#" tip="<?php _e('Fee excluding tax. Enter an amount, e.g. 2.50, or a percentage, e.g. 5%. Leave blank to disable.', 'jigoshop') ?>" class="tips" tabindex="99"></a><?php _e('Handling Fee', 'jigoshop') ?>:</td>
	        <td class="forminp">
		        <input type="text" name="jigoshop_flat_rate_handling_fee" id="jigoshop_flat_rate_handling_fee" style="min-width:50px;" value="<?php if ($value = get_option('jigoshop_flat_rate_handling_fee')) echo $value; ?>" />
	        </td>
	    </tr>
	    <tr>
	        <td class="titledesc"><?php _e('Method available for', 'jigoshop') ?>:</td>
	        <td class="forminp">
		        <select name="jigoshop_flat_rate_availability" id="jigoshop_flat_rate_availability" style="min-width:100px;">
		            <option value="all" <?php if (get_option('jigoshop_flat_rate_availability') == 'all') echo 'selected="selected"'; ?>><?php _e('All allowed countries', 'jigoshop'); ?></option>
		            <option value="specific" <?php if (get_option('jigoshop_flat_rate_availability') == 'specific') echo 'selected="selected"'; ?>><?php _e('Specific Countries', 'jigoshop'); ?></option>
		        </select>
	        </td>
	    </tr>
	    <?php
    	$countries = jigoshop_countries::$countries;
    	asort($countries);
    	$selections = get_option('jigoshop_flat_rate_countries', array());
    	?><tr class="multi_select_countries">
            <td class="titledesc"><?php _e('Specific Countries', 'jigoshop'); ?>:</td>
            <td class="forminp">
            	<div class="multi_select_countries"><ul><?php
        			if ($countries) foreach ($countries as $key=>$val) :
            			                    			
        				echo '<li><label><input type="checkbox" name="jigoshop_flat_rate_countries[]" value="'. $key .'" ';
        				if (in_array($key, $selections)) echo 'checked="checked"';
        				echo ' />'. __($val, 'jigoshop') .'</label></li>';

            		endforeach;
       			?></ul></div>
       		</td>
       	</tr>
       	<script type="text/javascript">
		jQuery(function() {
			jQuery('select#jigoshop_flat_rate_availability').change(function(){
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

   		if(isset($_POST['jigoshop_flat_rate_tax_status'])) update_option('jigoshop_flat_rate_tax_status', jigowatt_clean($_POST['jigoshop_flat_rate_tax_status'])); else @delete_option('jigoshop_flat_rate_tax_status');
   		
   		if(isset($_POST['jigoshop_flat_rate_enabled'])) update_option('jigoshop_flat_rate_enabled', jigowatt_clean($_POST['jigoshop_flat_rate_enabled'])); else @delete_option('jigoshop_flat_rate_enabled');
   		if(isset($_POST['jigoshop_flat_rate_title'])) update_option('jigoshop_flat_rate_title', jigowatt_clean($_POST['jigoshop_flat_rate_title'])); else @delete_option('jigoshop_flat_rate_title');
   		if(isset($_POST['jigoshop_flat_rate_type'])) update_option('jigoshop_flat_rate_type', jigowatt_clean($_POST['jigoshop_flat_rate_type'])); else @delete_option('jigoshop_flat_rate_type');
   		if(isset($_POST['jigoshop_flat_rate_cost'])) update_option('jigoshop_flat_rate_cost', jigowatt_clean($_POST['jigoshop_flat_rate_cost'])); else @delete_option('jigoshop_flat_rate_cost');
   		if(isset($_POST['jigoshop_flat_rate_handling_fee'])) update_option('jigoshop_flat_rate_handling_fee', jigowatt_clean($_POST['jigoshop_flat_rate_handling_fee'])); else @delete_option('jigoshop_flat_rate_handling_fee');
   		
   		if(isset($_POST['jigoshop_flat_rate_availability'])) update_option('jigoshop_flat_rate_availability', jigowatt_clean($_POST['jigoshop_flat_rate_availability'])); else @delete_option('jigoshop_flat_rate_availability');	    
	    if (isset($_POST['jigoshop_flat_rate_countries'])) $selected_countries = $_POST['jigoshop_flat_rate_countries']; else $selected_countries = array();
	    update_option('jigoshop_flat_rate_countries', $selected_countries);
   		
    }
	
}

function add_flat_rate_method( $methods ) {
	$methods[] = 'flat_rate'; return $methods;
}

add_filter('jigoshop_shipping_methods', 'add_flat_rate_method' );
