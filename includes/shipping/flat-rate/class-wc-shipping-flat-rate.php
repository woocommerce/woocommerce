<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Flat Rate Shipping Method
 *
 * @class 		WC_Shipping_Flat_Rate
 * @version		2.4.0
 * @package		WooCommerce/Classes/Shipping
 * @author 		WooThemes
 */
class WC_Shipping_Flat_Rate extends WC_Shipping_Method {

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->id                 = 'flat_rate';
		$this->method_title       = __( 'Flat Rate', 'woocommerce' );
		$this->method_description = __( 'Flat Rate Shipping lets you charge a fixed rate for shipping.', 'woocommerce' );

		add_action( 'woocommerce_update_options_shipping_' . $this->id, array( $this, 'process_admin_options' ) );

		$this->init();
	}

	/**
	 * init function.
	 */
	public function init() {
		// Load the settings.
		$this->init_form_fields();
		$this->init_settings();

		// Define user set variables
		$this->title        = $this->get_option( 'title' );
		$this->availability = $this->get_option( 'availability' );
		$this->countries    = $this->get_option( 'countries' );
		$this->type         = $this->get_option( 'type' );
		$this->tax_status   = $this->get_option( 'tax_status' );
	}

	/**
	 * Initialise Settings Form Fields
	 */
	public function init_form_fields() {
		$this->form_fields = include( 'includes/settings-flat-rate.php' );
	}

	/**
	 * Evaluate a cost from a sum/string
	 * @param  string $sum
	 * @param  array  $args
	 * @return string
	 */
	protected function evalulate_cost( $sum, $args = array() ) {
		include_once( 'includes/class-wc-eval-math.php' );

		$sum = str_replace(
			array(
				'[qty]',
				'[cost]'
			),
			array(
				$args['qty'],
				$args['cost']
			),
			$sum
		);

		$m = new WC_Eval_Math;
		return $m->evaluate( $sum );
	}

	/**
	 * calculate_shipping function.
	 *
	 * @param array $package (default: array())
	 */
	public function calculate_shipping( $package = array() ) {
		$rate = array(
			'id'    => $this->id,
			'label' => $this->title,
			'cost'  => 0,
		);

		// Calculate the costs
		$rate['cost'] = $this->evalulate_cost( $this->get_option( 'cost' ), array(
			'qty'  => $this->get_package_item_qty( $package ),
			'cost' => $package['contents_cost']
		) );

		// Add shipping class costs
		$found_shipping_classes = $this->find_shipping_classes( $package );

		foreach ( $found_shipping_classes as $shipping_class => $products ) {
			$class_cost = $this->get_option( 'class_cost_' . $shipping_class, '' );

			if ( $class_cost ) {
				$rate['cost'] += $this->evalulate_cost( $class_cost, array(
					'qty'  => array_sum( wp_list_pluck( $products, 'quantity' ) ),
					'cost' => array_sum( wp_list_pluck( $products, 'line_total' ) )
				) );
			}
		}

		// Add the rate
		$this->add_rate( $rate );

		/**
		 * Developers can add additional flat rates based on this one via this action since @version 2.4
		 *
		 * Previously there were (overly complex) options to add additional rates however this was not user
		 * friendly and goes against what Flat Rate Shipping was originally intended for.
		 *
		 * This example shows how you can add an extra rate based on this flat rate via custom function:
		 *
		 * 		add_action( 'woocommerce_flat_rate_shipping_add_rate', 'add_another_custom_flat_rate', 10, 2 );
		 *
		 * 		function add_another_custom_flat_rate( $method, $rate ) {
		 * 			$new_rate          = $rate;
		 * 			$new_rate['id']    .= ':' . 'custom_rate_name'; // Append a custom ID
		 * 			$new_rate['label'] = 'Rushed Shipping'; // Rename to 'Rushed Shipping'
		 * 			$new_rate['cost']  += 2; // Add $2 to the cost
		 *
		 * 			// Add it to WC
		 * 			$method->add_rate( $new_rate );
		 * 		}
		 */
		do_action( 'woocommerce_flat_rate_shipping_add_rate', $this, $rate );
	}

	/**
	 * Get items in package
	 * @param  array $package
	 * @return int
	 */
	public function get_package_item_qty( $package ) {
		$total_quantity = 0;
		foreach ( $package['contents'] as $item_id => $values ) {
			if ( $values['quantity'] > 0 && $values['data']->needs_shipping() ) {
				$total_quantity += $values['quantity'];
			}
		}
		return $total_quantity;
	}

	/**
	 * Finds and returns shipping classes and the products with said class.
	 * @param mixed $package
	 * @return array
	 */
	public function find_shipping_classes( $package ) {
		$found_shipping_classes = array();

		foreach ( $package['contents'] as $item_id => $values ) {
			if ( $values['data']->needs_shipping() ) {
				$found_class = $values['data']->get_shipping_class();

				if ( ! isset( $found_shipping_classes[ $found_class ] ) ) {
					$found_shipping_classes[ $found_class ] = array();
				}

				$found_shipping_classes[ $found_class ][ $item_id ] = $values;
			}
		}

		return $found_shipping_classes;
	}
}
