# Checkout Blocks API Reference

Complete technical reference for WooCommerce Checkout Blocks extension APIs.

## Table of Contents

- [Additional Checkout Fields](#additional-checkout-fields)
  - [Registration](#field-registration)
  - [PHP Hooks](#php-hooks-for-additional-fields)
  - [CheckoutFields Service](#checkoutfields-service)
- [Data Stores](#data-stores)
  - [Checkout Store](#checkout-store-wcstorescheckout)
  - [Cart Store](#cart-store-wcstorecart)
- [Store API Endpoints](#store-api-endpoints)
- [JavaScript APIs](#javascript-apis)
  - [Slot Fills](#slot-fills)
  - [Filter Registry](#filter-registry)
  - [Inner Blocks](#inner-blocks)
- [Migration Guide](#migration-guide-shortcode-to-blocks)
- [Troubleshooting Reference](#troubleshooting-reference)

---

## Additional Checkout Fields

### Field Registration

#### `woocommerce_register_additional_checkout_field( $options )`

Registers a custom field in the checkout block.

**Parameters:**

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `id` | string | Yes | Unique identifier in format `namespace/field-name` |
| `label` | string | Yes | User-facing label (translatable) |
| `location` | string | Yes | Where field appears: `'contact'`, `'address'`, or `'order'` |
| `type` | string | Yes | Input type: `'text'`, `'checkbox'`, or `'select'` |
| `required` | boolean | No | Whether field is mandatory (default: `false`) |
| `options` | array | Conditional | Required for `type: 'select'`. Array of `array('value' => '', 'label' => '')` |
| `attributes` | array | No | HTML attributes for the input element |
| `show_in_order_confirmation` | boolean | No | Show in order confirmation (default: `true`) |

**Example:**

```php
woocommerce_register_additional_checkout_field(array(
    'id'       => 'myplugin/gift-message',
    'label'    => __( 'Gift Message', 'myplugin' ),
    'location' => 'order',
    'type'     => 'text',
    'required' => false,
    'attributes' => array(
        'maxlength'   => 200,
        'placeholder' => __( 'Enter gift message (optional)', 'myplugin' )
    )
));
```

**Location Mapping:**

| Location | Appears In | Data Access Context |
|----------|-----------|-------------------|
| `'contact'` | Email/contact information section | `'other'` |
| `'address'` | Both billing AND shipping addresses | `'billing'` or `'shipping'` |
| `'order'` | Order information section | `'other'` |

**Field Types:**

**Text Field:**
```php
'type' => 'text',
'attributes' => array(
    'placeholder' => 'Placeholder text',
    'minlength'   => 1,            // HTML5 validation
    'maxlength'   => 100,
    'type'        => 'email',      // Can use HTML5 input types
)
```

**Select Field:**
```php
'type' => 'select',
'options' => array(
    array('value' => 'option1', 'label' => 'Option 1'),
    array('value' => 'option2', 'label' => 'Option 2'),
)
```

**Checkbox Field:**
```php
'type' => 'checkbox',
// Value will be '1' if checked, empty string if unchecked
```

---

### PHP Hooks for Additional Fields

#### Validation Hooks

##### `woocommerce_validate_additional_field`

Validate a single additional field.

**Signature:**
```php
apply_filters( 'woocommerce_validate_additional_field', WP_Error $errors, string $field_key, mixed $field_value );
```

**Parameters:**
- `$errors` (WP_Error): Add errors via `$errors->add( 'code', 'message' )`
- `$field_key` (string): Field ID (e.g., `'myshop/custom-field'`)
- `$field_value` (mixed): Field value to validate

**Example:**
```php
add_filter( 'woocommerce_validate_additional_field', function( $errors, $key, $value ) {
    if ( $key === 'myshop/phone' && ! empty( $value ) ) {
        if ( strlen( $value ) !== 10 || ! ctype_digit( $value ) ) {
            $errors->add( 'invalid_phone', 'Phone must be 10 digits' );
        }
    }
    return $errors;
}, 10, 3 );
```

##### `woocommerce_blocks_validate_location_{$location}_fields`

Validate multiple fields together for cross-field validation.

**Available hooks:**
- `woocommerce_blocks_validate_location_contact_fields`
- `woocommerce_blocks_validate_location_address_fields`
- `woocommerce_blocks_validate_location_order_fields`

**Signature:**
```php
apply_filters( "woocommerce_blocks_validate_location_{$location}_fields", WP_Error $errors, array $fields, string $location );
```

**Parameters:**
- `$errors` (WP_Error): Add errors here
- `$fields` (array): All field values keyed by field ID
- `$location` (string): Location identifier

**Example:**
```php
add_filter( 'woocommerce_blocks_validate_location_order_fields', function( $errors, $fields, $location ) {
    $delivery_date = $fields['myshop/delivery-date'] ?? '';
    $delivery_time = $fields['myshop/delivery-time'] ?? '';

    if ( $delivery_date && $delivery_time ) {
        if ( ! is_slot_available( $delivery_date, $delivery_time ) ) {
            $errors->add( 'slot_full', 'This time slot is fully booked' );
        }
    }

    return $errors;
}, 10, 3 );
```

#### Sanitization Hooks

##### `woocommerce_sanitize_additional_field`

Sanitize field value before saving.

**Signature:**
```php
apply_filters( 'woocommerce_sanitize_additional_field', mixed $value, string $field_key );
```

**Example:**
```php
add_filter( 'woocommerce_sanitize_additional_field', function( $value, $key ) {
    if ( $key === 'myshop/phone' ) {
        // Remove all non-numeric characters
        return filter_var( $value, FILTER_SANITIZE_NUMBER_INT );
    }
    return $value;
}, 10, 2 );
```

#### Action Hooks

##### `woocommerce_set_additional_field_value`

Fires when field value is saved to order or customer.

**Signature:**
```php
do_action( 'woocommerce_set_additional_field_value', string $field_key, mixed $field_value, int $field_id, object $object );
```

**Parameters:**
- `$field_key` (string): Field ID
- `$field_value` (mixed): The value being saved
- `$field_id` (int): Internal field ID
- `$object` (WC_Order|WC_Customer): Object being updated

**Example:**
```php
add_action( 'woocommerce_set_additional_field_value', function( $key, $value, $id, $object ) {
    if ( $key === 'myshop/subscribe' && $value === '1' && $object instanceof WC_Order ) {
        // Subscribe customer to newsletter
        subscribe_to_newsletter( $object->get_billing_email() );
    }
}, 10, 4 );
```

#### Default Value Hooks

##### `woocommerce_get_default_value_for_{$field_id}`

Set default value for a field.

**Signature:**
```php
apply_filters( "woocommerce_get_default_value_for_{$field_id}", mixed $default, string $context );
```

**Example:**
```php
add_filter( 'woocommerce_get_default_value_for_myshop/delivery-preference', function( $default, $context ) {
    if ( is_user_logged_in() ) {
        return get_user_meta( get_current_user_id(), 'preferred_delivery', true );
    }
    return $default;
}, 10, 2 );
```

---

### CheckoutFields Service

Service class for accessing additional field data.

#### Getting the Service

```php
use Automattic\WooCommerce\Blocks\Package;
use Automattic\WooCommerce\Blocks\Domain\Services\CheckoutFields;

$checkout_fields = Package::container()->get( CheckoutFields::class );
```

#### Methods

##### `get_field_from_object( $field_id, $object, $context )`

Get field value from order or customer.

**Parameters:**
- `$field_id` (string): Field ID (e.g., `'myshop/custom-field'`)
- `$object` (WC_Order|WC_Customer): Object to get value from
- `$context` (string): `'billing'`, `'shipping'`, or `'other'`

**Returns:** (mixed) Field value or empty string if not set

**Example:**
```php
$value = $checkout_fields->get_field_from_object(
    'myshop/gift-message',
    $order,
    'other'
);
```

##### `get_additional_fields()`

Get all registered additional fields.

**Returns:** (array) All registered fields keyed by field ID

**Example:**
```php
$all_fields = $checkout_fields->get_additional_fields();

foreach ( $all_fields as $field_id => $field_config ) {
    echo $field_config['label'];
}
```

##### `get_fields_for_location( $location )`

Get fields for a specific location.

**Parameters:**
- `$location` (string): `'contact'`, `'address'`, or `'order'`

**Returns:** (array) Fields for that location

**Example:**
```php
$order_fields = $checkout_fields->get_fields_for_location( 'order' );
```

---

## Data Stores

### Checkout Store (`wc/store/checkout`)

React data store for checkout state.

#### Selectors

**Import:**
```javascript
import { useSelect } from '@wordpress/data';

const checkoutData = useSelect(select => {
    const store = select('wc/store/checkout');
    return {
        // Use selectors here
    };
});
```

**Available Selectors:**

| Selector | Returns | Description |
|----------|---------|-------------|
| `getCustomerId()` | number | WordPress user ID (0 if guest) |
| `getOrderId()` | number | Order ID (0 if not created) |
| `getCheckoutStatus()` | string | `'idle'`, `'before_processing'`, `'processing'`, `'after_processing'`, `'complete'` |
| `isIdle()` | boolean | Status is idle |
| `isProcessing()` | boolean | Checkout is processing |
| `isComplete()` | boolean | Checkout is complete |
| `isBeforeProcessing()` | boolean | Before processing state |
| `hasError()` | boolean | Checkout has error |
| `getBillingAddress()` | object | Billing address object |
| `getShippingAddress()` | object | Shipping address object |
| `getOrderNotes()` | string | Customer order notes |
| `getShouldCreateAccount()` | boolean | Whether to create account |
| `getExtensionData()` | object | Extension data keyed by namespace |
| `getUseShippingAsBilling()` | boolean | Use shipping as billing flag |

**Example:**
```javascript
const MyComponent = () => {
    const { orderId, billingEmail, isProcessing } = useSelect(select => {
        const store = select('wc/store/checkout');
        return {
            orderId: store.getOrderId(),
            billingEmail: store.getBillingAddress()?.email,
            isProcessing: store.isProcessing()
        };
    });

    return <div>Order ID: {orderId}</div>;
};
```

#### Actions

**Import:**
```javascript
import { useDispatch } from '@wordpress/data';

const { setBillingAddress } = useDispatch('wc/store/checkout');
```

**Available Actions:**

| Action | Parameters | Description |
|--------|-----------|-------------|
| `setBillingAddress( address )` | object | Update billing address |
| `setShippingAddress( address )` | object | Update shipping address |
| `setOrderNotes( notes )` | string | Set customer notes |
| `setShouldCreateAccount( bool )` | boolean | Set account creation flag |
| `__internalSetExtensionData( namespace, data )` | string, object | Set extension data |

**Example:**
```javascript
const UpdateNotesButton = () => {
    const { setOrderNotes } = useDispatch('wc/store/checkout');

    return (
        <button onClick={() => setOrderNotes('Please gift wrap')}>
            Add Gift Wrap Note
        </button>
    );
};
```

---

### Cart Store (`wc/store/cart`)

Data store for cart state.

#### Key Selectors

| Selector | Returns | Description |
|----------|---------|-------------|
| `getCartTotals()` | object | Cart totals including `total_price` (in cents) |
| `getCartItems()` | array | Cart items |
| `getCartItemsCount()` | number | Total item count |
| `getCartItemsWeight()` | number | Total weight |
| `isCartDataStale()` | boolean | Cart needs refresh |
| `getShippingRates()` | array | Available shipping rates |

**Example:**
```javascript
const cartTotal = useSelect(select => {
    return select('wc/store/cart').getCartTotals().total_price;
});

// Total is in cents
const totalInDollars = cartTotal / 100;
```

---

## Store API Endpoints

### Checkout Endpoint

**Base URL:** `/wc/store/v1/checkout`

#### GET /wc/store/v1/checkout

Retrieve current checkout state.

**Response:**
```json
{
    "order_id": 123,
    "status": "checkout-draft",
    "customer_note": "",
    "billing_address": { },
    "shipping_address": { },
    "additional_fields": {
        "namespace/field-id": "value"
    },
    "payment_method": "stripe",
    "extensions": { }
}
```

#### PUT /wc/store/v1/checkout

Update checkout fields.

**Request Body:**
```json
{
    "billing_address": {
        "first_name": "John",
        "last_name": "Doe"
    },
    "additional_fields": {
        "myshop/custom-field": "value"
    }
}
```

**Response:** Updated checkout state + cart data

#### POST /wc/store/v1/checkout

Process order and payment.

**Request Body:**
```json
{
    "billing_address": { },
    "shipping_address": { },
    "customer_note": "",
    "payment_method": "stripe",
    "payment_data": [ ],
    "additional_fields": {
        "myshop/field": "value"
    }
}
```

**Response:**
```json
{
    "order_id": 124,
    "status": "processing",
    "payment_result": {
        "payment_status": "success",
        "redirect_url": "https://example.com/checkout/order-received/124"
    }
}
```

### Store API Hooks

#### `woocommerce_store_api_checkout_update_order_from_request`

Modify order during Store API processing.

**Signature:**
```php
do_action( 'woocommerce_store_api_checkout_update_order_from_request', WC_Order $order, WP_REST_Request $request );
```

**Example:**
```php
add_action( 'woocommerce_store_api_checkout_update_order_from_request', function( $order, $request ) {
    $extensions = $request->get_param( 'extensions' );
    $custom_data = $extensions['myplugin'] ?? [];

    if ( ! empty( $custom_data['tracking_id'] ) ) {
        $order->update_meta_data( '_tracking_id', $custom_data['tracking_id'] );
    }
}, 10, 2 );
```

---

## JavaScript APIs

### Slot Fills

Inject content into predefined locations.

#### Available Slots

| Slot | Location | Import |
|------|----------|--------|
| `ExperimentalOrderMeta` | Below order summary | `wc.blocksCheckout` |
| `ExperimentalOrderShippingPackages` | Inside shipping packages | `wc.blocksCheckout` |
| `ExperimentalOrderLocalPickupPackages` | Inside local pickup | `wc.blocksCheckout` |
| `ExperimentalDiscountsMeta` | Inside discounts | `wc.blocksCheckout` |

#### Usage

```javascript
const { registerPlugin } = wp.plugins;
const { ExperimentalOrderMeta } = wc.blocksCheckout;

const MyCustomContent = () => {
    return (
        <div className="my-custom-content">
            <p>Custom content here</p>
        </div>
    );
};

registerPlugin('my-plugin-slug', {
    render: () => (
        <ExperimentalOrderMeta>
            <MyCustomContent />
        </ExperimentalOrderMeta>
    ),
    scope: 'woocommerce-checkout'
});
```

**Note:** Slots marked "Experimental" may change in future versions.

---

### Filter Registry

Modify displayed data without creating components.

#### `registerCheckoutFilters( namespace, filters )`

Register filters to modify checkout data.

**Parameters:**
- `namespace` (string): Your unique namespace
- `filters` (object): Object of filter callbacks

#### Available Filters

| Filter Name | Type | Description |
|-------------|------|-------------|
| `totalLabel` | string → string | Modify "Total" label |
| `totalValue` | string → string | Modify total price display |
| `subtotalPriceFormat` | string → string | Modify subtotal format |
| `cartItemPrice` | string → string | Modify item price display |
| `cartItemClass` | string → string | Modify item CSS classes |
| `couponName` | string → string | Modify coupon code display |
| `paymentMethods` | array → array | Filter payment methods |
| `shippingRates` | array → array | Filter shipping rates |

#### Filter Callback Signature

```javascript
filterCallback( value, extensions, args )
```

**Parameters:**
- `value`: Current value to filter
- `extensions`: Extension data
- `args`: Additional context (varies by filter)

**Returns:** Modified value

#### Examples

**Filter payment methods:**
```javascript
import { registerCheckoutFilters } from '@woocommerce/blocks-checkout';

registerCheckoutFilters('myshop', {
    paymentMethods: (methods) => {
        const cartTotal = wp.data.select('wc/store/cart')
            .getCartTotals().total_price;

        // Hide COD for orders over $200
        if (cartTotal > 20000) {
            return methods.filter(method => method.id !== 'cod');
        }

        return methods;
    }
});
```

**Modify total label:**
```javascript
registerCheckoutFilters('myshop', {
    totalLabel: (label, extensions, args) => {
        return label + ' (includes tax)';
    }
});
```

**Modify item prices:**
```javascript
registerCheckoutFilters('myshop', {
    cartItemPrice: (price, extensions, args) => {
        // args contains: cartItem
        const item = args?.cartItem;

        if (item?.quantity > 5) {
            return price + ' <span class="bulk-discount">Bulk discount applied</span>';
        }

        return price;
    }
});
```

---

### Inner Blocks

Create custom React components integrated into checkout.

#### `registerCheckoutBlock( options )`

Register a custom checkout block.

**Parameters:**
- `metadata` (object): Block metadata
  - `name` (string): Block identifier
  - `parent` (array): Allowed parent blocks
- `component` (React.Component): Your React component

**Example:**

```javascript
import { registerCheckoutBlock } from '@woocommerce/blocks-checkout';

const MyCustomBlock = ({ checkoutExtensionData }) => {
    return (
        <div className="my-custom-block">
            <h3>Custom Content</h3>
        </div>
    );
};

registerCheckoutBlock({
    metadata: {
        name: 'myshop/custom-block',
        parent: ['woocommerce/checkout-totals-block']
    },
    component: MyCustomBlock
});
```

#### Available Parent Blocks

- `woocommerce/checkout-fields-block` (left column)
- `woocommerce/checkout-totals-block` (right column)
- `woocommerce/checkout-contact-information-block`
- `woocommerce/checkout-billing-address-block`
- `woocommerce/checkout-shipping-address-block`
- `woocommerce/checkout-shipping-methods-block`
- `woocommerce/checkout-payment-block`

#### Component Props

Your component receives:

| Prop | Type | Description |
|------|------|-------------|
| `checkoutExtensionData` | object | Extension data from store |
| `extensions` | object | Extension context |
| `cart` | object | Cart data |

---

### Event System

React to checkout lifecycle events.

#### `useCheckoutEventsContext()`

Access checkout events.

**Available Events:**

| Event | When Fired | Callback Return |
|-------|-----------|----------------|
| `onCheckoutValidation` | Before processing | `true` to allow, `{errorMessage: string}` to block |
| `onCheckoutSuccess` | After successful checkout | void |
| `onCheckoutFail` | After failed checkout | void |

**Example:**

```javascript
import { useCheckoutEventsContext } from '@woocommerce/base-context';
import { useEffect } from '@wordpress/element';

const MyValidator = () => {
    const { onCheckoutValidation } = useCheckoutEventsContext();

    useEffect(() => {
        const unsubscribe = onCheckoutValidation(() => {
            // Your validation logic
            if (customCheckFails()) {
                return {
                    errorMessage: 'Custom validation failed'
                };
            }
            return true;
        });

        return unsubscribe;
    }, [onCheckoutValidation]);

    return null;
};
```

#### `usePaymentEventsContext()`

Access payment events.

**Available Events:**

| Event | When Fired | Callback Return |
|-------|-----------|----------------|
| `onPaymentSetup` | Payment method selected | `{type: 'success', meta: {}}` |
| `onPaymentProcessing` | Before payment processing | `{type: 'success'}` or `{type: 'error', message: string}` |

**Example:**

```javascript
import { usePaymentEventsContext } from '@woocommerce/base-context';

const { onPaymentSetup } = usePaymentEventsContext();

useEffect(() => {
    const unsubscribe = onPaymentSetup(() => {
        return {
            type: 'success',
            meta: {
                paymentMethodData: {
                    custom_field: 'value'
                }
            }
        };
    });

    return unsubscribe;
}, [onPaymentSetup]);
```

---

## Migration Guide: Shortcode to Blocks

### Field Registration

| Shortcode | Blocks |
|-----------|--------|
| `add_filter('woocommerce_checkout_fields', ...)` | `woocommerce_register_additional_checkout_field()` |

**Before (Shortcode):**
```php
add_filter('woocommerce_checkout_fields', function($fields) {
    $fields['billing']['custom_field'] = array(
        'type'     => 'text',
        'label'    => 'Custom Field',
        'required' => true
    );
    return $fields;
});
```

**After (Blocks):**
```php
add_action('woocommerce_init', function() {
    woocommerce_register_additional_checkout_field(array(
        'id'       => 'myshop/custom-field',
        'label'    => 'Custom Field',
        'location' => 'address',
        'type'     => 'text',
        'required' => true
    ));
});
```

### Validation

| Shortcode | Blocks |
|-----------|--------|
| `woocommerce_checkout_process` | `woocommerce_validate_additional_field` |

**Before:**
```php
add_action('woocommerce_checkout_process', function() {
    if (empty($_POST['custom_field'])) {
        wc_add_notice('Custom field required', 'error');
    }
});
```

**After:**
```php
add_filter('woocommerce_validate_additional_field', function($errors, $key, $value) {
    if ($key === 'myshop/custom-field' && empty($value)) {
        $errors->add('required', 'Custom field required');
    }
    return $errors;
}, 10, 3);
```

### Saving Data

| Shortcode | Blocks |
|-----------|--------|
| `woocommerce_checkout_update_order_meta` | Automatic (via CheckoutFields) |

**Before:**
```php
add_action('woocommerce_checkout_update_order_meta', function($order_id) {
    update_post_meta($order_id, 'custom_field', $_POST['custom_field']);
});
```

**After:**
```php
// Automatic! Just retrieve when needed:
$value = $checkout_fields->get_field_from_object(
    'myshop/custom-field',
    $order,
    'billing'
);
```

### Template Hooks

| Shortcode Hook | Blocks Alternative |
|---------------|-------------------|
| `woocommerce_before_checkout_form` | Slot Fill or Inner Block |
| `woocommerce_after_checkout_billing_form` | Additional Field with `location: 'address'` |
| `woocommerce_after_order_notes` | Additional Field with `location: 'order'` |

---

## Troubleshooting Reference

### Field Not Appearing

**Check:** Checkout page uses block, not shortcode

```php
// Verify in template:
// Block: <div class="wp-block-woocommerce-checkout">
// Shortcode: [woocommerce_checkout]
```

**Check:** Field ID format

```php
// ✓ Correct
'id' => 'namespace/field-name'

// ✗ Wrong
'id' => 'field-name'
```

**Check:** Registration timing

```php
// ✓ Correct
add_action('woocommerce_init', function() { ... });

// ✗ Wrong
add_action('init', function() { ... });
```

### Validation Not Working

**Check:** Hook signature matches

```php
// Must return $errors
add_filter('woocommerce_validate_additional_field', function($errors, $key, $value) {
    // ...
    return $errors;  // ← Don't forget this!
}, 10, 3);
```

**Check:** Field key matches exactly

```php
// Registration
'id' => 'myshop/custom-field'

// Validation
if ($key === 'myshop/custom-field') // ← Must match exactly
```

### Value Not Saving

**Check:** Location context matches

```php
// For location: 'order' or 'contact'
$value = $checkout_fields->get_field_from_object($id, $order, 'other');

// For location: 'address'
$value = $checkout_fields->get_field_from_object($id, $order, 'billing');
// or
$value = $checkout_fields->get_field_from_object($id, $order, 'shipping');
```

### JavaScript Not Loading

**Check:** Dependencies array

```php
wp_enqueue_script(
    'my-script',
    'script.js',
    array('wp-element', 'wp-plugins', 'wc-blocks-checkout'),  // ← Required dependencies
    '1.0.0',
    true
);
```

**Check:** Script runs on checkout

```php
add_action('wp_enqueue_scripts', function() {
    if (is_checkout()) {  // ← Don't forget this check
        wp_enqueue_script(...);
    }
});
```

### Common Error Messages

**"Additional field namespace is not valid"**
- Field ID must contain `/`
- Format: `namespace/field-name`

**"CheckoutFields class not found"**
- WooCommerce Blocks not active
- WooCommerce version < 8.3

**"Invalid location"**
- Must be `'contact'`, `'address'`, or `'order'`

**"Options required for select type"**
- Select fields must have `options` parameter

---

## Quick Reference

### Field Location → Data Context Mapping

| Field Location | Access Context |
|---------------|---------------|
| `'contact'` | `'other'` |
| `'order'` | `'other'` |
| `'address'` | `'billing'` or `'shipping'` |

### Checkout Status Flow

```
'idle' → 'before_processing' → 'processing' → 'after_processing' → 'complete'
                                    ↓
                                 'error'
```

### Common Patterns Quick Copy

**Basic field:**
```php
woocommerce_register_additional_checkout_field(array(
    'id' => 'ns/field', 'label' => 'Label', 'location' => 'order', 'type' => 'text'
));
```

**Validation:**
```php
add_filter('woocommerce_validate_additional_field', function($e, $k, $v) {
    if ($k === 'ns/field' && !$v) $e->add('code', 'Error');
    return $e;
}, 10, 3);
```

**Get value:**
```php
$checkout_fields->get_field_from_object('ns/field', $order, 'other');
```

**React to save:**
```php
add_action('woocommerce_set_additional_field_value', function($k, $v, $i, $o) {
    if ($k === 'ns/field') { /* do something */ }
}, 10, 4);
```

---

## Advanced Debugging Techniques

For API-level debugging and troubleshooting:

### Inspect Store API Requests

View all checkout API communications:

```javascript
// Add to browser console
const originalFetch = window.fetch;
window.fetch = function(...args) {
    if (args[0].includes('/wc/store/v1/checkout')) {
        console.log('Checkout API Request:', args);
    }
    return originalFetch.apply(this, args).then(response => {
        return response.clone().json().then(data => {
            if (args[0].includes('/wc/store/v1/checkout')) {
                console.log('Checkout API Response:', data);
            }
            return response;
        });
    });
};
```

### Debug Data Store State

Monitor checkout store changes:

```javascript
// Watch specific selector
wp.data.subscribe(() => {
    const store = wp.data.select('wc/store/checkout');
    console.log('Status:', store.getCheckoutStatus());
    console.log('Billing:', store.getBillingAddress());
    console.log('Extensions:', store.getExtensionData());
});
```

### Verify Hook Registration

Check if your PHP hooks are registered:

```php
// Add temporarily to see all registered hooks
global $wp_filter;
if (isset($wp_filter['woocommerce_validate_additional_field'])) {
    error_log('Validation hooks: ' . print_r($wp_filter['woocommerce_validate_additional_field'], true));
}
```

### Test Field Registration

```php
add_action('woocommerce_init', function() {
    $fields = Automattic\WooCommerce\Blocks\Package::container()
        ->get( Automattic\WooCommerce\Blocks\Domain\Services\CheckoutFields::class )
        ->get_additional_fields();
    
    error_log('All registered fields: ' . implode(', ', array_keys($fields)));
}, 999);
```

### Debug Filter Registry

```javascript
// See all registered filters (browser console)
console.log(window.wc.blocksCheckout);

// Test if filter is applying
const { applyFilters } = window.wp.hooks;
console.log('Filtered value:', applyFilters('checkoutFilterName', 'test-value'));
```

### Monitor Performance

```php
// Log slow validation hooks
add_filter('woocommerce_validate_additional_field', function($errors, $key, $value) {
    $start = microtime(true);
    
    // Your validation code here
    
    $duration = (microtime(true) - $start) * 1000;
    if ($duration > 100) {  // Log if over 100ms
        error_log("SLOW validation for $key: {$duration}ms");
    }
    
    return $errors;
}, 10, 3);
```

### Debug Extension Data

```php
// See what extension data is being sent
add_action('woocommerce_store_api_checkout_update_order_from_request', function($order, $request) {
    $extensions = $request->get_param('extensions');
    error_log('Extension data: ' . print_r($extensions, true));
}, 10, 2);
```

---

## Further Resources

- [WooCommerce Blocks GitHub](https://github.com/woocommerce/woocommerce-blocks)
- [Store API Documentation](https://github.com/woocommerce/woocommerce-blocks/tree/trunk/docs)
- [WordPress Data Stores](https://developer.wordpress.org/block-editor/reference-guides/packages/packages-data/)
- [React Hooks Reference](https://react.dev/reference/react)
- [WooCommerce Community Slack](https://woocommerce.com/community-slack/) - Get help from the community

---

**This reference covers the complete Checkout Blocks extension API.** Bookmark it for quick lookup when building extensions.
