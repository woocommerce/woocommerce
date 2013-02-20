<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Flat Rate Shipping Method
 *
 * A simple shipping method for a flat fee per item or per order
 *
 * @class 		WC_Shipping_Flat_Rate
 * @version		2.0.0
 * @package		WooCommerce/Classes/Shipping
 * @author 		WooThemes
 */
class WC_Shipping_Flat_Rate extends WC_Shipping_Method {

	/**
	 * __construct function.
	 *
	 * @access public
	 * @return void
	 */
	function __construct() {
        $this->id 						= 'flat_rate';
        $this->method_title 			= __( 'Flat rate', 'woocommerce' );

		$this->flat_rate_option 		= 'woocommerce_flat_rates';
		$this->admin_page_heading 		= __( 'Flat Rates', 'woocommerce' );
		$this->admin_page_description 	= __( 'Flat rates let you define a standard rate per item, or per order.', 'woocommerce' );

		add_action( 'woocommerce_update_options_shipping_' . $this->id, array( $this, 'process_admin_options' ) );
		add_action( 'woocommerce_update_options_shipping_' . $this->id, array( $this, 'process_flat_rates' ) );

    	$this->init();
    }

    /**
     * init function.
     *
     * @access public
     * @return void
     */
    function init() {

		// Load the settings.
		$this->init_form_fields();
		$this->init_settings();

		// Define user set variables
		$this->title 		  = $this->get_option( 'title' );
		$this->availability   = $this->get_option( 'availability' );
		$this->countries 	  = $this->get_option( 'countries' );
		$this->type 		  = $this->get_option( 'type' );
		$this->tax_status	  = $this->get_option( 'tax_status' );
		$this->cost 		  = $this->get_option( 'cost' );
		$this->cost_per_order = $this->get_option( 'cost_per_order' );
		$this->fee 			  = $this->get_option( 'fee' );
		$this->minimum_fee 	  = $this->get_option( 'minimum_fee' );
		$this->options 		  = (array) explode( "\n", $this->get_option( 'options' ) );

		// Load Flat rates
		$this->get_flat_rates();
    }


    /**
     * Initialise Gateway Settings Form Fields
     *
     * @access public
     * @return void
     */
    function init_form_fields() {
    	global $woocommerce;

    	$this->form_fields = array(
			'enabled' => array(
							'title' 		=> __( 'Enable/Disable', 'woocommerce' ),
							'type' 			=> 'checkbox',
							'label' 		=> __( 'Enable this shipping method', 'woocommerce' ),
							'default' 		=> 'no',
						),
			'title' => array(
							'title' 		=> __( 'Method Title', 'woocommerce' ),
							'type' 			=> 'text',
							'description' 	=> __( 'This controls the title which the user sees during checkout.', 'woocommerce' ),
							'default'		=> __( 'Flat Rate', 'woocommerce' ),
							'desc_tip'      => true
						),
			'cost_per_order' => array(
							'title' 		=> __( 'Cost per order', 'woocommerce' ),
							'type' 			=> 'number',
							'custom_attributes' => array(
								'step'	=> 'any',
								'min'	=> '0'
							),
							'description'	=> __( 'Enter a cost (excluding tax) per order, e.g. 5.00. Leave blank to disable.', 'woocommerce' ),
							'default'		=> '',
							'desc_tip'      => true,
							'placeholder'	=> '0.00'
						),
			'availability' => array(
							'title' 		=> __( 'Method availability', 'woocommerce' ),
							'type' 			=> 'select',
							'default' 		=> 'all',
							'class'			=> 'availability',
							'options'		=> array(
								'all' 		=> __( 'All allowed countries', 'woocommerce' ),
								'specific' 	=> __( 'Specific Countries', 'woocommerce' ),
							),
						),
			'countries' => array(
							'title' 		=> __( 'Specific Countries', 'woocommerce' ),
							'type' 			=> 'multiselect',
							'class'			=> 'chosen_select',
							'css'			=> 'width: 450px;',
							'default' 		=> '',
							'options'		=> $woocommerce->countries->countries,
						),
			'type' => array(
							'title' 		=> __( 'Calculation Type', 'woocommerce' ),
							'type' 			=> 'select',
							'default' 		=> 'order',
							'options' 		=> array(
								'order' 	=> __( 'Per Order - charge shipping for the entire order as a whole', 'woocommerce' ),
								'item' 		=> __( 'Per Item - charge shipping for each item individually', 'woocommerce' ),
								'class' 	=> __( 'Per Class - charge shipping for each shipping class in an order', 'woocommerce' ),
							),
						),
			'tax_status' => array(
							'title' 		=> __( 'Tax Status', 'woocommerce' ),
							'type' 			=> 'select',
							'default' 		=> 'taxable',
							'options'		=> array(
								'taxable' 	=> __( 'Taxable', 'woocommerce' ),
								'none' 		=> __( 'None', 'woocommerce' ),
							),
						),
			'cost' => array(
							'title' 		=> __( 'Default Cost', 'woocommerce' ),
							'type' 			=> 'number',
							'custom_attributes' => array(
								'step'	=> 'any',
								'min'	=> '0'
							),
							'description'	=> __( 'Cost excluding tax. Enter an amount, e.g. 2.50.', 'woocommerce' ),
							'default' 		=> '',
							'desc_tip'      => true,
							'placeholder'	=> '0.00'
						),
			'fee' => array(
							'title' 		=> __( 'Default Handling Fee', 'woocommerce' ),
							'type' 			=> 'text',
							'description'	=> __( 'Fee excluding tax. Enter an amount, e.g. 2.50, or a percentage, e.g. 5%. Leave blank to disable.', 'woocommerce' ),
							'default'		=> '',
							'desc_tip'      => true,
							'placeholder'	=> '0.00'
						),
			'minimum_fee' => array(
							'title' 		=> __( 'Minimum Fee', 'woocommerce' ),
							'type' 			=> 'number',
							'custom_attributes' => array(
								'step'	=> 'any',
								'min'	=> '0'
							),
							'description'	=> __( 'Enter a minimum fee amount. Fee\'s less than this will be increased. Leave blank to disable.', 'woocommerce' ),
							'default'		=> '',
							'desc_tip'      => true,
							'placeholder'	=> '0.00'
						),
			'options' => array(
							'title' 		=> __( 'Shipping Options', 'woocommerce' ),
							'type' 			=> 'textarea',
							'description'	=> __( 'Optional extra shipping options with additional costs (one per line). Example: <code>Option Name|Cost|Per-order (yes or no)</code>. Example: <code>Priority Mail|6.95|yes</code>. If per-order is set to no, it will use the "Calculation Type" setting.', 'woocommerce' ),
							'default'		=> '',
							'desc_tip'      => true,
							'placeholder'	=> __( 'Option Name|Cost|Per-order (yes or no)', 'woocommerce' )
						),
			);

    }


    /**
     * calculate_shipping function.
     *
     * @access public
     * @param array $package (default: array())
     * @return void
     */
    function calculate_shipping( $package = array() ) {
    	global $woocommerce;

    	$this->rates 		= array();
    	$cost_per_order 	= ( isset( $this->cost_per_order ) && ! empty( $this->cost_per_order ) ) ? $this->cost_per_order : 0;

    	if ( $this->type == 'order' ) {

    		$shipping_total = $this->order_shipping( $package );

    		if ( ! is_null( $shipping_total ) || $cost_per_order > 0 )
	    		$rate = array(
					'id' 	=> $this->id,
					'label' => $this->title,
					'cost' 	=> $shipping_total + $cost_per_order,
				);

		} elseif ( $this->type == 'class' ) {

			$shipping_total = $this->class_shipping( $package );

			if ( ! is_null( $shipping_total ) || $cost_per_order > 0 )
	    		$rate = array(
					'id' 	=> $this->id,
					'label' => $this->title,
					'cost' 	=> $shipping_total + $cost_per_order,
				);

		} elseif ( $this->type == 'item' ) {

			$costs = $this->item_shipping( $package );

			if ( ! is_null( $costs ) || $cost_per_order > 0 ) {

				if ( ! is_array( $costs ) )
					$costs = array();

				$costs['order'] = $cost_per_order;

				$rate = array(
					'id' 		=> $this->id,
					'label' 	=> $this->title,
					'cost' 		=> $costs,
					'calc_tax' 	=> 'per_item',
				);

			}
		}

		if ( ! isset( $rate ) )
			return;

		// Register the rate
		$this->add_rate( $rate );

		// Add any extra rates
		if ( sizeof( $this->options ) > 0) foreach ( $this->options as $option ) {

			$this_option = preg_split( '~\s*\|\s*~', trim( $option ) );

			if ( sizeof( $this_option ) !== 3 ) continue;

			$extra_rate = $rate;

			$extra_rate['id']    = $this->id . ':' . sanitize_title($this_option[0]);
			$extra_rate['label'] = $this_option[0];

			$per_order_cost = ( $this_option[2] == 'yes' ) ? 1 : 0;
			$this_cost = $this_option[1];

			if ( is_array( $extra_rate['cost'] ) ) {
				if ( $per_order_cost ) {
					$extra_rate['cost']['order'] = $this_cost;
				} else {
					$total_quantity = 0;

					// Shipping per item
					foreach ( $package['contents'] as $item_id => $values )
						if ( $values['quantity'] > 0 && $values['data']->needs_shipping() )
							$total_quantity += $values['quantity'];

					// Per-product shipping
					$extra_rate['cost']['order'] = $this_cost * $total_quantity;
				}
			} else {
				// If using shipping per class, multiple the cost by the classes we found
				if ( ! $per_order_cost && $this->type == 'class' ) {
					$this_cost = $this_cost * $found_shipping_classes;
				}

				$extra_rate['cost'] = $extra_rate['cost'] + $this_cost;
			}

			$this->add_rate( $extra_rate );
		}
    }


    /**
     * order_shipping function.
     *
     * @access public
     * @param array $package
     * @return float
     */
    function order_shipping( $package ) {
    	$cost 	= null;
    	$fee 	= null;

		if ( sizeof( $this->flat_rates ) > 0 ) {

    		$found_shipping_classes = array();

    		// Find shipping classes for products in the cart
    		if ( sizeof( $package['contents'] ) > 0 ) {
    			foreach ( $package['contents'] as $item_id => $values ) {
    				if ( $values['data']->needs_shipping() )
    					$found_shipping_classes[] = $values['data']->get_shipping_class();
    			}
    		}

    		$found_shipping_classes = array_unique( $found_shipping_classes );

    		// Find most expensive class (if found)
    		foreach ( $found_shipping_classes as $shipping_class ) {
    			if ( isset( $this->flat_rates[ $shipping_class ] ) ) {
    				if ( $this->flat_rates[ $shipping_class ]['cost'] > $cost ) {
    					$cost 	= $this->flat_rates[ $shipping_class ]['cost'];
    					$fee	= $this->flat_rates[ $shipping_class ]['fee'];
    				}
    			} else {
    				// No matching classes so use defaults
    				if ( ! empty( $this->cost ) && $this->cost > $cost ) {
    					$cost 	= $this->cost;
    					$fee	= $this->fee;
    				}
    			}
    		}

		}

		// Default rates if set
		if ( is_null( $cost ) && $this->cost !== '' ) {
			$cost 	= $this->cost;
			$fee 	= $this->fee;
		} elseif ( is_null( $cost ) ) {
			// No match
			return null;
		}

		// Shipping for whole order
		return $cost + $this->get_fee( $fee, $package['contents_cost'] );
    }


    /**
     * class_shipping function.
     *
     * @access public
     * @param array $package
     * @return float
     */
    function class_shipping( $package ) {
		$cost 	= null;
    	$fee 	= null;

		if ( sizeof( $this->flat_rates ) > 0 ) {
    		$found_shipping_classes = array();

    		// Find shipping classes for products in the cart. Store prices too, so we can calc a fee for the class.
    		if ( sizeof( $package['contents'] ) > 0 ) {
    			foreach ( $package['contents'] as $item_id => $values ) {
    				if ( $values['data']->needs_shipping() ) {
    					if ( isset( $found_shipping_classes[ $values['data']->get_shipping_class() ] ) ) {
    						$found_shipping_classes[ $values['data']->get_shipping_class() ] = ( $values['data']->get_price() * $values['quantity'] ) + $found_shipping_classes[ $values['data']->get_shipping_class() ];
    					} else {
    						$found_shipping_classes[ $values['data']->get_shipping_class() ] = ( $values['data']->get_price() * $values['quantity'] );
    					}
    				}
    			}
    		}

    		$found_shipping_classes = array_unique( $found_shipping_classes );

    		$matched = false;

    		// For each found class, add up the costs and fees
    		foreach ( $found_shipping_classes as $shipping_class => $class_price ) {
    			if ( isset( $this->flat_rates[ $shipping_class ] ) ) {
    				$cost 	+= $this->flat_rates[ $shipping_class ]['cost'];
    				$fee	+= $this->get_fee( $this->flat_rates[ $shipping_class ]['fee'], $class_price );
    				$matched = true;
    			} elseif ( $this->cost !== '' ) {
    				// Class not set so we use default rate if its set
    				$cost 	+= $this->cost;
    				$fee	+= $this->get_fee( $this->fee, $class_price );
    				$matched = true;
    			}
    		}
		}

		// Total
		if ( $matched )
			return $cost + $fee;
		else
			return null;
    }


    /**
     * item_shipping function.
     *
     * @access public
     * @param array $package
     * @return array
     */
    function item_shipping( $package ) {
		// Per item shipping so we pass an array of costs (per item) instead of a single value
		$costs = array();

		$matched = false;

		// Shipping per item
		foreach ( $package['contents'] as $item_id => $values ) {
			$_product = $values['data'];

			if ( $values['quantity'] > 0 && $_product->needs_shipping() ) {
				$shipping_class = $_product->get_shipping_class();

				$fee = $cost = 0;

				if ( isset( $this->flat_rates[ $shipping_class ] ) ) {
					$cost 	= $this->flat_rates[ $shipping_class ]['cost'];
    				$fee	= $this->get_fee( $this->flat_rates[ $shipping_class ]['fee'], $_product->get_price() );
    				$matched = true;
				} elseif ( $this->cost !== '' ) {
					$cost 	= $this->cost;
					$fee	= $this->get_fee( $this->fee, $_product->get_price() );
					$matched = true;
				}

				$costs[ $item_id ] = ( ( $cost + $fee ) * $values['quantity'] );
			}
		}

		if ( $matched )
			return $costs;
		else
			return null;
    }


	/**
	 * Admin Panel Options
	 * - Options for bits like 'title' and availability on a country-by-country basis
	 *
	 * @since 1.0.0
	 * @access public
	 * @return void
	 */
	public function admin_options() {
		global $woocommerce;
    	?>
    	<h3><?php echo $this->admin_page_heading; ?></h3>
    	<p><?php echo $this->admin_page_description; ?></p>
    	<table class="form-table">
    	<?php
    		// Generate the HTML For the settings form.
    		$this->generate_settings_html();
    		?>
	    	<tr valign="top">
	            <th scope="row" class="titledesc"><?php _e( 'Flat Rates', 'woocommerce' ); ?>:</th>
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
	            		<tfoot>
	            			<tr>
	            				<th colspan="2"><a href="#" class="add button"><?php _e( '+ Add Flat Rate', 'woocommerce' ); ?></a></th>
	            				<th colspan="2"><small><?php _e( 'Add rates for shipping classes here &mdash; they will override the default costs defined above.', 'woocommerce' ); ?></small> <a href="#" class="remove button"><?php _e( 'Delete selected rates', 'woocommerce' ); ?></a></th>
	            			</tr>
	            		</tfoot>
	            		<tbody class="flat_rates">
	                	<?php
	                	$i = -1;
	                	if ( $this->flat_rates ) {
	                		foreach ( $this->flat_rates as $class => $rate ) {
		                		$i++;

		                		echo '<tr class="flat_rate">
		                			<td class="check-column"><input type="checkbox" name="select" /></td>
		                			<td class="flat_rate_class">
		                					<select name="' . esc_attr( $this->id . '_class[' . $i . ']' ) . '" class="select">';

		                		if ( $woocommerce->shipping->get_shipping_classes() ) {
			                		foreach ( $woocommerce->shipping->get_shipping_classes() as $shipping_class ) {
			                			echo '<option value="'.$shipping_class->slug.'" '.selected($shipping_class->slug, $class, false).'>'.$shipping_class->name.'</option>';
			                		}
		                		} else {
		                			echo '<option value="">'.__( 'Select a class&hellip;', 'woocommerce' ).'</option>';
		                		}

				                echo '</select>
				               		</td>
				                    <td><input type="number" step="any" min="0" value="' . esc_attr( $rate['cost'] ) . '" name="' . esc_attr( $this->id .'_cost[' . $i . ']' ) . '" placeholder="'.__( '0.00', 'woocommerce' ).'" size="4" /></td>
				                    <td><input type="text" value="' . esc_attr( $rate['fee'] ) . '" name="' . esc_attr( $this->id .'_fee[' . $i . ']' ) . '" placeholder="'.__( '0.00', 'woocommerce' ).'" size="4" /></td>
			                    </tr>';
	                		}
	                	}
	                	?>
	                	</tbody>
	                </table>
	            </td>
	        </tr>
		</table><!--/.form-table-->
       	<script type="text/javascript">
			jQuery(function() {

				jQuery('#<?php echo $this->id; ?>_flat_rates').on( 'click', 'a.add', function(){

					var size = jQuery('#<?php echo $this->id; ?>_flat_rates tbody .flat_rate').size();

					jQuery('<tr class="flat_rate">\
						<td class="check-column"><input type="checkbox" name="select" /></td>\
            			<td class="flat_rate_class">\
            				<select name="<?php echo $this->id; ?>_class[' + size + ']" class="select">\
	               				<?php
	               				if ($woocommerce->shipping->get_shipping_classes()) :
			                		foreach ($woocommerce->shipping->get_shipping_classes() as $class) :
			                			echo '<option value="' . esc_attr( $class->slug ) . '">' . esc_js( $class->name ) . '</option>';
			                		endforeach;
		                		else :
		                			echo '<option value="">'.__( 'Select a class&hellip;', 'woocommerce' ).'</option>';
		                		endif;
	               				?>\
	               			</select>\
	               		</td>\
	                    <td><input type="number" step="any" min="0" name="<?php echo $this->id; ?>_cost[' + size + ']" placeholder="0.00" size="4" /></td>\
	                    <td><input type="text" name="<?php echo $this->id; ?>_fee[' + size + ']" placeholder="0.00" size="4" /></td>\
                    </tr>').appendTo('#<?php echo $this->id; ?>_flat_rates table tbody');

					return false;
				});

				// Remove row
				jQuery('#<?php echo $this->id; ?>_flat_rates').on( 'click', 'a.remove', function(){
					var answer = confirm("<?php _e( 'Delete the selected rates?', 'woocommerce' ); ?>")
					if (answer) {
						jQuery('#<?php echo $this->id; ?>_flat_rates table tbody tr td.check-column input:checked').each(function(i, el){
							jQuery(el).closest('tr').remove();
						});
					}
					return false;
				});

			});
		</script>
    	<?php
    } // End admin_options()


    /**
     * process_flat_rates function.
     *
     * @access public
     * @return void
     */
    function process_flat_rates() {
		// Save the rates
		$flat_rate_class = array();
		$flat_rate_cost = array();
		$flat_rate_fee = array();
		$flat_rates = array();

		if ( isset( $_POST[ $this->id . '_class'] ) ) $flat_rate_class = array_map( 'woocommerce_clean', $_POST[ $this->id . '_class'] );
		if ( isset( $_POST[ $this->id . '_cost'] ) )  $flat_rate_cost  = array_map( 'woocommerce_clean', $_POST[ $this->id . '_cost'] );
		if ( isset( $_POST[ $this->id . '_fee'] ) )   $flat_rate_fee   = array_map( 'woocommerce_clean', $_POST[ $this->id . '_fee'] );

		// Get max key
		$values = $flat_rate_class;
		ksort( $values );
		$value = end( $values );
		$key = key( $values );

		for ( $i = 0; $i <= $key; $i++ ) {
			if ( isset( $flat_rate_class[ $i ] ) && isset( $flat_rate_cost[ $i ] ) && isset( $flat_rate_fee[ $i ] ) ) {

				$flat_rate_cost[$i] = number_format($flat_rate_cost[$i], 2,  '.', '');

				// Add to flat rates array
				$flat_rates[ sanitize_title($flat_rate_class[$i]) ] = array(
					'cost' => $flat_rate_cost[ $i ],
					'fee'  => $flat_rate_fee[ $i ],
				);
			}
		}

		update_option( $this->flat_rate_option, $flat_rates );

		$this->get_flat_rates();
    }


    /**
     * get_flat_rates function.
     *
     * @access public
     * @return void
     */
    function get_flat_rates() {
    	$this->flat_rates = array_filter( (array) get_option( $this->flat_rate_option ) );
    }

}