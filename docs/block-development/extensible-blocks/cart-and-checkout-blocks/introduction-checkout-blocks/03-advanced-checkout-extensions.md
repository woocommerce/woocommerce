# Advanced Checkout Extensions

**Prerequisite:** Complete [Your First Checkout Extension](./02-your-first-checkout-extension.md) before starting this guide.

## What You'll Learn

This guide covers advanced patterns for extending checkout blocks:

- Working with select and checkbox field types
- Conditional field display based on other values
- Cross-field validation
- Setting dynamic default values
- Integrating with external APIs
- Performance optimization
- Introduction to JavaScript extensions (Slot Fills and Filters)

## Pattern 1: Select and Checkbox Fields

### Select Dropdown Field

Create a field that offers customers multiple choices:

```php
<?php
add_action( 'woocommerce_init', function() {
    woocommerce_register_additional_checkout_field(
        array(
            'id'       => 'myshop/delivery-preference',
            'label'    => __( 'Delivery Preference', 'myshop' ),
            'location' => 'order',
            'type'     => 'select',
            'required' => false,
            'options'  => array(
                array(
                    'value' => 'standard',
                    'label' => __( 'Standard Delivery', 'myshop' ),
                ),
                array(
                    'value' => 'contactless',
                    'label' => __( 'Contactless Delivery', 'myshop' ),
                ),
                array(
                    'value' => 'signature',
                    'label' => __( 'Signature Required', 'myshop' ),
                ),
            ),
        )
    );
} );
```

**Key points:**
- `type` is `'select'`
- `options` is an array of arrays, each with `value` and `label`
- First option is automatically selected by default

### Checkbox Field

```php
<?php
add_action( 'woocommerce_init', function() {
    woocommerce_register_additional_checkout_field(
        array(
            'id'       => 'myshop/subscribe-newsletter',
            'label'    => __( 'Subscribe to our newsletter for exclusive offers', 'myshop' ),
            'location' => 'contact',
            'type'     => 'checkbox',
            'required' => false,
        )
    );
} );
```

**Accessing checkbox values:**

```php
$is_subscribed = $checkout_fields->get_field_from_object(
    'myshop/subscribe-newsletter',
    $order,
    'other'
);

// Value is '1' if checked, empty string if unchecked
if ( $is_subscribed === '1' ) {
    // Subscribe customer to newsletter
}
```

## Pattern 2: Conditional Field Display

Show or hide fields based on other checkout values.

### Use Case: Business Fields for Company Customers

Show business-related fields only when customer selects "Company" as their customer type.

```php
<?php
/**
 * Register customer type selector
 */
add_action( 'woocommerce_init', function() {
    // Customer type selector
    woocommerce_register_additional_checkout_field(
        array(
            'id'       => 'myshop/customer-type',
            'label'    => __( 'Customer Type', 'myshop' ),
            'location' => 'contact',
            'type'     => 'select',
            'required' => true,
            'options'  => array(
                array(
                    'value' => 'individual',
                    'label' => __( 'Individual', 'myshop' ),
                ),
                array(
                    'value' => 'company',
                    'label' => __( 'Company', 'myshop' ),
                ),
            ),
        )
    );

    // Company name (conditionally shown)
    woocommerce_register_additional_checkout_field(
        array(
            'id'       => 'myshop/company-name',
            'label'    => __( 'Company Name', 'myshop' ),
            'location' => 'address',
            'type'     => 'text',
            'required' => false,  // Conditionally required via validation
        )
    );

    // VAT number (conditionally shown)
    woocommerce_register_additional_checkout_field(
        array(
            'id'       => 'myshop/vat-number',
            'label'    => __( 'VAT Number', 'myshop' ),
            'location' => 'address',
            'type'     => 'text',
            'required' => false,
        )
    );
} );
```

### Conditional Validation

Make fields required only when relevant:

```php
<?php
/**
 * Make company fields required for company customers
 */
add_filter( 'woocommerce_blocks_validate_location_address_fields', function( $errors, $fields, $location ) {
    $customer_type = $fields['myshop/customer-type'] ?? '';

    // If customer type is company, validate business fields
    if ( $customer_type === 'company' ) {
        $company_name = $fields['myshop/company-name'] ?? '';
        $vat_number   = $fields['myshop/vat-number'] ?? '';

        if ( empty( $company_name ) ) {
            $errors->add(
                'missing_company_name',
                __( 'Company name is required for business customers', 'myshop' )
            );
        }

        if ( empty( $vat_number ) ) {
            $errors->add(
                'missing_vat',
                __( 'VAT number is required for business customers', 'myshop' )
            );
        } elseif ( strlen( $vat_number ) !== 11 || ! ctype_alpha( substr( $vat_number, 0, 2 ) ) || ! ctype_digit( substr( $vat_number, 2 ) ) ) {
            $errors->add(
                'invalid_vat_format',
                __( 'VAT number format is invalid (should be: XX123456789)', 'myshop' )
            );
        }
    }

    return $errors;
}, 10, 3 );
```

**Why use `woocommerce_blocks_validate_location_address_fields`?**

This hook receives ALL address fields at once, allowing cross-field validation. Available location-specific hooks:
- `woocommerce_blocks_validate_location_contact_fields`
- `woocommerce_blocks_validate_location_address_fields`
- `woocommerce_blocks_validate_location_order_fields`

### JavaScript Conditional Display

To actually hide/show fields in real-time, you need JavaScript:

```php
<?php
/**
 * Enqueue script for conditional field visibility
 */
add_action( 'wp_enqueue_scripts', function() {
    if ( is_checkout() ) {
        wp_add_inline_script(
            'wc-blocks-checkout',
            "
            (function() {
                const { useEffect } = window.wp.element;
                const { useSelect } = window.wp.data;
                const { registerPlugin } = window.wp.plugins;

                const ConditionalFieldsController = () => {
                    const customerType = useSelect((select) => {
                        const store = select('wc/store/checkout');
                        const fields = store.getAdditionalFields();
                        return fields?.['myshop/customer-type'] || 'individual';
                    });

                    useEffect(() => {
                        const companyFields = [
                            'myshop/company-name',
                            'myshop/vat-number'
                        ];

                        companyFields.forEach(fieldId => {
                            const field = document.getElementById(fieldId);
                            if (field) {
                                const wrapper = field.closest('.wc-block-components-text-input');
                                if (wrapper) {
                                    wrapper.style.display = customerType === 'company' ? 'block' : 'none';
                                }
                            }
                        });
                    }, [customerType]);

                    return null;
                };

                registerPlugin('myshop-conditional-fields', {
                    render: ConditionalFieldsController,
                    scope: 'woocommerce-checkout'
                });
            })();
            "
        );
    }
} );
```

**What this does:**
- Watches for changes to customer type field
- Shows/hides company fields based on selection
- Updates in real-time without page reload

## Pattern 3: Dynamic Default Values

Provide smart defaults based on customer history or external data.

### Use Case: Remember Customer Preferences

```php
<?php
/**
 * Set default delivery preference from customer's last order
 */
add_filter( 'woocommerce_get_default_value_for_myshop/delivery-preference', function( $default, $context ) {
    // Only for logged-in customers
    if ( ! is_user_logged_in() ) {
        return $default;
    }

    // Get customer's last order
    $customer = new WC_Customer( get_current_user_id() );
    $last_order = wc_get_orders(
        array(
            'customer' => $customer->get_id(),
            'limit'    => 1,
            'orderby'  => 'date',
            'order'    => 'DESC',
        )
    );

    if ( empty( $last_order ) ) {
        return $default;
    }

    $last_order = $last_order[0];

    // Get their previous delivery preference
    $checkout_fields = Automattic\WooCommerce\Blocks\Package::container()
        ->get( Automattic\WooCommerce\Blocks\Domain\Services\CheckoutFields::class );

    $previous_preference = $checkout_fields->get_field_from_object(
        'myshop/delivery-preference',
        $last_order,
        'other'
    );

    // Use previous preference if it exists
    return $previous_preference ?: $default;
}, 10, 2 );
```

### Use Case: Populate from User Meta

```php
<?php
/**
 * Pre-fill company VAT from user profile
 */
add_filter( 'woocommerce_get_default_value_for_myshop/vat-number', function( $default, $context ) {
    if ( is_user_logged_in() ) {
        $saved_vat = get_user_meta( get_current_user_id(), 'company_vat_number', true );
        if ( $saved_vat ) {
            return $saved_vat;
        }
    }

    return $default;
}, 10, 2 );
```

### Use Case: Calculate Based on Cart Contents

```php
<?php
/**
 * Auto-select signature required for high-value orders
 */
add_filter( 'woocommerce_get_default_value_for_myshop/delivery-preference', function( $default, $context ) {
    $cart_total = WC()->cart->get_total( 'raw' );

    // Orders over $500 default to signature required
    if ( $cart_total > 500 ) {
        return 'signature';
    }

    return $default;
}, 10, 2 );
```

## Pattern 4: External API Integration

Validate fields against external services.

### Use Case: VAT Number Verification

```php
<?php
/**
 * Validate VAT number with EU VIES service
 */
add_filter( 'woocommerce_validate_additional_field', function( $errors, $field_key, $field_value ) {
    if ( $field_key !== 'myshop/vat-number' || empty( $field_value ) ) {
        return $errors;
    }

    // Extract country code and number
    $country_code = substr( $field_value, 0, 2 );
    $vat_number   = substr( $field_value, 2 );

    // Call EU VIES validation API
    $response = wp_remote_post(
        'https://ec.europa.eu/taxation_customs/vies/services/checkVatService',
        array(
            'timeout' => 10,
            'body'    => sprintf(
                '<?xml version="1.0" encoding="UTF-8"?>
                <soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/">
                    <soapenv:Body>
                        <checkVat xmlns="urn:ec.europa.eu:taxud:vies:services:checkVat:types">
                            <countryCode>%s</countryCode>
                            <vatNumber>%s</vatNumber>
                        </checkVat>
                    </soapenv:Body>
                </soapenv:Envelope>',
                esc_xml( $country_code ),
                esc_xml( $vat_number )
            ),
            'headers' => array(
                'Content-Type' => 'text/xml; charset=utf-8',
                'SOAPAction'   => '',
            ),
        )
    );

    // Handle API errors gracefully
    if ( is_wp_error( $response ) ) {
        // Log error but don't block checkout
        error_log( 'VAT validation API error: ' . $response->get_error_message() );
        return $errors;
    }

    $body = wp_remote_retrieve_body( $response );

    // Check if VAT is valid
    if ( strpos( $body, '<valid>true</valid>' ) === false ) {
        $errors->add(
            'invalid_vat_number',
            __( 'VAT number could not be verified. Please check and try again.', 'myshop' )
        );
    }

    return $errors;
}, 10, 3 );
```

**Important considerations for API validation:**

1. **Add timeouts** - Don't make customers wait too long
2. **Fail gracefully** - If API is down, consider allowing checkout
3. **Cache results** - Use transients to avoid repeated calls
4. **Async validation** - Consider validating after order creation for non-critical checks

### Improved Version with Caching

```php
<?php
/**
 * Validate VAT with caching
 */
add_filter( 'woocommerce_validate_additional_field', function( $errors, $field_key, $field_value ) {
    if ( $field_key !== 'myshop/vat-number' || empty( $field_value ) ) {
        return $errors;
    }

    // Check cache first
    $cache_key = 'vat_validation_' . md5( $field_value );
    $cached_result = get_transient( $cache_key );

    if ( $cached_result !== false ) {
        if ( $cached_result === 'invalid' ) {
            $errors->add( 'invalid_vat_number', __( 'VAT number is not valid.', 'myshop' ) );
        }
        return $errors;
    }

    // Perform validation (code from above)
    $is_valid = validate_vat_number_with_api( $field_value );

    // Cache for 24 hours
    set_transient( $cache_key, $is_valid ? 'valid' : 'invalid', DAY_IN_SECONDS );

    if ( ! $is_valid ) {
        $errors->add( 'invalid_vat_number', __( 'VAT number is not valid.', 'myshop' ) );
    }

    return $errors;
}, 10, 3 );
```

## Pattern 5: Reacting to Field Changes

Perform actions when field values are saved.

### Use Case: Save VAT to Customer Profile

```php
<?php
/**
 * Save VAT number to customer profile for future use
 */
add_action( 'woocommerce_set_additional_field_value', function( $field_key, $field_value, $field_id, $object ) {
    // Only for VAT number field
    if ( $field_key !== 'myshop/vat-number' ) {
        return;
    }

    // Only when saving to order
    if ( ! $object instanceof WC_Order ) {
        return;
    }

    // Get customer ID
    $customer_id = $object->get_customer_id();

    if ( $customer_id ) {
        // Save to user meta for future checkouts
        update_user_meta( $customer_id, 'company_vat_number', $field_value );
    }
}, 10, 4 );
```

### Use Case: Trigger External System

```php
<?php
/**
 * Notify shipping system of delivery preferences
 */
add_action( 'woocommerce_set_additional_field_value', function( $field_key, $field_value, $field_id, $object ) {
    if ( $field_key !== 'myshop/delivery-preference' || ! $object instanceof WC_Order ) {
        return;
    }

    // Send to external shipping system
    wp_remote_post(
        'https://shipping-api.example.com/preferences',
        array(
            'body' => array(
                'order_id'   => $object->get_id(),
                'preference' => $field_value,
            ),
        )
    );
}, 10, 4 );
```

## Pattern 6: Multiple Related Fields

Working with groups of related fields.

### Use Case: Delivery Date and Time Selection

```php
<?php
add_action( 'woocommerce_init', function() {
    // Date field
    woocommerce_register_additional_checkout_field(
        array(
            'id'         => 'myshop/delivery-date',
            'label'      => __( 'Preferred Delivery Date', 'myshop' ),
            'location'   => 'order',
            'type'       => 'text',
            'required'   => true,
            'attributes' => array(
                'type'  => 'date',
                'min'   => date( 'Y-m-d', strtotime( '+2 days' ) ),  // Minimum 2 days from now
                'max'   => date( 'Y-m-d', strtotime( '+30 days' ) ), // Maximum 30 days out
            ),
        )
    );

    // Time slot field
    woocommerce_register_additional_checkout_field(
        array(
            'id'       => 'myshop/delivery-time',
            'label'    => __( 'Preferred Time Slot', 'myshop' ),
            'location' => 'order',
            'type'     => 'select',
            'required' => true,
            'options'  => array(
                array( 'value' => '9-12',  'label' => '9:00 AM - 12:00 PM' ),
                array( 'value' => '12-15', 'label' => '12:00 PM - 3:00 PM' ),
                array( 'value' => '15-18', 'label' => '3:00 PM - 6:00 PM' ),
                array( 'value' => '18-21', 'label' => '6:00 PM - 9:00 PM' ),
            ),
        )
    );
} );
```

### Cross-Field Validation

```php
<?php
/**
 * Validate delivery date isn't on a holiday
 */
add_filter( 'woocommerce_blocks_validate_location_order_fields', function( $errors, $fields, $location ) {
    $delivery_date = $fields['myshop/delivery-date'] ?? '';

    if ( empty( $delivery_date ) ) {
        return $errors;
    }

    // Check if date is a weekend
    $day_of_week = date( 'N', strtotime( $delivery_date ) );
    if ( $day_of_week >= 6 ) {
        $errors->add(
            'weekend_delivery',
            __( 'Weekend delivery is not available. Please select a weekday.', 'myshop' )
        );
    }

    // Check against holiday list
    $holidays = array( '2025-12-25', '2025-01-01', '2025-07-04' );  // Example holidays
    if ( in_array( $delivery_date, $holidays, true ) ) {
        $errors->add(
            'holiday_delivery',
            __( 'Delivery is not available on this date. Please select another date.', 'myshop' )
        );
    }

    // Validate time slot availability
    $delivery_time = $fields['myshop/delivery-time'] ?? '';
    if ( ! is_time_slot_available( $delivery_date, $delivery_time ) ) {
        $errors->add(
            'slot_unavailable',
            __( 'This time slot is fully booked. Please select another time.', 'myshop' )
        );
    }

    return $errors;
}, 10, 3 );

/**
 * Check if a time slot is available (example implementation)
 */
function is_time_slot_available( $date, $time_slot ) {
    // Query orders with this date/time combination
    $orders = wc_get_orders(
        array(
            'limit'        => 10,  // Max 10 deliveries per slot
            'meta_query'   => array(
                'relation' => 'AND',
                array(
                    'key'   => '_myshop_delivery-date',
                    'value' => $date,
                ),
                array(
                    'key'   => '_myshop_delivery-time',
                    'value' => $time_slot,
                ),
            ),
        )
    );

    return count( $orders ) < 10;  // Return true if under capacity
}
```

## Introduction to JavaScript Extensions

For UI customization beyond what additional fields provide, you'll need JavaScript.

### Slot Fills: Adding Content to Checkout

Slot fills let you inject custom content into predefined locations without creating full inner blocks.

```php
<?php
/**
 * Enqueue script that uses a slot fill
 */
add_action( 'wp_enqueue_scripts', function() {
    if ( is_checkout() ) {
        wp_enqueue_script(
            'myshop-loyalty-display',
            plugins_url( 'loyalty-display.js', __FILE__ ),
            array( 'wp-element', 'wp-plugins', 'wc-blocks-checkout' ),
            '1.0.0',
            true
        );
    }
} );
```

**loyalty-display.js:**
```javascript
const { registerPlugin } = wp.plugins;
const { ExperimentalOrderMeta } = wc.blocksCheckout;
const { createElement } = wp.element;

const LoyaltyPointsDisplay = () => {
    return createElement(
        'div',
        { className: 'loyalty-points-notice' },
        createElement('h3', null, 'Loyalty Rewards'),
        createElement('p', null, 'You will earn 50 points with this purchase!')
    );
};

registerPlugin('myshop-loyalty-points', {
    render: () => createElement(
        ExperimentalOrderMeta,
        null,
        createElement(LoyaltyPointsDisplay)
    ),
    scope: 'woocommerce-checkout'
});
```

**Available Slot Fills:**
- `ExperimentalOrderMeta` - Below order summary
- `ExperimentalOrderShippingPackages` - Inside shipping options
- `ExperimentalOrderLocalPickupPackages` - Inside local pickup

### Filters: Modifying Displayed Data

Use filters to change how data appears without creating components.

```javascript
const { registerCheckoutFilters } = wc.blocksCheckout;

registerCheckoutFilters('myshop-filters', {
    // Hide specific payment methods based on cart total
    paymentMethods: (methods) => {
        const cartTotal = wp.data.select('wc/store/cart')
            .getCartTotals().total_price;

        // Hide cash on delivery for orders over $200
        if (cartTotal > 20000) {  // In cents
            return methods.filter(method => method.id !== 'cod');
        }

        return methods;
    },

    // Add custom text to total
    totalLabel: (label, extensions) => {
        return label + ' (tax included)';
    },

    // Modify how item prices display
    cartItemPrice: (price, extensions, args) => {
        // Add "per item" suffix
        return price + ' <small>per item</small>';
    }
});
```

**Common filter names:**
- `paymentMethods` - Filter available payment methods
- `shippingRates` - Filter available shipping options
- `totalLabel` - Modify "Total" label
- `totalValue` - Modify total price display
- `cartItemPrice` - Modify item price display
- `subtotalPriceFormat` - Modify subtotal format
- `couponName` - Modify how coupon codes display

## Performance Optimization

### 1. Cache Expensive Calculations

```php
<?php
/**
 * Cache default value calculation
 */
add_filter( 'woocommerce_get_default_value_for_myshop/delivery-preference', function( $default, $context ) {
    if ( ! is_user_logged_in() ) {
        return $default;
    }

    $cache_key = 'delivery_pref_' . get_current_user_id();
    $cached = get_transient( $cache_key );

    if ( $cached !== false ) {
        return $cached;
    }

    // Expensive calculation here
    $value = calculate_default_preference();

    set_transient( $cache_key, $value, HOUR_IN_SECONDS );

    return $value;
}, 10, 2 );
```

### 2. Limit API Calls

```php
<?php
/**
 * Only validate on final submission, not during sync
 */
add_filter( 'woocommerce_validate_additional_field', function( $errors, $field_key, $field_value ) {
    if ( $field_key !== 'myshop/vat-number' ) {
        return $errors;
    }

    // Skip expensive validation during auto-sync
    // Only validate when explicitly processing checkout
    if ( ! did_action( 'woocommerce_store_api_checkout_order_processed' ) ) {
        // Perform expensive API validation
        $is_valid = validate_with_external_api( $field_value );

        if ( ! $is_valid ) {
            $errors->add( 'invalid_vat', 'VAT number is invalid' );
        }
    }

    return $errors;
}, 10, 3 );
```

### 3. Optimize Default Value Queries

```php
<?php
/**
 * Use efficient query for customer's last order
 */
add_filter( 'woocommerce_get_default_value_for_myshop/delivery-preference', function( $default ) {
    global $wpdb;

    if ( ! is_user_logged_in() ) {
        return $default;
    }

    // Direct DB query is faster than wc_get_orders for this use case
    $last_preference = $wpdb->get_var( $wpdb->prepare(
        "SELECT meta_value
         FROM {$wpdb->postmeta} pm
         JOIN {$wpdb->posts} p ON p.ID = pm.post_id
         WHERE pm.meta_key = %s
         AND p.post_type = 'shop_order'
         AND p.post_author = %d
         ORDER BY p.post_date DESC
         LIMIT 1",
        '_myshop_delivery-preference',
        get_current_user_id()
    ) );

    return $last_preference ?: $default;
} );
```

## Common Pitfalls in Advanced Extensions

Avoid these common mistakes when building complex checkout extensions:

### ❌ Pitfall 1: Not Validating Cross-Field Dependencies

```php
// WRONG - Only validating individual field
add_filter('woocommerce_validate_additional_field', function($errors, $key, $value) {
    if ($key === 'myshop/delivery-date' && empty($value)) {
        $errors->add('required', 'Date required');
    }
    return $errors;
}, 10, 3);

// CORRECT - Use location validation for related fields
add_filter('woocommerce_blocks_validate_location_order_fields', function($errors, $fields, $location) {
    $date = $fields['myshop/delivery-date'] ?? '';
    $time = $fields['myshop/delivery-time'] ?? '';
    
    if ($date && !$time) {
        $errors->add('missing_time', 'Please select a time slot for your delivery date');
    }
    return $errors;
}, 10, 3);
```

**Why:** Related fields should be validated together to check their relationship.

### ❌ Pitfall 2: Expensive API Calls on Every Sync

```php
// WRONG - Calls API on every field change
add_filter('woocommerce_validate_additional_field', function($errors, $key, $value) {
    if ($key === 'myshop/vat-number') {
        $is_valid = call_external_vat_api($value);  // Slow!
        if (!$is_valid) {
            $errors->add('invalid', 'Invalid VAT');
        }
    }
    return $errors;
}, 10, 3);

// CORRECT - Cache results and validate only on final submission
add_filter('woocommerce_validate_additional_field', function($errors, $key, $value) {
    if ($key === 'myshop/vat-number') {
        // Check cache first
        $cache_key = 'vat_' . md5($value);
        $cached = get_transient($cache_key);
        
        if ($cached === false && did_action('woocommerce_store_api_checkout_order_processed')) {
            $is_valid = call_external_vat_api($value);
            set_transient($cache_key, $is_valid, DAY_IN_SECONDS);
        } else {
            $is_valid = $cached;
        }
        
        if (!$is_valid) {
            $errors->add('invalid', 'Invalid VAT');
        }
    }
    return $errors;
}, 10, 3);
```

**Why:** API calls are slow and expensive. Cache results and defer non-critical validation.

### ❌ Pitfall 3: Not Handling API Failures Gracefully

```php
// WRONG - Blocks checkout if API is down
$response = wp_remote_get('https://api.example.com/validate');
if (is_wp_error($response)) {
    $errors->add('api_error', 'Cannot proceed');  // Blocks all checkouts!
}

// CORRECT - Degrade gracefully
$response = wp_remote_get('https://api.example.com/validate');
if (is_wp_error($response)) {
    error_log('API validation unavailable: ' . $response->get_error_message());
    // Allow checkout to proceed, validate post-purchase instead
    return $errors;
}
```

**Why:** Third-party API failures shouldn't prevent all checkouts. Consider async validation.

### ❌ Pitfall 4: Forgetting to Sanitize Before Validation

```php
// WRONG - Validating unsanitized data
add_filter('woocommerce_validate_additional_field', function($errors, $key, $value) {
    if ($key === 'myshop/phone' && strlen($value) !== 10) {
        $errors->add('invalid', 'Must be 10 digits');  // Might have spaces/dashes!
    }
    return $errors;
}, 10, 3);

// CORRECT - Sanitize first, then validate
add_filter('woocommerce_sanitize_additional_field', function($value, $key) {
    if ($key === 'myshop/phone') {
        return filter_var($value, FILTER_SANITIZE_NUMBER_INT);  // Remove non-digits
    }
    return $value;
}, 10, 2);

add_filter('woocommerce_validate_additional_field', function($errors, $key, $value) {
    if ($key === 'myshop/phone' && strlen($value) !== 10) {
        $errors->add('invalid', 'Must be 10 digits');  // Now checks sanitized value
    }
    return $errors;
}, 10, 3);
```

**Why:** Sanitization runs before validation. Clean data first, validate second.

### ❌ Pitfall 5: Inefficient Default Value Queries

```php
// WRONG - Queries all orders every time
add_filter('woocommerce_get_default_value_for_myshop/preference', function($default) {
    $orders = wc_get_orders(array('customer' => get_current_user_id()));
    foreach ($orders as $order) {
        $pref = $order->get_meta('preference');
        if ($pref) return $pref;
    }
    return $default;
});

// CORRECT - Use transient caching
add_filter('woocommerce_get_default_value_for_myshop/preference', function($default) {
    if (!is_user_logged_in()) return $default;
    
    $cache_key = 'pref_' . get_current_user_id();
    $cached = get_transient($cache_key);
    
    if ($cached !== false) {
        return $cached;
    }
    
    $orders = wc_get_orders(array(
        'customer' => get_current_user_id(),
        'limit' => 1,
        'orderby' => 'date',
        'order' => 'DESC'
    ));
    
    $pref = $orders ? $orders[0]->get_meta('preference') : $default;
    set_transient($cache_key, $pref, HOUR_IN_SECONDS);
    
    return $pref;
});
```

**Why:** Default value filters run on every checkout load. Cache expensive operations.

### ❌ Pitfall 6: Using JavaScript Without Proper Enqueue Checks

```php
// WRONG - Always enqueues, even on non-checkout pages
add_action('wp_enqueue_scripts', function() {
    wp_enqueue_script('my-script', 'script.js', array('wc-blocks-checkout'));
});

// CORRECT - Only enqueue on checkout
add_action('wp_enqueue_scripts', function() {
    if (is_checkout() && !is_wc_endpoint_url('order-received')) {
        wp_enqueue_script('my-script', 'script.js', array('wc-blocks-checkout'));
    }
});
```

**Why:** Scripts should only load where needed. Improves performance site-wide.

---

## Testing Your Extensions

### Unit Test Example

```php
<?php
/**
 * Test field registration and validation
 */
class Test_Delivery_Fields extends WC_Unit_Test_Case {

    public function test_field_registered() {
        do_action( 'woocommerce_init' );

        $checkout_fields = Automattic\WooCommerce\Blocks\Package::container()
            ->get( Automattic\WooCommerce\Blocks\Domain\Services\CheckoutFields::class );

        $fields = $checkout_fields->get_additional_fields();

        $this->assertArrayHasKey( 'myshop/delivery-preference', $fields );
    }

    public function test_weekend_validation() {
        $errors = new WP_Error();

        $fields = array(
            'myshop/delivery-date' => '2025-11-15',  // Saturday
        );

        $errors = apply_filters(
            'woocommerce_blocks_validate_location_order_fields',
            $errors,
            $fields,
            'order'
        );

        $this->assertTrue( $errors->has_errors() );
        $this->assertEquals( 'weekend_delivery', $errors->get_error_code() );
    }
}
```

## Debugging Advanced Extensions

When working with complex patterns, these debugging techniques are essential:

### Debug Conditional Field Logic

```php
// See what fields are being validated
add_filter('woocommerce_blocks_validate_location_address_fields', function($errors, $fields, $location) {
    error_log('Address fields received: ' . print_r($fields, true));
    error_log('Customer type: ' . ($fields['myshop/customer-type'] ?? 'not set'));
    
    // Your validation logic
    return $errors;
}, 10, 3);
```

### Debug API Integration Issues

```php
// Log API responses
add_filter('woocommerce_validate_additional_field', function($errors, $key, $value) {
    if ($key === 'myshop/vat-number') {
        $response = call_vat_api($value);
        error_log('VAT API Response: ' . print_r($response, true));
        error_log('Response code: ' . wp_remote_retrieve_response_code($response));
    }
    return $errors;
}, 10, 3);
```

### Debug Default Value Problems

```php
// See what default value is being returned
add_filter('woocommerce_get_default_value_for_myshop/delivery-preference', function($default, $context) {
    $calculated = calculate_preference();
    error_log("Default value - Input: $default, Calculated: $calculated, Context: $context");
    return $calculated;
}, 10, 2);
```

### Debug Caching Issues

```php
// Clear specific transients for testing
delete_transient('vat_validation_' . md5($test_vat_number));

// Or clear all your transients
global $wpdb;
$wpdb->query("DELETE FROM $wpdb->options WHERE option_name LIKE '_transient_vat_%'");
```

### Monitor Performance

```php
// Time your expensive operations
add_filter('woocommerce_validate_additional_field', function($errors, $key, $value) {
    if ($key === 'myshop/vat-number') {
        $start = microtime(true);
        $is_valid = call_vat_api($value);
        $time = (microtime(true) - $start) * 1000;
        error_log("VAT validation took {$time}ms");
    }
    return $errors;
}, 10, 3);
```

### Debug JavaScript Conditional Display

Add to browser console:

```javascript
// See current checkout state
wp.data.select('wc/store/checkout').getBillingAddress();
wp.data.select('wc/store/checkout').getExtensionData();

// Watch for state changes
wp.data.subscribe(() => {
    const store = wp.data.select('wc/store/checkout');
    console.log('Checkout state updated:', store.getCheckoutStatus());
});
```

### Test API Failures

Temporarily force API errors to test graceful degradation:

```php
add_filter('woocommerce_validate_additional_field', function($errors, $key, $value) {
    if ($key === 'myshop/vat-number') {
        // Simulate API failure for testing
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('Simulating API failure for testing');
            return $errors;  // Skip validation
        }
        
        $is_valid = call_vat_api($value);
        // ... rest of code
    }
    return $errors;
}, 10, 3);
```

### Debug Query Performance

```php
// Enable query logging
define('SAVEQUERIES', true);

// Then after your operation:
global $wpdb;
error_log('Total queries: ' . count($wpdb->queries));
foreach ($wpdb->queries as $query) {
    if ($query[1] > 0.01) {  // Queries over 10ms
        error_log("Slow query ({$query[1]}s): {$query[0]}");
    }
}
```

---

## What You've Learned

✓ **Field types** - Select dropdowns and checkboxes beyond basic text inputs

✓ **Conditional logic** - Show/hide fields and make them conditionally required

✓ **Dynamic defaults** - Set smart default values from history or calculations

✓ **API integration** - Validate against external services with caching

✓ **Cross-field validation** - Validate multiple fields together

✓ **React to changes** - Perform actions when field values are saved

✓ **JavaScript basics** - Slot fills and filters for UI customization

✓ **Performance** - Optimize with caching and efficient queries

## Next Steps

### Need Complete Reference?

→ **[Checkout Blocks API Reference](./04-checkout-blocks-api-reference.md)** - All hooks, filters, and functions documented

### Want to Build Custom Components?

For completely custom UI components (beyond slot fills), you'll need to create Inner Blocks using React. This is covered in the API reference under "Inner Blocks Registration".

### Production Checklist

Before deploying your checkout extensions:

- [ ] Test with WooCommerce debug mode enabled
- [ ] Verify validation works correctly
- [ ] Test on mobile devices
- [ ] Check performance with caching disabled
- [ ] Ensure graceful handling of API failures
- [ ] Add proper error logging
- [ ] Test with various payment and shipping methods
- [ ] Verify data saves correctly to orders
- [ ] Check compatibility with popular plugins
- [ ] Test translation strings work

---

**You're now equipped to build sophisticated checkout extensions!** These patterns cover the majority of real-world customization needs while maintaining performance and user experience.
