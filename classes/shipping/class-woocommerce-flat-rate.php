<?php
/**
 * Flat Rate Shipping Method
 * 
 * A simple shipping method for a flat fee per item or per order
 *
 * @class 		Flat_Rate
 * @package		WooCommerce
 * @category	Shipping
 * @author		WooThemes
 */  
class Woocommerce_Flat_Rate extends Woocommerce_Shipping_Method {
	
	function __construct() { 
        $this->id 			= 'flat_rate';
        $this->method_title = __('Flat rate', 'woocommerce');

		// Load the form fields.
		$this->init_form_fields();
		
		// Load the settings.
		$this->init_settings();
		
		// Define user set variables
        $this->enabled		= $this->settings['enabled'];
		$this->title 		= $this->settings['title'];
		$this->availability = $this->settings['availability'];
		$this->countries 	= $this->settings['countries'];
		$this->type 		= $this->settings['type'];
		$this->tax_status	= $this->settings['tax_status'];
		$this->cost 		= $this->settings['cost'];
		$this->fee 			= $this->settings['fee']; 
		
		// Flat rates
		$this->get_flat_rates();
		
		// Actions
		add_action('woocommerce_update_options_shipping_methods', array(&$this, 'process_admin_options'));
		add_action('woocommerce_update_options_shipping_methods', array(&$this, 'process_flat_rates'));
    } 

	/**
     * Initialise Gateway Settings Form Fields
     */
    function init_form_fields() {
    	global $woocommerce;
    
    	$this->form_fields = array(
			'enabled' => array(
							'title' 		=> __( 'Enable/Disable', 'woocommerce' ), 
							'type' 			=> 'checkbox', 
							'label' 		=> __( 'Enable Flat Rate shipping', 'woocommerce' ), 
							'default' 		=> 'yes'
						), 
			'title' => array(
							'title' 		=> __( 'Method Title', 'woocommerce' ), 
							'type' 			=> 'text', 
							'description' 	=> __( 'This controls the title which the user sees during checkout.', 'woocommerce' ), 
							'default'		=> __( 'Flat Rate', 'woocommerce' )
						),
			'availability' => array(
							'title' 		=> __( 'Method availability', 'woocommerce' ), 
							'type' 			=> 'select', 
							'default' 		=> 'all',
							'class'			=> 'availability',
							'options'		=> array(
								'all' 		=> __('All allowed countries', 'woocommerce'),
								'specific' 	=> __('Specific Countries', 'woocommerce')
							)
						),
			'countries' => array(
							'title' 		=> __( 'Specific Countries', 'woocommerce' ), 
							'type' 			=> 'multiselect', 
							'class'			=> 'chosen_select',
							'css'			=> 'width: 450px;',
							'default' 		=> '',
							'options'		=> $woocommerce->countries->countries
						),
			'type' => array(
							'title' 		=> __( 'Calculation Type', 'woocommerce' ), 
							'type' 			=> 'select', 
							'description' 	=> '', 
							'default' 		=> 'order',
							'options' 		=> array(
								'order' 	=> __('Per Order - charge shipping for the entire order as a whole', 'woocommerce'),
								'item' 		=> __('Per Item - charge shipping for each item individually', 'woocommerce'),
								'class' 	=> __('Per Class - charge shipping for each shipping class in an order', 'woocommerce')
							)
						),
			'tax_status' => array(
							'title' 		=> __( 'Tax Status', 'woocommerce' ), 
							'type' 			=> 'select', 
							'description' 	=> '', 
							'default' 		=> 'taxable',
							'options'		=> array(
								'taxable' 	=> __('Taxable', 'woocommerce'),
								'none' 		=> __('None', 'woocommerce')
							)
						),
			'cost' => array(
							'title' 		=> __( 'Default Cost', 'woocommerce' ), 
							'type' 			=> 'text', 
							'description'	=> __('Cost excluding tax. Enter an amount, e.g. 2.50.', 'woocommerce'),
							'default' 		=> ''
						), 
			'fee' => array(
							'title' 		=> __( 'Default Handling Fee', 'woocommerce' ), 
							'type' 			=> 'text', 
							'description'	=> __('Fee excluding tax. Enter an amount, e.g. 2.50, or a percentage, e.g. 5%. Leave blank to disable.', 'woocommerce'),
							'default'		=> ''
						),
			);
    
    } // End init_form_fields()
    
    function calculate_shipping() {
    	global $woocommerce;
    	
    	$this->rates 	= array();
    	$shipping_total = 0;
    	
    	if ($this->type=='order') :
    		
    		$cost 	= null;
	    	$fee 	= null;
	    		
    		if (sizeof($this->flat_rates)>0) :
    		
	    		$found_shipping_classes = array();
	    		
	    		// Find shipping classes for products in the cart
	    		if (sizeof($woocommerce->cart->get_cart())>0) : 
	    			foreach ($woocommerce->cart->get_cart() as $item_id => $values) : 
	    				if ( $values['data']->needs_shipping() ) :
	    					$found_shipping_classes[] = $values['data']->get_shipping_class(); 
	    				endif;
	    			endforeach; 
	    		endif;
	
	    		$found_shipping_classes = array_unique($found_shipping_classes);
	    		
	    		// Find most expensive class (if found)
	    		foreach ($found_shipping_classes as $shipping_class) :
	    			if (isset($this->flat_rates[$shipping_class])) :
	    				if ($this->flat_rates[$shipping_class]['cost'] > $cost) :
	    					$cost 	= $this->flat_rates[$shipping_class]['cost'];
	    					$fee	= $this->flat_rates[$shipping_class]['fee'];
	    				endif;
	    			else :
	    				// No matching classes so use defaults
	    				if ($this->cost > $cost) :
	    					$cost 	= $this->cost;
	    					$fee	= $this->fee;
	    				endif;
	    			endif;
	    		endforeach;

    		endif;
    		
    		// Default rates
    		if (is_null($cost)) :
    			$cost = $this->cost;
    			$fee = $this->fee;
    		endif;
    		
			// Shipping for whole order
			$shipping_total = $cost + $this->get_fee( $fee, $woocommerce->cart->cart_contents_total );
			
			$rate = array(
				'id' 	=> $this->id,
				'label' => $this->title,
				'cost' 	=> $shipping_total
			);
			
		elseif ($this->type=='class') :
			// Shipping per class
    		$cost 	= null;
	    	$fee 	= null;
	    		
    		if (sizeof($this->flat_rates)>0) :
    		
	    		$found_shipping_classes = array();
	    		
	    		// Find shipping classes for products in the cart. Store prices too, so we can calc a fee for the class.
	    		if (sizeof($woocommerce->cart->get_cart())>0) : 
	    			foreach ($woocommerce->cart->get_cart() as $item_id => $values) : 
	    				if ( $values['data']->needs_shipping() ) :
	    					if (isset($found_shipping_classes[$values['data']->get_shipping_class()])) :
	    						$found_shipping_classes[$values['data']->get_shipping_class()] = ($values['data']->get_price() * $values['quantity']) + $found_shipping_classes[$values['data']->get_shipping_class()];
	    					else :
	    						$found_shipping_classes[$values['data']->get_shipping_class()] = ($values['data']->get_price() * $values['quantity']);
	    					endif;
	    				endif;
	    			endforeach; 
	    		endif;
	
	    		$found_shipping_classes = array_unique($found_shipping_classes);
	    		
	    		// For each found class, add up the costs and fees
	    		foreach ($found_shipping_classes as $shipping_class => $class_price) :
	    			if (isset($this->flat_rates[$shipping_class])) :
	    				$cost 	+= $this->flat_rates[$shipping_class]['cost'];
	    				$fee	+= $this->get_fee( $this->flat_rates[$shipping_class]['fee'], $class_price );
	    			else :
	    				// Class not set so we use default rate
	    				$cost 	+= $this->cost;
	    				$fee	+= $this->get_fee( $this->fee, $class_price );
	    			endif;
	    		endforeach;

    		endif;
 			
 			// Total
 			$shipping_total = $cost + $fee;
			
			$rate = array(
				'id' 	=> $this->id,
				'label' => $this->title,
				'cost' 	=> $shipping_total
			);

		elseif ($this->type=='item') :
			
			// Per item shipping so we pass an array of costs (per item) instead of a single value
			$costs = array();
			
			// Shipping per item
			foreach ($woocommerce->cart->get_cart() as $item_id => $values) :
				
				$_product = $values['data'];
				
				if ($values['quantity']>0 && $_product->needs_shipping()) :
				
					$shipping_class = $_product->get_shipping_class();
					
					if (isset($this->flat_rates[$shipping_class])) :
						$cost 	= $this->flat_rates[$shipping_class]['cost'];
	    				$fee	= $this->get_fee( $this->flat_rates[$shipping_class]['fee'], $_product->get_price() );
					else :
						$cost 	= $this->cost;
						$fee	= $this->get_fee( $this->fee, $_product->get_price() );
					endif;
					
					$costs[$item_id] = (( $cost + $fee ) * $values['quantity']);
					
				endif;
			endforeach;
			
			$rate = array(
				'id' 		=> $this->id,
				'label' 	=> $this->title,
				'cost' 		=> $costs,
				'calc_tax' 	=> 'per_item'
			);
			
		endif;	
		
		// Register the rate
		$this->add_rate( $rate );  
    } 

	/**
	 * Admin Panel Options 
	 * - Options for bits like 'title' and availability on a country-by-country basis
	 *
	 * @since 1.0.0
	 */
	public function admin_options() {
		global $woocommerce;
    	?>
    	<h3><?php _e('Flat Rates', 'woocommerce'); ?></h3>
    	<p><?php _e('Flat rates let you define a standard rate per item, or per order.', 'woocommerce'); ?></p>
    	<table class="form-table">
    	<?php
    		// Generate the HTML For the settings form.
    		$this->generate_settings_html();
    		?>
	    	<tr valign="top">
	            <th scope="row" class="titledesc"><?php _e('Flat Rates', 'woocommerce'); ?>:</th>
	            <td class="forminp" id="flat_rates">
	            	<table class="shippingrows widefat" cellspacing="0">
	            		<thead>
	            			<tr>
	            				<th class="check-column"><input type="checkbox"></th>
	            				<th class="shipping_class"><?php _e('Shipping Class', 'woocommerce'); ?></th>
	        	            	<th><?php _e('Cost', 'woocommerce'); ?> <a class="tips" tip="<?php _e('Cost, excluding tax.', 'woocommerce'); ?>">[?]</a></th>
	        	            	<th><?php _e('Handling Fee', 'woocommerce'); ?> <a class="tips" tip="<?php _e('Fee excluding tax. Enter an amount, e.g. 2.50, or a percentage, e.g. 5%.', 'woocommerce'); ?>">[?]</a></th>
	            			</tr>
	            		</thead>
	            		<tfoot>
	            			<tr>
	            				<th colspan="2"><a href="#" class="add button"><?php _e('+ Add Flat Rate', 'woocommerce'); ?></a></th>
	            				<th colspan="2"><small><?php _e('Add rates for shipping classes here &mdash; they will override the default costs defined above.', 'woocommerce'); ?></small> <a href="#" class="remove button"><?php _e('Delete selected rates', 'woocommerce'); ?></a></th>
	            			</tr>
	            		</tfoot>
	            		<tbody class="flat_rates">
	                	<?php
	                	$i = -1; if ($this->flat_rates) foreach( $this->flat_rates as $class => $rate ) : $i++;

	                		echo '<tr class="flat_rate">
	                			<td class="check-column"><input type="checkbox" name="select" /></td>
	                			<td class="flat_rate_class">
	                					<select name="flat_rate_class['.$i.']" class="select">';
	                					
	                		if ($woocommerce->shipping->get_shipping_classes()) :
		                		foreach ($woocommerce->shipping->get_shipping_classes() as $shipping_class) :
		                			echo '<option value="'.$shipping_class->slug.'" '.selected($shipping_class->slug, $class, false).'>'.$shipping_class->name.'</option>';
		                		endforeach;
	                		else :
	                			echo '<option value="">'.__('Select a class&hellip;', 'woocommerce').'</option>';
	                		endif;
			               	
			                echo '</select>
			               		</td>
			                    <td><input type="text" value="'.$rate['cost'].'" name="flat_rate_cost['.$i.']" placeholder="'.__('0.00', 'woocommerce').'" size="4" /></td>
			                    <td><input type="text" value="'.$rate['fee'].'" name="flat_rate_fee['.$i.']" placeholder="'.__('0.00', 'woocommerce').'" size="4" /></td>
		                    </tr>';
	                	endforeach;
	                	?>
	                	</tbody>		                    	
	                </table>
	            </td>
	        </tr>
		</table><!--/.form-table-->
       	<script type="text/javascript">
			jQuery(function() {
			
				jQuery('#flat_rates a.add').live('click', function(){
					
					var size = jQuery('tbody.flat_rates .flat_rate').size();
					
					jQuery('<tr class="flat_rate">\
						<td class="check-column"><input type="checkbox" name="select" /></td>\
            			<td class="flat_rate_class">\
            				<select name="flat_rate_class[' + size + ']" class="select">\
	               				<?php 
	               				if ($woocommerce->shipping->get_shipping_classes()) :
			                		foreach ($woocommerce->shipping->get_shipping_classes() as $class) :
			                			echo '<option value="'.$class->slug.'">'.$class->name.'</option>';
			                		endforeach;
		                		else :
		                			echo '<option value="">'.__('Select a class&hellip;', 'woocommerce').'</option>';
		                		endif;
	               				?>\
	               			</select>\
	               		</td>\
	                    <td><input type="text" name="flat_rate_cost[' + size + ']" placeholder="<?php _e('0.00', 'woocommerce'); ?>" size="4" /></td>\
	                    <td><input type="text" name="flat_rate_fee[' + size + ']" placeholder="<?php _e('0.00', 'woocommerce'); ?>" size="4" /></td>\
                    </tr>').appendTo('#flat_rates table tbody');
					
					return false;
				});
				
				// Remove row
				jQuery('#flat_rates a.remove').live('click', function(){
					var answer = confirm("<?php _e('Delete the selected rates?', 'woocommerce'); ?>")
					if (answer) {
						jQuery('#flat_rates table tbody tr td.check-column input:checked').each(function(i, el){
							jQuery(el).closest('tr').remove();
						});
					}
					return false;
				});
				
			});
		</script>
    	<?php
    } // End admin_options()
    
    function process_flat_rates() {
   		
		// Save the rates
		$flat_rate_class = array();
		$flat_rate_cost = array();
		$flat_rate_fee = array();
		$flat_rates = array();
		
		if (isset($_POST['flat_rate_class']))	$flat_rate_class	= array_map('woocommerce_clean', $_POST['flat_rate_class']);
		if (isset($_POST['flat_rate_cost'])) 	$flat_rate_cost 	= array_map('woocommerce_clean', $_POST['flat_rate_cost']);
		if (isset($_POST['flat_rate_fee'])) 	$flat_rate_fee 		= array_map('woocommerce_clean', $_POST['flat_rate_fee']);
		
		// Get max key
		$values = $flat_rate_class;
		ksort($values);   
		$value = end($values);  
		$key = key($values);
		
		for ($i=0; $i<=$key; $i++) :
		
			if (isset($flat_rate_class[$i]) && isset($flat_rate_cost[$i]) && isset($flat_rate_fee[$i])) :
				
				$flat_rate_cost[$i] = number_format($flat_rate_cost[$i], 2,  '.', '');
				
				// Add to flat rates array
				$flat_rates[ sanitize_title($flat_rate_class[$i]) ] = array(
					'cost' 				=> $flat_rate_cost[$i],
					'fee' 				=> $flat_rate_fee[$i]
				);  
				
			endif;

		endfor;
		
		update_option('woocommerce_flat_rates', $flat_rates);
		
		$this->get_flat_rates();
    }
    
    function get_flat_rates() {
    	$this->flat_rates = array_filter((array) get_option('woocommerce_flat_rates'));
    }

}

function add_flat_rate_method( $methods ) {
	$methods[] = 'woocommerce_flat_rate'; return $methods;
}

add_filter('woocommerce_shipping_methods', 'add_flat_rate_method' );
