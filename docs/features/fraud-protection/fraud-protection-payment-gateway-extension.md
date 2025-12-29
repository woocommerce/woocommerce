# Fraud protection payment gateway extension points

## Overview

The WooCommerce fraud protection system provides extension points that allow payment gateways to add custom payment data (card BIN, last 4 digits, card brand, tokens, and other metadata) to fraud protection analysis.

To enable fraud protection head to /wp-admin > WooCommerce > Settings > Advanced > Features > Fraud protection.

⚠️ Fraud protection requires a Jetpack connection to work.

## Available filters

### 1. `woocommerce_fraud_protection_payment_data`

A general-purpose filter that applies to all payment gateways, allowing them to populate payment-specific data fields.

**Parameters:**
- `$payment_data` (array): Payment data array with fields to populate
- `$chosen_payment_method` (string|null): The chosen payment method ID, or null if not available
- `$data_collector` (SessionDataCollector): The SessionDataCollector instance for additional context

**Returns:** (array) Modified payment data array

**Available fields:**
- `payment_gateway_name` (string|null): Name of the payment gateway
- `payment_method_type` (string|null): Type of payment method (e.g., 'card', 'bank_transfer', 'digital_wallet')
- `card_bin` (string|null): Bank Identification Number (first 6-8 digits of card)
- `card_last4` (string|null): Last 4 digits of the card number
- `card_brand` (string|null): Card brand (e.g., 'visa', 'mastercard', 'amex')
- `payer_id` (string|null): Payer identifier (e.g., PayPal payer ID)
- `outcome` (string|null): Payment outcome status (e.g., 'authorized', 'declined', 'pending')
- `decline_reason` (string|null): Reason for payment decline, if applicable
- `avs_result` (string|null): Address Verification System result code
- `cvc_result` (string|null): Card Verification Code check result
- `tokenized_card_identifier` (string|null): Token identifier for saved payment methods

**Example usage:**

```php
add_filter( 'woocommerce_fraud_protection_payment_data', 'my_gateway_add_payment_data', 10, 3 );

function my_gateway_add_payment_data( $payment_data, $chosen_payment_method, $data_collector ) {
    // Only modify data for our gateway.
    if ( 'my_payment_gateway' !== $chosen_payment_method ) {
        return $payment_data;
    }

    // Get payment details from your gateway's session or API.
    $card_details = my_gateway_get_card_details();

    // Populate payment fields.
    if ( $card_details ) {
        $payment_data['payment_gateway_name']  = 'My Payment Gateway';
        $payment_data['payment_method_type']   = 'card';
        $payment_data['card_bin']              = $card_details['bin'];
        $payment_data['card_last4']            = $card_details['last4'];
        $payment_data['card_brand']            = $card_details['brand'];
        $payment_data['tokenized_card_identifier'] = $card_details['token_id'] ?? null;
    }

    return $payment_data;
}
```

### 2. `woocommerce_fraud_protection_payment_data_{gateway_id}`

A gateway-specific filter that only applies to a specific payment gateway. This allows gateways to hook directly into their own filter without checking the payment method ID in the callback.

**Dynamic hook name:** The `{gateway_id}` portion is replaced with the actual payment gateway ID (e.g., `stripe`, `paypal`, `square`).

**Parameters:**
- `$payment_data` (array): Payment data array with fields to populate
- `$data_collector` (SessionDataCollector): The SessionDataCollector instance for additional context

**Returns:** (array) Modified payment data array

**Example usage for Stripe:**

```php
add_filter( 'woocommerce_fraud_protection_payment_data_stripe', 'stripe_add_fraud_protection_data', 10, 2 );

function stripe_add_fraud_protection_data( $payment_data, $data_collector ) {
    // Get Stripe payment intent or charge data.
    $stripe_data = get_stripe_payment_data();

    if ( $stripe_data ) {
        $payment_data['payment_gateway_name']  = 'Stripe';
        $payment_data['payment_method_type']   = 'card';
        $payment_data['card_bin']              = $stripe_data['card']['bin'] ?? null;
        $payment_data['card_last4']            = $stripe_data['card']['last4'] ?? null;
        $payment_data['card_brand']            = $stripe_data['card']['brand'] ?? null;
        $payment_data['avs_result']            = $stripe_data['avs_check'] ?? null;
        $payment_data['cvc_result']            = $stripe_data['cvc_check'] ?? null;
        $payment_data['outcome']               = $stripe_data['outcome']['type'] ?? null;
        $payment_data['decline_reason']        = $stripe_data['outcome']['reason'] ?? null;
    }

    return $payment_data;
}
```

**Example usage for PayPal:**

```php
add_filter( 'woocommerce_fraud_protection_payment_data_paypal', 'paypal_add_fraud_protection_data', 10, 2 );

function paypal_add_fraud_protection_data( $payment_data, $data_collector ) {
    // Get PayPal transaction data.
    $paypal_data = get_paypal_transaction_data();

    if ( $paypal_data ) {
        $payment_data['payment_gateway_name']  = 'PayPal';
        $payment_data['payment_method_type']   = 'digital_wallet';
        $payment_data['payer_id']              = $paypal_data['payer_id'] ?? null;
        $payment_data['outcome']               = $paypal_data['status'] ?? null;
    }

    return $payment_data;
}
```

## Best practices

### 1. Data privacy and security

- **Never include full card numbers** - only BIN (first 6-8 digits) and last 4 digits
- **Sanitize all data** before adding it to the payment data array
- **Follow PCI compliance** requirements when handling card data
- **Use tokenized identifiers** instead of raw payment method data when possible

### 2. Performance considerations

- **Cache data when possible** - avoid making external API calls during every collection
- **Return quickly** - fraud protection data collection should not slow down checkout
- **Handle errors gracefully** - return the payment data array even if your gateway data is unavailable

### 3. Data accuracy

- **Populate only available fields** - leave fields as null if data is not available
- **Use consistent values** - follow standard conventions for field values (e.g., lowercase card brands: 'visa', 'mastercard')
- **Update in real-time** - ensure data reflects the current state of the payment

### 4. Testing

- **Test with different payment states** - authorized, declined, pending, etc.
- **Test with missing data** - ensure your filter handles cases where payment data is not available
- **Test filter priority** - if using both filters, ensure they work together correctly

## Integration examples

### Full example: integrating a custom gateway

```php
/**
 * Add fraud protection data for My Custom Gateway.
 */
class My_Custom_Gateway_Fraud_Protection {

    /**
     * Initialize fraud protection integration.
     */
    public function init() {
        add_filter(
            'woocommerce_fraud_protection_payment_data_my_custom_gateway',
            array( $this, 'add_payment_data' ),
            10,
            2
        );
    }

    /**
     * Add payment data to fraud protection.
     *
     * @param array                  $payment_data    Payment data array.
     * @param SessionDataCollector   $data_collector  Data collector instance.
     * @return array Modified payment data.
     */
    public function add_payment_data( $payment_data, $data_collector ) {
        try {
            // Get payment details from gateway session or API.
            $gateway_data = $this->get_gateway_payment_data();

            if ( ! $gateway_data ) {
                return $payment_data;
            }

            // Populate basic payment info.
            $payment_data['payment_gateway_name'] = 'My Custom Gateway';
            $payment_data['payment_method_type']  = $gateway_data['type'];

            // Populate card-specific fields if available.
            if ( 'card' === $gateway_data['type'] && isset( $gateway_data['card'] ) ) {
                $payment_data['card_bin']    = $this->sanitize_bin( $gateway_data['card']['bin'] );
                $payment_data['card_last4']  = $this->sanitize_last4( $gateway_data['card']['last4'] );
                $payment_data['card_brand']  = $this->normalize_card_brand( $gateway_data['card']['brand'] );
                $payment_data['avs_result']  = $gateway_data['card']['avs_result'] ?? null;
                $payment_data['cvc_result']  = $gateway_data['card']['cvc_result'] ?? null;
            }

            // Populate tokenization data if available.
            if ( ! empty( $gateway_data['token_id'] ) ) {
                $payment_data['tokenized_card_identifier'] = $gateway_data['token_id'];
            }

            // Populate transaction outcome.
            $payment_data['outcome']        = $gateway_data['status'];
            $payment_data['decline_reason'] = $gateway_data['decline_reason'] ?? null;

        } catch ( Exception $e ) {
            // Log error but don't fail - return payment data as-is.
            error_log( 'Error adding fraud protection payment data: ' . $e->getMessage() );
        }

        return $payment_data;
    }

    /**
     * Get payment data from gateway.
     *
     * @return array|null Gateway payment data or null if not available.
     */
    private function get_gateway_payment_data() {
        // Implement your gateway-specific logic to retrieve payment data.
        // This could come from session, database, or an API call.
        return null;
    }

    /**
     * Sanitize BIN (Bank Identification Number).
     *
     * @param string $bin Raw BIN value.
     * @return string|null Sanitized BIN (6-8 digits) or null.
     */
    private function sanitize_bin( $bin ) {
        $bin = preg_replace( '/[^0-9]/', '', $bin );
        return ( strlen( $bin ) >= 6 && strlen( $bin ) <= 8 ) ? $bin : null;
    }

    /**
     * Sanitize last 4 digits of card.
     *
     * @param string $last4 Raw last 4 value.
     * @return string|null Sanitized last 4 digits or null.
     */
    private function sanitize_last4( $last4 ) {
        $last4 = preg_replace( '/[^0-9]/', '', $last4 );
        return ( strlen( $last4 ) === 4 ) ? $last4 : null;
    }

    /**
     * Normalize card brand to standard format.
     *
     * @param string $brand Raw brand value.
     * @return string Normalized brand in lowercase.
     */
    private function normalize_card_brand( $brand ) {
        $brand = strtolower( trim( $brand ) );

        // Map common variations to standard names.
        $brand_map = array(
            'american express' => 'amex',
            'americanexpress'  => 'amex',
            'diners club'      => 'diners',
        );

        return $brand_map[ $brand ] ?? $brand;
    }
}

// Initialize the integration.
$my_gateway_fraud_protection = new My_Custom_Gateway_Fraud_Protection();
$my_gateway_fraud_protection->init();
```

## Troubleshooting

### Data not appearing in fraud protection

1. **Check filter priority** - ensure your filter is added before fraud protection data is collected
2. **Verify payment method ID** - confirm the `$chosen_payment_method` matches your gateway ID
3. **Check return value** - ensure you're returning the modified `$payment_data` array
4. **Test with logging** - add error_log() statements to verify your filter is being called

### Performance issues

1. **Avoid API calls in filter** - cache payment data or retrieve it before the filter is called
2. **Use gateway-specific filter** - the `woocommerce_fraud_protection_payment_data_{gateway_id}` filter is more efficient
3. **Profile your code** - use WordPress debugging tools to identify bottlenecks

### Data validation errors

1. **Sanitize all inputs** - use appropriate WordPress sanitization functions
2. **Validate data types** - ensure string fields contain strings, not arrays or objects
3. **Handle missing data** - return null for unavailable fields rather than empty strings

## Frequently asked questions

**Q: When should I use the general filter vs. the gateway-specific filter?**

A: Use the gateway-specific filter (`woocommerce_fraud_protection_payment_data_{gateway_id}`) when you're only modifying data for a single payment gateway. Use the general filter when you need to modify data for multiple gateways or need access to the `$chosen_payment_method` parameter.

**Q: Can I add custom fields beyond the documented fields?**

A: Yes, you can add custom fields to the payment data array. However, only the documented fields are officially supported by the fraud protection system. Custom fields may be ignored or cause issues if they conflict with future field additions.

**Q: How do I test my integration?**

A: Create unit tests that mock the payment gateway data and verify the filter correctly populates the payment data array. See the test examples in the WooCommerce test suite.

**Q: What happens if my filter throws an exception?**

A: The fraud protection system will catch the exception and continue with the default payment data (all null values). However, it's best practice to handle exceptions within your filter and log errors appropriately.

## Related resources

- [SessionDataCollector Class Documentation](../src/Internal/FraudProtection/SessionDataCollector.php)
- [WooCommerce Payment Gateway API](https://woocommerce.com/document/payment-gateway-api/)
- [PCI DSS Compliance Guide](https://www.pcisecuritystandards.org/)

## Changelog

- **10.5.0** - Introduced `woocommerce_fraud_protection_payment_data` and `woocommerce_fraud_protection_payment_data_{gateway_id}` filters
