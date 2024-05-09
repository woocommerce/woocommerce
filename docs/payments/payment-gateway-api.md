---
post_title: WooCommerce Payment Gateway API
menu_title: Payment Gateway API
tags: reference
---

Payment gateways in WooCommerce are class based and can be added through traditional plugins. This guide provides an intro to gateway development.

## Types of payment gateway

Payment gateways come in several varieties:

1.  **Form based** - This is where the user must click a button on a form that then redirects them to the payment processor on the gateway's own website. _Example_: PayPal standard, Authorize.net DPM
2.  **iFrame based** - This is when the gateway payment system is loaded inside an iframe on your store. _Example_: SagePay Form, PayPal Advanced
3.  **Direct** - This is when the payment fields are shown directly on the checkout page and the payment is made when 'place order' is pressed. _Example_: PayPal Pro, Authorize.net AIM
4.  **Offline** - No online payment is made. _Example_: Cheque, Bank Transfer

Form and iFrame based gateways post data offsite, meaning there are less security issues for you to think about. Direct gateways, however, require server security to be implemented ([SSL certificates](https://woocommerce.com/document/ssl-and-https/), etc.) and may also require a level of [PCI compliance](https://woocommerce.com/document/pci-dss-compliance-and-woocommerce/).

## Creating a basic payment gateway

**Note:** The instructions below are for the default Checkout page. If you're looking to add a custom payment method for the new Checkout block, check out [this documentation.](https://github.com/woocommerce/woocommerce-blocks/blob/trunk/docs/third-party-developers/extensibility/checkout-payment-methods/payment-method-integration.md)

Payment gateways should be created as additional plugins that hook into WooCommerce. Inside the plugin, you need to create a class after plugins are loaded. Example:

```php
add_action( 'plugins_loaded', 'init_your_gateway_class' );
```

It is also important that your gateway class extends the WooCommerce base gateway class, so you have access to important methods and the [settings API](https://developer.woocommerce.com/docs/settings-api/):

```php
function init_your_gateway_class() {
class WC_Gateway_Your_Gateway extends WC_Payment_Gateway {}
}
```

You can view the [WC_Payment_Gateway class in the API Docs](https://woocommerce.github.io/code-reference/classes/WC-Payment-Gateway.html).

As well as defining your class, you need to also tell WooCommerce (WC) that it exists. Do this by filtering _woocommerce_payment_gateways_:

```php
function add_your_gateway_class( $methods ) {
$methods\[\] = 'WC_Gateway_Your_Gateway';
return $methods;
}
```

```php
add_filter( 'woocommerce_payment_gateways', 'add_your_gateway_class' );
```

### Required Methods

Most methods are inherited from the WC_Payment_Gateway class, but some are required in your custom gateway.

#### \_\_construct()

Within your constructor, you should define the following variables:

- `$this->id` - Unique ID for your gateway, e.g., 'your_gateway'
- `$this->icon` - If you want to show an image next to the gateway's name on the frontend, enter a URL to an image.
- `$this->has_fields` - Bool. Can be set to true if you want payment fields to show on the checkout (if doing a direct integration).
- `$this->method_title` - Title of the payment method shown on the admin page.
- `$this->method_description` - Description for the payment method shown on the admin page.

Your constructor should also define and load settings fields:

```php
$this->init\_form\_fields();
$this->init_settings();
```

We'll cover `init_form_fields()` later, but this basically defines your settings that are then loaded with `init_settings()`.

After `init_settings()` is called, you can get the settings and load them into variables, meaning:

```php
$this->title = $this->get_option( 'title' );
```

Finally, you need to add a save hook for your settings:

```php
add_action( 'woocommerce_update_options_payment_gateways\_' . $this->id, array( $this, 'process_admin_options' ) );
```

#### init_form_fields()

Use this method to set `$this->form_fields` - these are options you'll show in admin on your gateway settings page and make use of the [WC Settings API](https://developer.woocommerce.com/docs/settings-api/).

A basic set of settings for your gateway would consist of _enabled_, _title_ and _description_:

```php
$this->form_fields = array(
'enabled' => array(
'title' => \_\_( 'Enable/Disable', 'woocommerce' ),
'type' => 'checkbox',
'label' => \_\_( 'Enable Cheque Payment', 'woocommerce' ),
'default' => 'yes'
),
'title' => array(
'title' => \_\_( 'Title', 'woocommerce' ),
'type' => 'text',
'description' => \_\_( 'This controls the title which the user sees during checkout.', 'woocommerce' ),
'default' => \_\_( 'Cheque Payment', 'woocommerce' ),
'desc_tip' => true,
),
'description' => array(
'title' => \_\_( 'Customer Message', 'woocommerce' ),
'type' => 'textarea',
'default' => ''
)
);
```

#### process_payment( $order_id )

Now for the most important part of the gateway - handling payment and processing the order. Process_payment also tells WC where to redirect the user, and this is done with a returned array.

Here is an example of a process_payment function from the Cheque gateway:

```php
function process_payment( $order_id ) {
global $woocommerce;
$order = new WC_Order( $order_id );

    // Mark as on-hold (we're awaiting the cheque)
    $order->update\_status('on-hold', \_\_( 'Awaiting cheque payment', 'woocommerce' ));

    // Remove cart
    $woocommerce->cart->empty\_cart();

    // Return thankyou redirect
    return array(
        'result' => 'success',
        'redirect' => $this->get\_return\_url( $order )
    );

}
```

As you can see, its job is to:

- Get and update the order being processed
- Return success and redirect URL (in this case the thanks page)

Cheque gives the order On-Hold status since the payment cannot be verified automatically. If, however, you are building a direct gateway, then you can complete the order here instead. Rather than using update_status when an order is paid, you should use payment_complete:

```php
$order->payment_complete();
```

This ensures stock reductions are made, and the status is changed to the correct value.

If payment fails, you should throw an error and return null:

```php
wc_add_notice( \_\_('Payment error:', 'woothemes') . $error_message, 'error' );
return;
```

WooCommerce will catch this error and show it on the checkout page.

Stock levels are updated via actions (`woocommerce_payment_complete` and in transitions between order statuses), so it's no longer needed to manually call the methods reducing stock levels while processing the payment.

### Updating Order Status and Adding Notes

Updating the order status can be done using functions in the order class. You should only do this if the order status is not processing (in which case you should use payment_complete()). An example of updating to a custom status would be:

```php
$order = new WC\_Order( $order\_id );
$order->update_status('on-hold', \_\_('Awaiting cheque payment', 'woothemes'));
```

The above example updates the status to On-Hold and adds a note informing the owner that it is awaiting a Cheque. You can add notes without updating the order status; this is used for adding a debug message:

```php
$order->add_order_note( \_\_('IPN payment completed', 'woothemes') );
```

### Order Status Best Practice

- If the order has completed but the admin needs to manually verify payment, use **On-Hold**
- If the order fails and has already been created, set to **Failed**
- If payment is complete, let WooCommerce handle the status and use `$order->payment_complete()`. WooCommerce will use either **Completed** or **Processing** status and handle stock.

## Notes on Direct Gateways

If you are creating an advanced, direct gateway (i.e., one that takes payment on the actual checkout page), there are additional steps involved. First, you need to set has_fields to true in the gateway constructor:

```php
$this->has_fields = true;
```

This tells the checkout to output a 'payment_box' containing your direct payment form that you define next.

Create a method called `payment_fields()` - this contains your form, most likely to have credit card details.

The next but optional method to add is `validate_fields()`. Return true if the form passes validation or false if it fails. You can use the `wc_add_notice()` function if you want to add an error and display it to the user.

Finally, you need to add payment code inside your `process_payment( $order_id )` method. This takes the posted form data and attempts payment directly via the payment provider.

If payment fails, you should output an error and return nothing:

```php
wc_add_notice( \_\_('Payment error:', 'woothemes') . $error_message, 'error' );
return;
```

If payment is successful, you should set the order as paid and return the success array:

```php
// Payment complete
$order->payment_complete();
```

```php
// Return thank you page redirect
return array(
'result' => 'success',
'redirect' => $this->get_return_url( $order )
);
```

## Working with Payment Gateway Callbacks (such as PayPal IPN)

If you are building a gateway that makes a callback to your store to tell you about the status of an order, you need to add code to handle this inside your gateway.

The best way to add a callback and callback handler is to use WC-API hooks. An example would be as PayPal Standard does. It sets the callback/IPN URL as:

```php
str_replace( 'https:', 'http:', add_query_arg( 'wc-api', 'WC_Gateway_Paypal', home_url( '/' ) ) );
```

Then hooks in its handler to the hook:

```php
add_action( 'woocommerce_api_wc_gateway_paypal', array( $this, 'check_ipn_response' ) );
```

WooCommerce will call your gateway and run the action when the URL is called.

For more information, see [WC_API - The WooCommerce API Callback](https://woocommerce.com/document/wc_api-the-woocommerce-api-callback/).

## Hooks in Gateways

It's important to note that adding hooks inside gateway classes may not trigger. Gateways are only loaded when needed, such as during checkout and on the settings page in admin.

You should keep hooks outside of the class or use WC-API if you need to hook into WordPress events from your class.
