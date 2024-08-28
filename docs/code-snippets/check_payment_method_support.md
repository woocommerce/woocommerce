---
post_title: Check if a Payment Method Support Refunds, Subscriptions or Pre-orders
menu_title: Payment method support  for refunds, subscriptions, pre-orders
tags: payment-methods
current wccom url: https://woocommerce.com/document/check-if-payment-gateway-supports-refunds-subscriptions-preorders/
---

If a payment method's documentation doesn’t clearly outline the supported features, you can often find what features are supported by looking at payment methods code.

Payment methods can add support for certain features from WooCommerce and its extensions. For example, a payment method can support refunds, subscriptions or pre-orders functionality.

## Simplify Commerce example

Taking the Simplify Commerce payment method as an example, open the plugin files in your favorite editor and search for `$this->supports`. You'll find the supported features:

```php
class WC_Gateway_Simplify_Commerce extends WC_Payment_Gateway {    

/**      * Constructor   */
    public function __construct() {
        $this->id
                 = 'simplify_commerce';
        $this->method_title
       = __( 'Simplify Commerce', 'woocommerce' );
        $this->method_description = __( 'Take payments via Simplify Commerce - uses simplify.js to create card tokens and the Simplify Commerce SDK. Requires SSL when sandbox is disabled.', 'woocommerce' );
        $this->has_fields         = true;
        $this->supports           = array(
            'subscriptions',
            'products',
            'subscription_cancellation',
            'subscription_reactivation',
            'subscription_suspension',
            'subscription_amount_changes',
            'subscription_payment_method_change',
            'subscription_date_changes',
            'default_credit_card_form',
            'refunds',
            'pre-orders'
        );    
```

If you don't find `$this->supports` in the plugin files, that may mean that the payment method isn't correctly declaring support for refunds, subscripts or pre-orders.
