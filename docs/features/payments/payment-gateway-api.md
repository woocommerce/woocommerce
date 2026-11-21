---
post_title: WooCommerce Payment Gateway API
sidebar_label: Payment Gateway API
sidebar_position: 1
---

# WooCommerce Payment Gateway API

Payment gateways in WooCommerce are class based and can be added through traditional plugins. This guide provides an intro to gateway development.

## Types of payment gateway

Payment gateways come in several varieties:

1.  **Form based** - This is where the user must click a button on a form that then redirects them to the payment processor on the gateway's own website. _Example_: PayPal standard, Authorize.net DPM
2.  **iFrame based** - This is when the gateway payment system is loaded inside an iframe on your store. _Example_: SagePay Form, PayPal Advanced
3.  **Direct** - This is when the payment fields are shown directly on the checkout page and the payment is made when 'place order' is pressed. _Example_: PayPal Pro, Authorize.net AIM
4.  **Offline** - No online payment is made. _Example_: Cheque, Bank Transfer

Form and iFrame based gateways post data offsite, meaning there are less security issues for you to think about. Direct gateways, however, require server security to be implemented ([SSL certificates](https://woocommerce.com/document/ssl-and-https/), etc.) and may also require a level of [PCI compliance](https://woocommerce.com/document/pci-dss-compliance-and-woocommerce/).

## Creating a basic payment gateway

**Note:** The instructions below are for the default Checkout page. If you're looking to add a custom payment method for the new Checkout block, check out [the payment method integration documentation](/docs/block-development/extensible-blocks/cart-and-checkout-blocks/checkout-payment-methods/payment-method-integration).

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
    $methods[] = 'WC_Gateway_Your_Gateway';
    return $methods;
}
```

```php
add_filter( 'woocommerce_payment_gateways', 'add_your_gateway_class' );
```

### Required Methods

Most methods are inherited from the WC_Payment_Gateway class, but some are required in your custom gateway.

#### __construct()

Within your constructor, you should define the following variables:

- `$this->id` - Unique ID for your gateway, e.g., 'your_gateway'
- `$this->icon` - If you want to show an image next to the gateway's name on the frontend, enter a URL to an image.
- `$this->has_fields` - Bool. Can be set to true if you want payment fields to show on the checkout (if doing a direct integration).
- `$this->method_title` - Title of the payment method shown on the admin page.
- `$this->method_description` - Description for the payment method shown on the admin page.

Your constructor should also define and load settings fields:

```php
$this->init_form_fields();
$this->init_settings();
```

We'll cover `init_form_fields()` later, but this basically defines your settings that are then loaded with `init_settings()`.

After `init_settings()` is called, you can get the settings and load them into variables, meaning:

```php
$this->title = $this->get_option( 'title' );
```

Finally, you need to add a save hook for your settings:

```php
add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
```

##### Display in the Payments settings page

The Payments settings page uses the gateway's admin-facing details to build the payment provider list. Set these values in your gateway constructor so merchants can identify your extension before they open its individual settings screen.

| Provider detail | Source | Notes |
| ----------------- | -------- | ------- |
| Icon | `$this->icon` | Must be a valid image URL. Use a square image for the best fit. If the value is empty, invalid, or contains an `<img>` tag instead of a URL, WooCommerce shows the default payments icon. |
| Title | `PaymentsProviders\PaymentGateway::get_title()` | Uses `WC_Payment_Gateway::get_method_title()` as a `$this->method_title` accessor. If it is empty, the provider falls back to the gateway's customer-facing `WC_Payment_Gateway::get_title()`, strips HTML, and truncates the display value to 75 characters. |
| Description | `PaymentsProviders\PaymentGateway::get_description()` | Uses `WC_Payment_Gateway::get_method_description()` as a `$this->method_description` accessor. If it is empty, the provider falls back to the gateway's customer-facing `WC_Payment_Gateway::get_description()`, strips HTML, and truncates the display value to 130 characters. |

Use `$this->method_title` and `$this->method_description` for concise admin copy. Use `$this->title` and `$this->description` for the customer-facing checkout label and payment instructions, often loaded from merchant settings with `$this->get_option()`.

For example:

```php
public function __construct() {
    $this->id                 = 'your_gateway';
    $this->method_title       = __( 'Your Gateway', 'your-text-domain' );
    $this->method_description = __( 'Accept card payments with Your Gateway.', 'your-text-domain' );
    $this->icon               = plugins_url( 'assets/images/your-gateway-icon.svg', __FILE__ );
    $this->has_fields         = true;

    $this->init_form_fields();
    $this->init_settings();

    $this->title       = $this->get_option( 'title' );
    $this->description = $this->get_option( 'description' );
}
```

#### init_form_fields()

Use this method to set `$this->form_fields` - these are options you'll show in admin on your gateway settings page and make use of the [WC Settings API](https://developer.woocommerce.com/docs/settings-api/).

A basic set of settings for your gateway would consist of _enabled_, _title_ and _description_:

```php
$this->form_fields = array(
    'enabled' => array(
        'title' => __( 'Enable/Disable', 'woocommerce' ),
        'type' => 'checkbox',
        'label' => __( 'Enable Cheque Payment', 'woocommerce' ),
        'default' => 'yes'
    ),
    'title' => array(
        'title' => __( 'Title', 'woocommerce' ),
        'type' => 'text',
        'description' => __( 'This controls the title which the user sees during checkout.', 'woocommerce' ),
        'default' => __( 'Cheque Payment', 'woocommerce' ),
        'desc_tip' => true,
    ),
    'description' => array(
        'title' => __( 'Customer Message', 'woocommerce' ),
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
    $order->update_status(\Automattic\WooCommerce\Enums\OrderStatus::ON_HOLD, __( 'Awaiting cheque payment', 'woocommerce' ));

    // Remove cart
    $woocommerce->cart->empty_cart();

    // Return thankyou redirect
    return array(
        'result' => 'success',
        'redirect' => $this->get_return_url( $order )
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

If payment fails, you should throw an error and return an array with the failure status:

```php
wc_add_notice( __('Payment error:', 'woothemes') . $error_message, 'error' );
return array(
    'result'   => 'failure'
);
```

WooCommerce will catch this error and show it on the checkout page.

Stock levels are updated via actions (`woocommerce_payment_complete` and in transitions between order statuses), so it's no longer needed to manually call the methods reducing stock levels while processing the payment.

### Updating Order Status and Adding Notes

Updating the order status can be done using functions in the order class. You should only do this if the order status is not processing (in which case you should use payment_complete()). An example of updating to a custom status would be:

```php
$order = new WC_Order( $order_id );
$order->update_status(\Automattic\WooCommerce\Enums\OrderStatus::ON_HOLD, __('Awaiting cheque payment', 'woothemes'));
```

The above example updates the status to On-Hold and adds a note informing the owner that it is awaiting a Cheque. You can add notes without updating the order status; this is used for adding a debug message:

```php
$order->add_order_note( __('IPN payment completed', 'woothemes') );
```

### Order Status Best Practice

- If the order has completed but the admin needs to manually verify payment, use **On-Hold**
- If the order fails and has already been created, set to **Failed**
- If payment is complete, let WooCommerce handle the status and use `$order->payment_complete()`. WooCommerce will use either **Completed** or **Processing** status and handle stock. Call it only after the payment provider confirms the payment: see [Retries and uncertain outcomes](#retries-and-uncertain-outcomes).

### Recording test or live mode

Since WooCommerce 11.4, gateways can record whether an order was paid in test or live mode with `$order->set_payment_mode()`. WooCommerce never sets it, so an order whose gateway doesn't call it has no mode. Read it back with `$order->get_payment_mode()`, which returns `PaymentMode::TEST`, `PaymentMode::LIVE` or an empty string.

Call it on every charge, when the gateway picks its test or live credentials and before it saves the order. That includes checkout, the pay-for-order page, subscription renewals, and charges completed by webhooks or other off-session requests. Record `live` too, so each order says which mode it was paid in.

```php
use Automattic\WooCommerce\Enums\PaymentMode;

// Gateways that support WooCommerce versions before 11.4 need this check.
if ( method_exists( $order, 'set_payment_mode' ) ) {
    $order->set_payment_mode( $this->testmode ? PaymentMode::TEST : PaymentMode::LIVE );
}
$order->save();
```

When an order is saved with another payment method ID, WooCommerce clears its mode, so a mode from a failed attempt doesn't carry over to a retry with another gateway. A mode set on the same order object before the switch is kept. If your gateway changes the order's payment method ID in a later request, such as a redirect return or a webhook, call `set_payment_mode()` again in that request before saving.

## Notes on Direct Gateways

If you are creating an advanced, direct gateway (i.e., one that takes payment on the actual checkout page), there are additional steps involved. First, you need to set has_fields to true in the gateway constructor:

```php
$this->has_fields = true;
```

This tells the checkout to output a 'payment_box' containing your direct payment form that you define next.

Create a method called `payment_fields()` - this contains your form, most likely to have credit card details.

The next but optional method to add is `validate_fields()`. Return true if the form passes validation or false if it fails. You can use the `wc_add_notice()` function if you want to add an error and display it to the user.

Finally, you need to add payment code inside your `process_payment( $order_id )` method. This takes the posted form data and attempts payment directly via the payment provider.

If payment fails, you should output an error and return the failure array:

```php
wc_add_notice( __('Payment error:', 'woothemes') . $error_message, 'error' );
return array(
    'result'   => 'failure',
);
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

## Retries and uncertain outcomes

A checkout request can end after the payment provider accepts a payment but before the shopper reaches the order received page, for example on a timeout, a lost connection, or a fatal error in code that runs after the payment. The shopper may then submit checkout again for the same order. Follow these practices so that a retry of the same order does not charge the shopper twice.

### Set the order status to match the payment

WooCommerce reads an order's status to decide whether it still needs payment. Orders in a payable status, **Pending payment** or **Failed** by default, can be paid again when their total is above zero. From WooCommerce 11.4, once your gateway moves an order out of those statuses, for example with `$order->payment_complete()` or by setting it to **On-Hold**, checkout treats the payment step as done. If checkout fails after that point, or the shopper submits the same cart again, WooCommerce sends the shopper to the order received page of the existing order instead of asking them to pay again. The exceptions are draft, **Cancelled**, **Refunded** and trashed orders, for which checkout does not treat the payment step as done. On earlier versions, a repeat submit creates a new order and calls `process_payment()` again.

While the shopper may still need to pay, for example after a lost response or a status lookup that could not finish, leave the order **Pending payment**. When stock management is on, WooCommerce cancels unpaid orders after the hold stock time, 60 minutes by default, so use **Pending payment** for short waits only. If the provider accepted the payment and will confirm it later, set the order to **On-Hold** and send the shopper to the order received page. If the payment fails, leave the order **Pending payment** or set it to **Failed**, so the shopper can pay again.

Avoid holding an order in a custom status while the outcome is open. The `woocommerce_valid_order_statuses_for_payment` filter can make a custom status payable, but when the shopper submits checkout again, only the block checkout stays on the same order, setting it back to **Pending payment**. The classic checkout creates a new order, which does not carry the transaction ID saved on the earlier one.

### Make process_payment() safe to repeat

Within the shopper's checkout session, when an order with a total above zero is **Pending payment** or **Failed** and the cart has not changed, WooCommerce calls `process_payment()` for the same order each time the shopper submits checkout.

If an earlier request reached the provider but its response never came back, a new charge request can take a second payment. Use the provider's idempotency support, such as an idempotency key or one payment object reused for the order, so that a repeated request returns the first result instead of creating a new charge. Keep the key the same for every retry of an order until the provider confirms that the attempt failed, even if it returned no transaction ID. Remove any failed transaction ID at the same time, otherwise the next retry reads the old failed ID instead of checking the new attempt.

### Store the transaction ID and check it before charging again

Save the provider's transaction or payment intent ID on the order as soon as the provider returns it, before you wait for the outcome. On the next attempt, ask the provider for the status of that transaction, and create a new charge only if it confirms that the earlier one failed or never happened. In this sketch, `get_provider_status()` stands for your provider's status lookup. It returns `paid`, `pending` for a payment the provider accepted and will confirm later, `failed` for a payment that failed or never happened, or `unknown` for anything else, such as a lookup that could not finish:

```php
public function process_payment( $order_id ) {
    $order          = wc_get_order( $order_id );
    $transaction_id = $order->get_meta( '_your_gateway_transaction_id' );

    if ( $transaction_id ) {
        $status = $this->get_provider_status( $transaction_id );

        // An earlier attempt took the payment before its request ended.
        if ( 'paid' === $status ) {
            // Check the amount paid first, as described below this sketch.
            $order->payment_complete( $transaction_id );

            return array(
                'result'   => 'success',
                'redirect' => $this->get_return_url( $order ),
            );
        }

        // The provider accepted the payment and will confirm it later.
        if ( 'pending' === $status ) {
            $order->update_status( \Automattic\WooCommerce\Enums\OrderStatus::ON_HOLD, __( 'Awaiting payment confirmation', 'your-text-domain' ) );

            return array(
                'result'   => 'success',
                'redirect' => $this->get_return_url( $order ),
            );
        }

        // The outcome is still open, so a new charge could take a second payment.
        if ( 'failed' !== $status ) {
            wc_add_notice( __( 'We could not confirm your payment yet. Try again in a few minutes.', 'your-text-domain' ), 'error' );

            return array( 'result' => 'failure' );
        }

        // The earlier payment failed: remove its transaction ID and change the idempotency key.
    }

    // Create the payment with the order's idempotency key, then save its ID on the order.
}
```

Before you call `$order->payment_complete()`, check that the amount paid matches the order total, because a merchant can change the total of an unpaid order. If they differ, do not complete the order or create a new charge: set it to **On-Hold** so the merchant can review it.

The saved ID check reads the order, so it works only when the retry reaches the same order. WooCommerce creates a new order, without the saved ID, when the cart or its total changed, when the order was cancelled, or when the session ended. A shopper who switches to another gateway also skips the check.

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
