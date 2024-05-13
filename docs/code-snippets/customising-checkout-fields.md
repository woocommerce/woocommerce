---
post_title: Customizing checkout fields using actions and filters
tags: code-snippet
---

If you are unfamiliar with code and resolving potential conflicts, we have an extension that can help: [WooCommerce Checkout Field Editor](https://woocommerce.com/products/woocommerce-checkout-field-editor/). Installing and activating this extension overrides any code below that you try to implement; and you cannot have custom checkout field code in your functions.php file when the extension is activated.

Custom code should be copied into your child theme's **functions.php** file.

## How Are Checkout Fields Loaded to WooCommerce?

The billing and shipping fields for checkout pull from the countries class `class-wc-countries.php` and the **`get_address_fields`** function. This allows WooCommerce to enable/disable fields based on the user's location.

Before returning these fields, WooCommerce puts the fields through a *filter*. This allows them to be edited by third-party plugins, themes and your own custom code.

Billing:

```php
$address_fields = apply_filters( 'woocommerce_billing_fields', $address_fields );
```

Shipping:

```php
$address_fields = apply_filters( 'woocommerce_shipping_fields', $address_fields );
```

The checkout class adds the loaded fields to its `checkout_fields` array, as well as adding a few other fields like "order notes".

```php
$this->checkout_fields['billing']  = $woocommerce->countries->get_address_fields( $this->get_value( 'billing_country' ), 'billing_' );
$this->checkout_fields['shipping'] = $woocommerce->countries->get_address_fields( $this->get_value( 'shipping_country' ), 'shipping_' );
$this->checkout_fields['account']  = array(
    'account_username'   => array(
        'type'        => 'text',
        'label'       => __( 'Account username', 'woocommerce' ),
        'placeholder' => _x( 'Username', 'placeholder', 'woocommerce' ),
    ),
    'account_password'   => array(
        'type'        => 'password',
        'label'       => __( 'Account password', 'woocommerce' ),
        'placeholder' => _x( 'Password', 'placeholder', 'woocommerce' ),
        'class'       => array( 'form-row-first' )
    ),
    'account_password-2' => array(
        'type'        => 'password',
        'label'       => __( 'Account password', 'woocommerce' ),
        'placeholder' => _x( 'Password', 'placeholder', 'woocommerce' ),
        'class'       => array( 'form-row-last' ),
        'label_class' => array( 'hidden' )
    ),
);
$this->checkout_fields['order']   = array(
    'order_comments' => array(
        'type'        => 'textarea',
        'class'       => array( 'notes' ),
        'label'       => __( 'Order Notes', 'woocommerce' ),
        'placeholder' => _x( 'Notes about your order, e.g. special notes for delivery.', 'placeholder', 'woocommerce' )
    )
);
```

This array is also passed through a filter:

```php
$this->checkout_fields = apply_filters( 'woocommerce_checkout_fields', $this->checkout_fields );
```

That means you have **full control** over checkout fields - you only need to know how to access them.

## Overriding Core Fields

Hooking into the  **`woocommerce_checkout_fields`** filter lets you override any field. As an example, let's change the placeholder on the order_comments fields. Currently, it's set to:

```php
_x( 'Notes about your order, e.g. special notes for delivery.', 'placeholder', 'woocommerce' );
```

We can change this by adding a function to our theme functions.php file:

```php
// Hook in
add_filter( 'woocommerce_checkout_fields' , 'custom_override_checkout_fields' );

// Our hooked in function - $fields is passed via the filter!
function custom_override_checkout_fields( $fields ) {
    $fields['order']['order_comments']['placeholder'] = 'My new placeholder';
    return $fields;
}
```

You can override other parts, such as labels:

```php
// Hook in
add_filter( 'woocommerce_checkout_fields' , 'custom_override_checkout_fields' );

// Our hooked in function - $fields is passed via the filter!
function custom_override_checkout_fields( $fields ) {
    $fields['order']['order_comments']['placeholder'] = 'My new placeholder';
    $fields['order']['order_comments']['label']       = 'My new label';
    return $fields;
}
```

Or remove fields:

```php
// Hook in
add_filter( 'woocommerce_checkout_fields' , 'custom_override_checkout_fields' );

// Our hooked in function - $fields is passed via the filter!
function custom_override_checkout_fields( $fields ) {
    unset( $fields['order']['order_comments'] );

    return $fields;
}
```

Here's a full list of fields in the array passed to `woocommerce_checkout_fields`:

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

-   `type` - type of field (text, textarea, password, select)
-   `label` - label for the input field
-   `placeholder` - placeholder for the input
-   `class` - class for the input
-   `required` - true or false, whether or not the field is require
-   `clear` - true or false, applies a clear fix to the field/label
-   `label_class` - class for the label element
-   `options` - for select boxes, array of options (key => value pairs)

In specific cases you need to use the **`woocommerce_default_address_fields`** filter. This filter is applied to all billing and shipping default fields:

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

```php
// Hook in
add_filter( 'woocommerce_default_address_fields' , 'custom_override_default_address_fields' );

// Our hooked in function - $address_fields is passed via the filter!
function custom_override_default_address_fields( $address_fields ) {
    $address_fields['address_1']['required'] = false;

    return $address_fields;
}
```

### Defining select options

If you are adding a field with type 'select', as stated above you would define key/value pairs. For example:

```php
$fields['billing']['your_field']['options'] = array(
    'option_1' => 'Option 1 text',
    'option_2' => 'Option 2 text'
);
```

## Priority

Priority in regards to PHP code helps establish when a bit of code - called a function - runs in relation to a page load. It is set inside of each function and is useful when overriding existing code for custom display.

Code with a higher number set as the priority will run after code with a lower number, meaning code with a priority of 20 will run after code with 10 priority.

The priority argument is set during the [add_action](https://developer.wordpress.org/reference/functions/add_action/) function, after you establish which hook you're connecting to and what the name of your custom function will be.

In the example below, blue text is the name of the hook we're modifying, green text is the name of our custom function, and red is the priority we set.

![Setting priority for the hooked function](https://developer.woocommerce.com/wp-content/uploads/2023/12/priority-markup.png)

## Examples

### Change Return to Shop button redirect URL

In this example, the code is set to redirect the "Return to Shop" button found in the cart to a category that lists products for sale at `http://example.url/category/specials/`.

```php
/**
 * Changes the redirect URL for the Return To Shop button in the cart.
 */
function wc_empty_cart_redirect_url() {
    return 'http://example.url/category/specials/';
}

add_filter( 'woocommerce_return_to_shop_redirect', 'wc_empty_cart_redirect_url', 10 );
```

There, we can see the priority is set to 10. This is the typical default for WooCommerce functions and scripts, so that may not be sufficient to override that button's functionality.

Instead, we can change the priority to any number greater than 10. While 11 would work, best practice dictates we use increments of ten, so 20, 30, and so on.

```php
/**
 * Changes the redirect URL for the Return To Shop button in the cart.
 */
function wc_empty_cart_redirect_url() {
    return 'http://example.com/category/specials/';
}

add_filter( 'woocommerce_return_to_shop_redirect', 'wc_empty_cart_redirect_url', 20 );
```

With priority, we can have two functions that are acting on the same hook. Normally this would cause a variety of problems, but since we've established one has a higher priority than the other, our site will only load the appropriate function, and we will be taken to the Specials page as intended with the code below.

```php
/**
 * Changes the redirect URL for the Return To Shop button in the cart.
 * BECAUSE THIS FUNCTION HAS THE PRIORITY OF 20, IT WILL RUN AFTER THE FUNCTION BELOW (HIGHER NUMBERS RUN LATER)
 */
function wc_empty_cart_redirect_url() {
    return 'http://example.com/category/specials/';
}

add_filter( 'woocommerce_return_to_shop_redirect', 'wc_empty_cart_redirect_url', 20 );

/**
 * Changes the redirect URL for the Return To Shop button in the cart.
 * EVEN THOUGH THIS FUNCTION WOULD NORMALLY RUN LATER BECAUSE IT'S CODED AFTERWARDS, THE 10 PRIORITY IS LOWER THAN 20 ABOVE
 */
function wc_empty_cart_redirect_url() {
    return 'http://example.com/shop/';
}

add_filter( 'woocommerce_return_to_shop_redirect', 'wc_empty_cart_redirect_url', 10 );
```

### Adding Custom Shipping And Billing Fields

Adding fields is done in a similar way to overriding fields. For example, let's add a new field to shipping fields - `shipping_phone`:

```php
// Hook in
add_filter( 'woocommerce_checkout_fields' , 'custom_override_checkout_fields' );

// Our hooked in function - $fields is passed via the filter!
function custom_override_checkout_fields( $fields ) {
     $fields['shipping']['shipping_phone'] = array(
        'label'       => __( 'Phone', 'woocommerce' ),
        'placeholder' => _x( 'Phone', 'placeholder', 'woocommerce' ),
        'required'    => false,
        'class'       => array( 'form-row-wide' ),
        'clear'       => true
     );

     return $fields;
}

/**
 * Display field value on the order edit page
 */
add_action( 'woocommerce_admin_order_data_after_shipping_address', 'my_custom_checkout_field_display_admin_order_meta', 10, 1 );

function my_custom_checkout_field_display_admin_order_meta($order){
    echo '<p><strong>'. esc_html__( 'Phone From Checkout Form' ) . ':</strong> ' . esc_html( $order->get_meta( '_shipping_phone', true ) ) . '</p>';
}
```

![adding custom sthipping and billing fields](https://developer.woocommerce.com/wp-content/uploads/2023/12/Webp-to-PNG-Shipping-Field-Hook.png)

It's alive!

What do we do with the new field? Nothing. Because we defined the field in the `checkout_fields` array, the field is automatically processed and saved to the order post meta (in this case, \_shipping_phone). If you want to add validation rules, see the checkout class where there are additional hooks you can use.

### Adding a Custom Special Field

To add a custom field is similar. Let's add a new field to checkout, after the order notes, by hooking into the following:

```php
/**
 * Add the field to the checkout
 */
add_action( 'woocommerce_after_order_notes', 'my_custom_checkout_field' );

function my_custom_checkout_field( $checkout ) {

    echo '<div id="my_custom_checkout_field"><h2>' . esc_html__( 'My Field' ) . '</h2>';

    woocommerce_form_field(
        'my_field_name',
        array(
            'type'        => 'text',
            'class'       => array( 'my-field-class form-row-wide' ),
            'label'       => __( 'Fill in this field' ),
            'placeholder' => __( 'Enter something' ),
        ),
        $checkout->get_value( 'my_field_name' )
    );

    echo '</div>';

}
```

This gives us:

![WooCommerce Codex - Checkout Field Hook](https://developer.woocommerce.com/wp-content/uploads/2023/12/WooCommerce-Codex-Checkout-Field-Hook.png)

Next we need to validate the field when the checkout form is posted. For this example the field is required and not optional:

```php
/**
 * Process the checkout
 */
add_action( 'woocommerce_checkout_process', 'my_custom_checkout_field_process' );

function my_custom_checkout_field_process() {
    // Check if set, if its not set add an error.
    if ( ! $_POST['my_field_name'] ) {
        wc_add_notice( esc_html__( 'Please enter something into this new shiny field.' ), 'error' );
    }
}
```

A checkout error is displayed if the field is blank:

![WooCommerce Codex - Checkout Field Notice](https://developer.woocommerce.com/wp-content/uploads/2023/12/WooCommerce-Codex-Checkout-Field-Notice.png)

Finally, let's save the new field to order custom fields using the following code:

```php
/**
 * Update the order meta with field value
 */
add_action( 'woocommerce_checkout_update_order_meta', 'my_custom_checkout_field_update_order_meta' );

function my_custom_checkout_field_update_order_meta( $order_id ) {
    if ( ! empty( $_POST['my_field_name'] ) ) {
        $order = wc_get_order( $order_id );
        $order->update_meta_data( 'My Field', sanitize_text_field( $_POST['my_field_name'] ) );
        $order->save_meta_data();
    }
}
```

The field is now saved to the order.

If you wish to display the custom field value on the admin order edition page, you can add this code:

```php
/**
 * Display field value on the order edit page
 */
add_action( 'woocommerce_admin_order_data_after_billing_address', 'my_custom_checkout_field_display_admin_order_meta', 10, 1 );

function my_custom_checkout_field_display_admin_order_meta( $order ){
    echo '<p><strong>' . esc_html__( 'My Field' ) . ':</strong> ' . esc_html( $order->get_meta( 'My Field', true ) ) . '</p>';
}
```

This is the result:

![checkout_field_custom_field_admin](https://developer.woocommerce.com/wp-content/uploads/2023/12/checkout_field_custom_field_admin.png)

### Make phone number not required

```php
add_filter( 'woocommerce_billing_fields', 'wc_npr_filter_phone', 10, 1 );

function wc_npr_filter_phone( $address_fields ) {
	$address_fields['billing_phone']['required'] = false;
	return $address_fields;
}
```
