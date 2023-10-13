# Customizing checkout fields using actions and filters

**Note:** We are unable to provide support for customizations under our **[Support Policy](http://www.woocommerce.com/support-policy/)**. If you need to further customize a snippet, or extend its functionality, we highly recommend [**Codeable**](https://codeable.io/?ref=z4Hnp), or a [**Certified WooExpert**](https://woocommerce.com/experts/).

If you are unfamiliar with code and resolving potential conflicts, we have an extension that can help: [WooCommerce Checkout Field Editor](https://woocommerce.com/products/woocommerce-checkout-field-editor/). Installing and activating this extension overrides any code below that you try to implement; and you cannot have custom checkout field code in your functions.php file when the extension is activated.

Custom code should be copied into your child theme’s **functions.php** file.

## [How Are Checkout Fields Loaded to WooCommerce?](#how-are-checkout-fields-loaded-to-woocommerce)

[↑ Back to top](#doc-title 'Back to top')

The billing and shipping fields for checkout pull from the countries class (`class-wc-countries.php`) and the **`get_address_fields`** function. This allows WooCommerce to enable/disable fields based on the user’s location.

Before returning these fields, WooCommerce puts the fields through a *filter*. This allows them to be edited by third-party plugins, themes and your own custom code.

Billing:

$address_fields = apply_filters('woocommerce_billing_fields', $address_fields);

Shipping:

$address_fields = apply_filters('woocommerce_shipping_fields', $address_fields);

The checkout class adds the loaded fields to its ‘checkout_fields’ array, as well as adding a few other fields like “order notes”.

$this->checkout\_fields\['billing'\]    = $woocommerce->countries->get\_address\_fields( $this->get\_value('billing\_country'), 'billing\_' );
$this->checkout_fields\['shipping'\] = $woocommerce->countries->get\_address\_fields( $this->get\_value('shipping\_country'), 'shipping\_' );
$this->checkout_fields\['account'\] = array(
'account_username' => array(
'type' => 'text',
'label' => \_\_('Account username', 'woocommerce'),
'placeholder' => \_x('Username', 'placeholder', 'woocommerce')
),
'account_password' => array(
'type' => 'password',
'label' => \_\_('Account password', 'woocommerce'),
'placeholder' => \_x('Password', 'placeholder', 'woocommerce'),
'class' => array('form-row-first')
),
'account_password-2' => array(
'type' => 'password',
'label' => \_\_('Account password', 'woocommerce'),
'placeholder' => \_x('Password', 'placeholder', 'woocommerce'),
'class' => array('form-row-last'),
'label_class' => array('hidden')
)
);
$this->checkout_fields\['order'\] = array(
'order_comments' => array(
'type' => 'textarea',
'class' => array('notes'),
'label' => \_\_('Order Notes', 'woocommerce'),
'placeholder' => \_x('Notes about your order, e.g. special notes for delivery.', 'placeholder', 'woocommerce')
)
);

This array is also passed through a filter:

$this->checkout_fields = apply_filters('woocommerce_checkout_fields', $this->checkout_fields);

That means you have **full control** over checkout fields – you only need to know how to access them.

## [Overriding Core Fields](#overriding-core-fields)

[↑ Back to top](#doc-title 'Back to top')

Hooking into the  **`woocommerce_checkout_fields`** filter lets you override any field. As an example, let’s change the placeholder on the order_comments fields. Currently, it’s set to:

\_x('Notes about your order, e.g. special notes for delivery.', 'placeholder', 'woocommerce')

We can change this by adding a function to our theme functions.php file:

// Hook in
add_filter( 'woocommerce_checkout_fields' , 'custom_override_checkout_fields' );

// Our hooked in function - $fields is passed via the filter!
function custom_override_checkout_fields( $fields ) {
$fields\['order'\]\['order_comments'\]\['placeholder'\] = 'My new placeholder';
return $fields;
}

You can override other parts, such as labels:

// Hook in
add_filter( 'woocommerce_checkout_fields' , 'custom_override_checkout_fields' );

// Our hooked in function - $fields is passed via the filter!
function custom_override_checkout_fields( $fields ) {
$fields\['order'\]\['order_comments'\]\['placeholder'\] = 'My new placeholder';
$fields\['order'\]\['order_comments'\]\['label'\] = 'My new label';
return $fields;
}

Or remove fields:

// Hook in
add_filter( 'woocommerce_checkout_fields' , 'custom_override_checkout_fields' );

// Our hooked in function - $fields is passed via the filter!
function custom\_override\_checkout\_fields( $fields ) {
     unset($fields\['order'\]\['order_comments'\]);

     return $fields;

}

Here’s a full list of fields in the array passed to `woocommerce_checkout_fields`:

-   Billing
    -   `billing_first_name`
    -   `billing_last_name`
    -   `billing_company`
    -   `billing_address_1`
    -   `billing_address_2`
    -   `billing_city`
    -   `billing_postcode`
    -   `billing_country`
    -   `billing_state`
    -   `billing_email`
    -   `billing_phone`
-   Shipping
    -   `shipping_first_name`
    -   `shipping_last_name`
    -   `shipping_company`
    -   `shipping_address_1`
    -   `shipping_address_2`
    -   `shipping_city`
    -   `shipping_postcode`
    -   `shipping_country`
    -   `shipping_state`
-   Account
    -   `account_username`
    -   `account_password`
    -   `account_password-2`
-   Order
    -   `order_comments`

Each field contains an array of properties:

-   `type` – type of field (text, textarea, password, select)
-   `label` – label for the input field
-   `placeholder` – placeholder for the input
-   `class` – class for the input
-   `required` – true or false, whether or not the field is require
-   `clear` – true or false, applies a clear fix to the field/label
-   `label_class` – class for the label element
-   `options` – for select boxes, array of options (key => value pairs)

In specific cases you need to use the **`woocommerce_default_address_fields`** filter. This filter is applied to all billing and shipping default fields:

-   `country`
-   `first_name`
-   `last_name`
-   `company`
-   `address_1`
-   `address_2`
-   `city`
-   `state`
-   `postcode`

For example, to make the `address_1` field optional:

// Hook in
add_filter( 'woocommerce_default_address_fields' , 'custom_override_default_address_fields' );

// Our hooked in function - $address_fields is passed via the filter!
function custom_override_default_address_fields( $address_fields ) {
$address_fields\['address_1'\]\['required'\] = false;

     return $address\_fields;

}

### [Defining select options](#defining-select-options)

[↑ Back to top](#doc-title 'Back to top')

If you are adding a field with type ‘select’, as stated above you would define key/value pairs. For example:

$fields\['billing'\]\['your_field'\]\['options'\] = array(
'option_1' => 'Option 1 text',
'option_2' => 'Option 2 text'
);

## [Priority](#priority)

[↑ Back to top](#doc-title 'Back to top')

Priority in regards to PHP code helps establish when a bit of code — called a function — runs in relation to a page load. It is set inside of each function and is useful when overriding existing code for custom display.

Code with a higher number set as the priority will run after code with a lower number, meaning code with a priority of 20 will run after code with 10 priority.

The priority argument is set during the [add_action](https://developer.wordpress.org/reference/functions/add_action/) function, after you establish which hook you’re connecting to and what the name of your custom function will be.

In the example below, blue text is the name of the hook we’re modifying, green text is the name of our custom function, and red is the priority we set.

![](https://woocommerce.com/wp-content/uploads/2012/04/priority-markup.png)

## [Examples](#examples)

[↑ Back to top](#doc-title 'Back to top')

### [Change Return to Shop button redirect URL](#change-return-to-shop-button-redirect-url)

[↑ Back to top](#doc-title 'Back to top')

In this example, the code is set to redirect the “Return to Shop” button found in the cart to a category that lists products for sale at http://example.url/category/specials/.

.gist table { margin-bottom: 0; }

/\*\*

\* Changes the redirect URL for the Return To Shop button in the cart.

\*/

function wc_empty_cart_redirect_url() {

return 'http://example.url/category/specials/';

}

add_filter( 'woocommerce_return_to_shop_redirect', 'wc_empty_cart_redirect_url', 10 );

[view raw](https://gist.github.com/woogists/689b375f3ac8f03632f3c9e53e78b0d5/raw/d21d34dd073c2911c01062043e6f8af9bef5c514/wc-empty-cart-redirect-url.php) [wc-empty-cart-redirect-url.php](https://gist.github.com/woogists/689b375f3ac8f03632f3c9e53e78b0d5#file-wc-empty-cart-redirect-url-php) hosted with ❤ by [GitHub](https://github.com)

There, we can see the priority is set to 10. This is the typical default for WooCommerce functions and scripts, so that may not be sufficient to override that button’s functionality.

Instead, we can change the priority to any number greater than 10. While 11 would work, best practice dictates we use increments of ten, so 20, 30, and so on.

.gist table { margin-bottom: 0; }

/\*\*

\* Changes the redirect URL for the Return To Shop button in the cart.

\*/

function wc_empty_cart_redirect_url() {

return 'http://example.com/category/specials/';

}

add_filter( 'woocommerce_return_to_shop_redirect', 'wc_empty_cart_redirect_url', 20 );

[view raw](https://gist.github.com/woogists/12ffb9dcd39026c17c34365a6ce8a077/raw/2c6802495e3dac0c8ad4dc1c8d415c2e03a4a531/wc-empty-cart-redirect-url-priority.php) [wc-empty-cart-redirect-url-priority.php](https://gist.github.com/woogists/12ffb9dcd39026c17c34365a6ce8a077#file-wc-empty-cart-redirect-url-priority-php) hosted with ❤ by [GitHub](https://github.com)

With priority, we can have two functions that are acting on the same hook. Normally this would cause a variety of problems, but since we’ve established one has a higher priority than the other, our site will only load the appropriate function, and we will be taken to the Specials page as intended with the code below.

.gist table { margin-bottom: 0; }

/\*\*

\* Changes the redirect URL for the Return To Shop button in the cart.

\* BECAUSE THIS FUNCTION HAS THE PRIORITY OF 20, IT WILL RUN AFTER THE FUNCTION BELOW (HIGHER NUMBERS RUN LATER)

\*/

function wc_empty_cart_redirect_url() {

return 'http://example.com/category/specials/';

}

add_filter( 'woocommerce_return_to_shop_redirect', 'wc_empty_cart_redirect_url', 20 );

/\*\*

\* Changes the redirect URL for the Return To Shop button in the cart.

\* EVEN THOUGH THIS FUNCTION WOULD NORMALLY RUN LATER BECAUSE IT'S CODED AFTERWARDS, THE 10 PRIORITY IS LOWER THAN 20 ABOVE

\*/

function wc_empty_cart_redirect_url() {

return 'http://example.com/shop/';

}

add_filter( 'woocommerce_return_to_shop_redirect', 'wc_empty_cart_redirect_url', 10 );

[view raw](https://gist.github.com/woogists/89b9ab404d4f236b56488521e7b8b1c3/raw/d875beb8a87376fa72c4cb232f39db176cdd7c23/wc-empty-cart-redirect-url-double-snippet.php) [wc-empty-cart-redirect-url-double-snippet.php](https://gist.github.com/woogists/89b9ab404d4f236b56488521e7b8b1c3#file-wc-empty-cart-redirect-url-double-snippet-php) hosted with ❤ by [GitHub](https://github.com)

### [Adding Custom Shipping And Billing Fields](#adding-custom-shipping-and-billing-fields)

[↑ Back to top](#doc-title 'Back to top')

Adding fields is done in a similar way to overriding fields. For example, let’s add a new field to shipping fields – `shipping_phone`:

.gist table { margin-bottom: 0; }

// Hook in

add_filter( 'woocommerce_checkout_fields' , 'custom_override_checkout_fields' );

// Our hooked in function – $fields is passed via the filter!

function custom_override_checkout_fields( $fields ) {

$fields\['shipping'\]\['shipping_phone'\] = array(

'label' =\> \_\_('Phone', 'woocommerce'),

'placeholder' =\> \_x('Phone', 'placeholder', 'woocommerce'),

'required' =\> false,

'class' =\> array('form-row-wide'),

'clear' =\> true

);

return $fields;

}

/\*\*

\* Display field value on the order edit page

\*/

add_action( 'woocommerce_admin_order_data_after_shipping_address', 'my_custom_checkout_field_display_admin_order_meta', 10, 1 );

function my_custom_checkout_field_display_admin_order_meta($order){

echo '<p\><strong\>'.\_\_('Phone From Checkout Form').':</strong\> ' . get_post_meta( $order-\>get_id(), '\_shipping_phone', true ) . '</p\>';

}

[view raw](https://gist.github.com/woogists/7241afdfa6f21d561ce85f7247a0f282/raw/d9b1eb55bc30dd80860f3f1790241c2ceb82e017/wc-override-checkout-fields.php) [wc-override-checkout-fields.php](https://gist.github.com/woogists/7241afdfa6f21d561ce85f7247a0f282#file-wc-override-checkout-fields-php) hosted with ❤ by [GitHub](https://github.com)

![It's alive!](https://woocommerce.com/wp-content/uploads/2012/04/WooCommerce-Codex-Shipping-Field-Hook.png)

It’s alive!

What do we do with the new field? Nothing. Because we defined the field in the checkout_fields array, the field is automatically processed and saved to the order post meta (in this case, \_shipping_phone). If you want to add validation rules, see the checkout class where there are additional hooks you can use.

### [Adding a Custom Special Field](#adding-a-custom-special-field)

[↑ Back to top](#doc-title 'Back to top')

To add a custom field is similar. Let’s add a new field to checkout, after the order notes, by hooking into the following:

.gist table { margin-bottom: 0; }

/\*\*

\* Add the field to the checkout

\*/

add_action( 'woocommerce_after_order_notes', 'my_custom_checkout_field' );

function my_custom_checkout_field( $checkout ) {

echo '<div id\="my_custom_checkout_field"\><h2\>' . \_\_('My Field') . '</h2\>';

woocommerce_form_field( 'my_field_name', array(

'type' =\> 'text',

'class' =\> array('my-field-class form-row-wide'),

'label' =\> \_\_('Fill in this field'),

'placeholder' =\> \_\_('Enter something'),

), $checkout-\>get_value( 'my_field_name' ));

echo '</div\>';

}

[view raw](https://gist.github.com/woogists/981c3b405b4288c562a64f59db9c5e13/raw/6c8a970e5fa938819d1843782e68f55711f404e1/wc-custom-checkout-field.php) [wc-custom-checkout-field.php](https://gist.github.com/woogists/981c3b405b4288c562a64f59db9c5e13#file-wc-custom-checkout-field-php) hosted with ❤ by [GitHub](https://github.com)

This gives us:

![WooCommerce Codex - Checkout Field Hook](https://woocommerce.com/wp-content/uploads/2012/04/WooCommerce-Codex-Checkout-Field-Hook.png)

Next we need to validate the field when the checkout form is posted. For this example the field is required and not optional:

.gist table { margin-bottom: 0; }

/\*\*

\* Process the checkout

\*/

add_action('woocommerce_checkout_process', 'my_custom_checkout_field_process');

function my_custom_checkout_field_process() {

// Check if set, if its not set add an error.

if ( ! $\_POST\['my_field_name'\] )

wc_add_notice( \_\_( 'Please enter something into this new shiny field.' ), 'error' );

}

[view raw](https://gist.github.com/woogists/d8d6845db93eb35b36102f4fd9ed8202/raw/41915bfa3dd053dcc625e3861fe8adcedde0773d/wc-process-checkout-field.php) [wc-process-checkout-field.php](https://gist.github.com/woogists/d8d6845db93eb35b36102f4fd9ed8202#file-wc-process-checkout-field-php) hosted with ❤ by [GitHub](https://github.com)

A checkout error is displayed if the field is blank:

![WooCommerce Codex - Checkout Field Notice](https://woocommerce.com/wp-content/uploads/2012/04/WooCommerce-Codex-Checkout-Field-Notice.png)

Finally, let’s save the new field to order custom fields using the following code:

.gist table { margin-bottom: 0; }

/\*\*

\* Update the order meta with field value

\*/

add_action( 'woocommerce_checkout_update_order_meta', 'my_custom_checkout_field_update_order_meta' );

function my_custom_checkout_field_update_order_meta( $order_id ) {

if ( ! empty( $\_POST\['my_field_name'\] ) ) {

update_post_meta( $order_id, 'My Field', sanitize_text_field( $\_POST\['my_field_name'\] ) );

}

}

[view raw](https://gist.github.com/woogists/9bc6e4b0de1eeeb53395596729ca5a3f/raw/0f68c1de77f6613c59eca74570a8b53dbbe8e969/wc-save-custom-checkout-field.php) [wc-save-custom-checkout-field.php](https://gist.github.com/woogists/9bc6e4b0de1eeeb53395596729ca5a3f#file-wc-save-custom-checkout-field-php) hosted with ❤ by [GitHub](https://github.com)

The field is now saved to the order.

If you wish to display the custom field value on the admin order edition page, you can add this code:

.gist table { margin-bottom: 0; }

/\*\*

\* Display field value on the order edit page

\*/

add_action( 'woocommerce_admin_order_data_after_billing_address', 'my_custom_checkout_field_display_admin_order_meta', 10, 1 );

function my_custom_checkout_field_display_admin_order_meta($order){

echo '<p\><strong\>'.\_\_('My Field').':</strong\> ' . get_post_meta( $order-\>id, 'My Field', true ) . '</p\>';

}

[view raw](https://gist.github.com/woogists/fba058890c3a49f649fc6fe8eb9ddf7c/raw/4eebf7635b25df13c52848b7f98bd28ebb59187b/wc-display-custom-checkout-field-order.php) [wc-display-custom-checkout-field-order.php](https://gist.github.com/woogists/fba058890c3a49f649fc6fe8eb9ddf7c#file-wc-display-custom-checkout-field-order-php) hosted with ❤ by [GitHub](https://github.com)

This is the result:

[![checkout_field_custom_field_admin](https://woocommerce.com/wp-content/uploads/2012/04/checkout_field_custom_field_admin.png)](https://woocommerce.com/wp-content/uploads/2012/04/checkout_field_custom_field_admin.png)

### [Make phone number not required](#make-phone-number-not-required)

[↑ Back to top](#doc-title 'Back to top')

.gist table { margin-bottom: 0; }

add_filter( 'woocommerce_billing_fields', 'wc_npr_filter_phone', 10, 1 );

function wc_npr_filter_phone( $address_fields ) {

$address_fields\['billing_phone'\]\['required'\] = false;

return $address_fields;

}

[view raw](https://gist.github.com/woogists/c32b71157e20c922ac975759b0f2e68c/raw/ecda527ab11d37a74704facb21dbc5acca461848/wc-custom-checkout-field-not-required.php) [wc-custom-checkout-field-not-required.php](https://gist.github.com/woogists/c32b71157e20c922ac975759b0f2e68c#file-wc-custom-checkout-field-not-required-php) hosted with ❤ by [GitHub](https://github.com)
