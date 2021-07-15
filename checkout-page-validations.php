add_filter( 'woocommerce_checkout_fields', 'bbloomer_checkout_fields_custom_attributes', 9999 );
 
function bbloomer_checkout_fields_custom_attributes( $fields ) {
   $fields['billing']['billing_first_name']['maxlength'] = 16;
   $fields['billing']['billing_last_name']['maxlength'] = 16;
   $fields['billing']['billing_company']['maxlength'] = 15;
   $fields['shipping']['shipping_first_name']['maxlength'] = 15;
   $fields['shipping']['shipping_last_name']['maxlength'] = 15;
   $fields['shipping']['shipping_company']['maxlength'] = 15;
   return $fields;
}

add_action('woocommerce_checkout_process', 'wh_alphaCheckCheckoutFields');

function wh_alphaCheckCheckoutFields() {
    $billing_first_name = filter_input(INPUT_POST, 'billing_first_name');
    $billing_last_name = filter_input(INPUT_POST, 'billing_last_name');
	//$billing_phone = filter_input(INPUT_POST, 'billing_phone');
    $shipping_first_name = filter_input(INPUT_POST, 'shipping_first_name');
    $shipping_last_name = filter_input(INPUT_POST, 'shipping_last_name');
    $ship_to_different_address = filter_input(INPUT_POST, 'ship_to_different_address');

    if (empty(trim($billing_first_name)) || !ctype_alpha($billing_first_name)) {
        wc_add_notice(__('Only alphabets are alowed in <strong>Billing First Name</strong>.'), 'error');
    }
    if (empty(trim($billing_last_name)) || !ctype_alpha($billing_last_name)) {
        wc_add_notice(__('Only alphabets are alowed in <strong>Billing Last Name</strong>.'), 'error');
    }
	/*if (empty(trim($billing_phone)) || !ctype_digit($billing_phone)) {
        wc_add_notice(__('Only number are alowed in <strong>Billing Phone</strong>.'), 'error');
    }*/
    // Check if Ship to a different address is set, if it's set then validate shipping fields.
    if (!empty($ship_to_different_address)) {
        if (empty(trim($shipping_first_name)) || !ctype_alpha($shipping_first_name)) {
            wc_add_notice(__('Only alphabets are alowed in <strong>Shipping First Name</strong>.'), 'error');
        }
        if (empty(trim($shipping_last_name)) || !ctype_alpha($shipping_last_name)) {
            wc_add_notice(__('Only alphabets are alowed in <strong>Shipping Last Name</strong>.'), 'error');
        }
    }
}
