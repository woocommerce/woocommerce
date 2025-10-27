# Your First Checkout Extension

**Time to complete: 10-15 minutes**

## What You'll Build

A working checkout extension that adds a "Purchase Order Number" field to the checkout form. This field will:
- Appear automatically in the checkout block
- Validate that it's filled in
- Save to the order
- Display in order confirmation and admin

By the end, you'll have a complete, working extension using just PHP.

## Prerequisites

Before starting, make sure you have:

- [ ] WooCommerce 8.3 or higher installed
- [ ] A checkout page using the checkout block (not the shortcode)
- [ ] A plugin or theme where you can add PHP code
- [ ] Access to view WooCommerce orders in admin

**Check your checkout:** Go to your checkout page. If you see a modern, two-column layout, you're using the block. If it looks like a traditional form, you're using the shortcode.

## Step 1: Register Your Field

### Where to Add Code

You can add this code to:
- Your custom plugin file
- Your theme's `functions.php`
- A custom functionality plugin

For production sites, always use a custom plugin or child theme, never the main theme.

### The Code

Add this code to your file:

```php
<?php
/**
 * Register a Purchase Order Number field in checkout
 */
add_action( 'woocommerce_init', function() {
    woocommerce_register_additional_checkout_field(
        array(
            'id'       => 'myshop/purchase-order',
            'label'    => __( 'Purchase Order Number', 'myshop' ),
            'location' => 'order',
            'type'     => 'text',
            'required' => true,
            'attributes' => array(
                'placeholder' => __( 'Enter your PO number', 'myshop' ),
                'minlength'   => 5,
                'title'       => __( 'PO number must be at least 5 characters (letters and numbers only)', 'myshop' ),
            ),
        )
    );
} );
```

### Understanding the Parameters

| Parameter | Value | What It Does |
|-----------|-------|--------------|
| `id` | `'myshop/purchase-order'` | Unique identifier (use your namespace) |
| `label` | `'Purchase Order Number'` | Label shown to customer |
| `location` | `'order'` | Where field appears (see location options below) |
| `type` | `'text'` | Input type (text, checkbox, or select) |
| `required` | `true` | Whether customer must fill it in |
| `attributes` | Array | HTML attributes for the input |

### Location Options

Choose where your field appears:

- **`'contact'`** - Appears with email/contact information (top of form)
- **`'address'`** - Appears in BOTH billing and shipping addresses
- **`'order'`** - Appears in order information section (near order notes)

For this tutorial, we're using `'order'` to keep it simple.

### Save Your File

Save your changes and proceed to testing.

## Step 2: Test Your Field

### View the Checkout

1. Go to your site's checkout page (add a product to cart first)
2. Scroll to the order information section
3. You should see "Purchase Order Number" with a text input

**Can't see it?** Jump to [Troubleshooting](#troubleshooting) below.

### Test Required Validation

1. Leave the PO Number field empty
2. Fill in all other required fields
3. Click "Place Order"

**Expected result:** You should see an error message: "Purchase Order Number is a required field"

### Test Pattern Validation

1. Enter "ABC" in the PO Number field (too short)
2. Try to place order

**Expected result:** Browser shows validation message about format (must be at least 5 characters)

### Test Success

1. Enter a valid PO number like "PO12345"
2. Complete the rest of checkout
3. Place the order

**Expected result:** Order completes successfully.

## Step 3: Add Custom Validation

Let's add server-side validation to ensure PO numbers follow your business rules.

### Add Validation Hook

Add this code after your field registration:

```php
<?php
/**
 * Validate Purchase Order Number format
 */
add_filter( 'woocommerce_validate_additional_field', function( $errors, $field_key, $field_value ) {
    // Only validate our specific field
    if ( $field_key !== 'myshop/purchase-order' ) {
        return $errors;
    }

    // Skip if field is empty (required validation handles that)
    if ( empty( $field_value ) ) {
        return $errors;
    }

    // Custom validation: must start with "PO"
    if ( strpos( strtoupper( $field_value ), 'PO' ) !== 0 ) {
        $errors->add(
            'invalid_po_format',
            __( 'Purchase Order Number must start with "PO"', 'myshop' )
        );
    }

    // Custom validation: must be alphanumeric
    if ( ! ctype_alnum( str_replace( '-', '', $field_value ) ) ) {
        $errors->add(
            'invalid_po_chars',
            __( 'Purchase Order Number can only contain letters, numbers, and hyphens', 'myshop' )
        );
    }

    return $errors;
}, 10, 3 );
```

### Test Validation

1. Go back to checkout
2. Enter "12345" (doesn't start with PO)
3. Try to place order

**Expected result:** Error message "Purchase Order Number must start with PO"

4. Enter "PO-12345" (valid format)
5. Complete order

**Expected result:** Order succeeds.

## Step 4: Access the Field Value

Now let's use the PO number data in your order processing.

### Add to Order Confirmation Email

```php
<?php
/**
 * Display PO number in order emails
 */
add_action( 'woocommerce_email_order_meta', function( $order, $sent_to_admin ) {
    // Get the CheckoutFields service
    $checkout_fields = Automattic\WooCommerce\Blocks\Package::container()
        ->get( Automattic\WooCommerce\Blocks\Domain\Services\CheckoutFields::class );

    // Get the PO number from the order
    $po_number = $checkout_fields->get_field_from_object(
        'myshop/purchase-order',
        $order,
        'other'  // Location context: 'billing', 'shipping', or 'other'
    );

    if ( $po_number ) {
        echo '<p><strong>' . __( 'Purchase Order:', 'myshop' ) . '</strong> ' . esc_html( $po_number ) . '</p>';
    }
}, 10, 2 );
```

### Display in Order Admin

```php
<?php
/**
 * Display PO number in order admin
 */
add_action( 'woocommerce_admin_order_data_after_billing_address', function( $order ) {
    $checkout_fields = Automattic\WooCommerce\Blocks\Package::container()
        ->get( Automattic\WooCommerce\Blocks\Domain\Services\CheckoutFields::class );

    $po_number = $checkout_fields->get_field_from_object(
        'myshop/purchase-order',
        $order,
        'other'
    );

    if ( $po_number ) {
        echo '<p><strong>' . __( 'PO Number:', 'myshop' ) . '</strong> ' . esc_html( $po_number ) . '</p>';
    }
} );
```

### Test Data Access

1. Place a test order with PO number "PO-TEST-001"
2. Check your order confirmation email
3. View the order in WooCommerce > Orders
4. Click on the order to view details

**Expected result:** You should see "Purchase Order: PO-TEST-001" in both the email and order admin.

## Step 5: Add Sanitization

Clean the data before it's saved to prevent issues.

```php
<?php
/**
 * Sanitize PO number before saving
 */
add_filter( 'woocommerce_sanitize_additional_field', function( $value, $field_key ) {
    if ( $field_key === 'myshop/purchase-order' ) {
        // Convert to uppercase and remove extra spaces
        $value = strtoupper( trim( $value ) );

        // Remove any characters that aren't letters, numbers, or hyphens
        $value = str_replace( array( ' ', '.', ',', '!', '@', '#', '$', '%', '^', '&', '*', '(', ')', '_', '+', '=', '[', ']', '{', '}', '|', '\\', ':', ';', '"', "'", '<', '>', '?', '/', '`', '~' ), '', $value );
    }

    return $value;
}, 10, 2 );
```

### Test Sanitization

1. Enter PO number as "po-test-123" (lowercase with hyphens)
2. Complete order
3. Check order details

**Expected result:** Saved as "PO-TEST-123" (uppercase, cleaned)

## Complete Code Example

Here's all the code together in one place:

```php
<?php
/**
 * Complete Purchase Order Number Extension
 */

// 1. Register the field
add_action( 'woocommerce_init', function() {
    woocommerce_register_additional_checkout_field(
        array(
            'id'       => 'myshop/purchase-order',
            'label'    => __( 'Purchase Order Number', 'myshop' ),
            'location' => 'order',
            'type'     => 'text',
            'required' => true,
            'attributes' => array(
                'placeholder' => __( 'Enter your PO number', 'myshop' ),
            ),
        )
    );
} );

// 2. Sanitize the value
add_filter( 'woocommerce_sanitize_additional_field', function( $value, $field_key ) {
    if ( $field_key === 'myshop/purchase-order' ) {
        $value = strtoupper( trim( $value ) );
        $value = str_replace( array( ' ', '.', ',', '!', '@', '#', '$', '%', '^', '&', '*', '(', ')', '_', '+', '=', '[', ']', '{', '}', '|', '\\', ':', ';', '"', "'", '<', '>', '?', '/', '`', '~' ), '', $value );
    }
    return $value;
}, 10, 2 );

// 3. Validate the value
add_filter( 'woocommerce_validate_additional_field', function( $errors, $field_key, $field_value ) {
    if ( $field_key !== 'myshop/purchase-order' || empty( $field_value ) ) {
        return $errors;
    }

    if ( strpos( $field_value, 'PO' ) !== 0 ) {
        $errors->add(
            'invalid_po_format',
            __( 'Purchase Order Number must start with "PO"', 'myshop' )
        );
    }

    return $errors;
}, 10, 3 );

// 4. Display in emails
add_action( 'woocommerce_email_order_meta', function( $order, $sent_to_admin ) {
    $checkout_fields = Automattic\WooCommerce\Blocks\Package::container()
        ->get( Automattic\WooCommerce\Blocks\Domain\Services\CheckoutFields::class );

    $po_number = $checkout_fields->get_field_from_object(
        'myshop/purchase-order',
        $order,
        'other'
    );

    if ( $po_number ) {
        echo '<p><strong>' . __( 'Purchase Order:', 'myshop' ) . '</strong> ' . esc_html( $po_number ) . '</p>';
    }
}, 10, 2 );

// 5. Display in admin
add_action( 'woocommerce_admin_order_data_after_billing_address', function( $order ) {
    $checkout_fields = Automattic\WooCommerce\Blocks\Package::container()
        ->get( Automattic\WooCommerce\Blocks\Domain\Services\CheckoutFields::class );

    $po_number = $checkout_fields->get_field_from_object(
        'myshop/purchase-order',
        $order,
        'other'
    );

    if ( $po_number ) {
        echo '<p><strong>' . __( 'PO Number:', 'myshop' ) . '</strong> ' . esc_html( $po_number ) . '</p>';
    }
} );
```

You can copy this entire block into your plugin file.

## Common Pitfalls to Avoid

Learning from mistakes is helpful, but avoiding them is better! Here are the most common issues developers encounter:

### ❌ Pitfall 1: Missing or Incorrect Namespace

```php
// WRONG - No namespace
'id' => 'purchase-order'

// WRONG - Wrong separator
'id' => 'myshop_purchase_order'

// CORRECT
'id' => 'myshop/purchase-order'
```

**Why it matters:** Field ID MUST include a namespace with a forward slash. Without it, the field won't register properly.

### ❌ Pitfall 2: Wrong Location Context When Retrieving Data

```php
// Field registered with location: 'order'
woocommerce_register_additional_checkout_field(array(
    'location' => 'order'
));

// WRONG - Using 'billing' context
$value = $checkout_fields->get_field_from_object($id, $order, 'billing');

// CORRECT - Use 'other' for 'order' location
$value = $checkout_fields->get_field_from_object($id, $order, 'other');
```

**Location → Context mapping:**
- `'contact'` location → use `'other'` context
- `'order'` location → use `'other'` context
- `'address'` location → use `'billing'` or `'shipping'` context

### ❌ Pitfall 3: Forgetting to Return $errors in Validation

```php
// WRONG - Doesn't return $errors
add_filter('woocommerce_validate_additional_field', function($errors, $key, $value) {
    if ($key === 'myshop/purchase-order' && empty($value)) {
        $errors->add('required', 'Field is required');
    }
    // Missing return!
}, 10, 3);

// CORRECT - Always return $errors
add_filter('woocommerce_validate_additional_field', function($errors, $key, $value) {
    if ($key === 'myshop/purchase-order' && empty($value)) {
        $errors->add('required', 'Field is required');
    }
    return $errors;  // ✓ Don't forget this!
}, 10, 3);
```

### ❌ Pitfall 4: Incorrect Hook Priority/Parameters

```php
// WRONG - Wrong parameter count
add_filter('woocommerce_validate_additional_field', function($errors, $key) {
    // Missing $value parameter!
}, 10, 2);  // Says 2 parameters but should be 3

// CORRECT
add_filter('woocommerce_validate_additional_field', function($errors, $key, $value) {
    // All parameters present
}, 10, 3);  // Matches the 3 parameters
```

### ❌ Pitfall 5: Using Shortcode Hooks with Blocks

```php
// WRONG - This is a shortcode hook, won't work with blocks
add_action('woocommerce_checkout_process', function() {
    // This won't fire in checkout blocks!
});

// CORRECT - Use block-specific hooks
add_filter('woocommerce_validate_additional_field', function($errors, $key, $value) {
    // This works with checkout blocks
}, 10, 3);
```

### ❌ Pitfall 6: Not Checking if Blocks Are Active

```php
// WRONG - Assumes blocks exist
woocommerce_register_additional_checkout_field(...);

// CORRECT - Check function exists first
add_action('woocommerce_init', function() {
    if (function_exists('woocommerce_register_additional_checkout_field')) {
        woocommerce_register_additional_checkout_field(...);
    }
});
```

**Why:** If WooCommerce Blocks isn't active or is an old version, the function won't exist and will cause a fatal error.

---

## Troubleshooting

### Field Doesn't Appear

**Check 1: Are you using checkout blocks?**
- Go to Pages > Checkout in WordPress admin
- Edit the page
- Make sure you see "Checkout" block, not `[woocommerce_checkout]` shortcode

**Check 2: Is your code running?**
```php
// Add this temporarily to test
add_action( 'woocommerce_init', function() {
    error_log( 'WooCommerce init fired!' );
} );
```
Check your error log. If you don't see the message, your code isn't loading.

**Check 3: Is there a PHP error?**
- Enable WordPress debug mode in `wp-config.php`:
```php
define( 'WP_DEBUG', true );
define( 'WP_DEBUG_LOG', true );
```
- Check `wp-content/debug.log` for errors

**Check 4: Clear caches**
- Clear any caching plugins
- Hard refresh your browser (Ctrl+Shift+R or Cmd+Shift+R)

### Field Appears But Validation Doesn't Work

**Check: Field ID matches exactly**

Your validation hook must use the exact same field ID:
```php
if ( $field_key === 'myshop/purchase-order' ) // Must match registration exactly
```

### Value Not Saving to Order

**Check: Location parameter**

When retrieving the value, use the correct location:
- If `location` is `'contact'` or `'order'`: use `'other'`
- If `location` is `'address'`: use `'billing'` or `'shipping'`

```php
// For location: 'order', use 'other'
$value = $checkout_fields->get_field_from_object(
    'myshop/purchase-order',
    $order,
    'other'  // ← This must match your field location
);
```

### Field Shows Twice

**Issue: Namespace missing or incorrect**

Field ID must include namespace with slash:
```php
'id' => 'myshop/purchase-order',  // ✓ Correct
'id' => 'purchase-order',          // ✗ Wrong - will cause issues
```

### Getting "Namespace not found" Error

**Issue: Autoloading problem**

If you get errors about `Automattic\WooCommerce\Blocks\Package`, make sure:
1. WooCommerce Blocks is active (it's bundled with WooCommerce 8.3+)
2. You're accessing the class correctly:

```php
use Automattic\WooCommerce\Blocks\Package;
use Automattic\WooCommerce\Blocks\Domain\Services\CheckoutFields;

$checkout_fields = Package::container()->get( CheckoutFields::class );
```

---

## Debugging Tips

When things aren't working as expected, these techniques will help you diagnose the issue:

### Enable WordPress Debug Mode

Add to your `wp-config.php` (before "That's all, stop editing!"):

```php
define( 'WP_DEBUG', true );
define( 'WP_DEBUG_LOG', true );
define( 'WP_DEBUG_DISPLAY', false );
```

Errors will be logged to `wp-content/debug.log`.

### Check if Your Field is Registered

Add this temporary code to verify registration:

```php
add_action( 'woocommerce_init', function() {
    $checkout_fields = Automattic\WooCommerce\Blocks\Package::container()
        ->get( Automattic\WooCommerce\Blocks\Domain\Services\CheckoutFields::class );
    
    $fields = $checkout_fields->get_additional_fields();
    error_log( 'Registered fields: ' . print_r( array_keys( $fields ), true ) );
}, 999 );
```

Check `debug.log` - you should see your field ID in the list.

### See What Data is Being Sent

1. Open your browser's Developer Tools (F12)
2. Go to the **Network** tab
3. Start checkout process
4. Look for requests to `/wc/store/v1/checkout`
5. Click on a request → **Payload** tab
6. Check if your field data is in `additional_fields`

### Verify Field Value is Saved

After placing a test order, check the database:

```php
// Add this to functions.php temporarily
add_action( 'woocommerce_checkout_order_created', function( $order ) {
    $checkout_fields = Automattic\WooCommerce\Blocks\Package::container()
        ->get( Automattic\WooCommerce\Blocks\Domain\Services\CheckoutFields::class );
    
    $value = $checkout_fields->get_field_from_object(
        'myshop/purchase-order',
        $order,
        'other'
    );
    
    error_log( 'PO Number saved: ' . $value );
} );
```

### Check for JavaScript Errors

1. Open Developer Tools (F12)
2. Go to **Console** tab
3. Look for red error messages
4. JavaScript errors can prevent the field from rendering

### Test with Default Theme

If your field doesn't appear:
1. Temporarily switch to Twenty Twenty-Four or Storefront theme
2. Test again
3. If it works, the issue is theme-related

### Common Debug Scenarios

**Field appears but validation doesn't work:**
```php
// Check your hook is firing
add_filter('woocommerce_validate_additional_field', function($errors, $key, $value) {
    error_log("Validating: $key = $value");
    // Your validation logic
    return $errors;
}, 10, 3);
```

**Value not saving to order:**
```php
// Verify the hook fires
add_action('woocommerce_set_additional_field_value', function($key, $value, $id, $object) {
    error_log("Saving: $key = $value to order " . $object->get_id());
}, 10, 4);
```

**Need to see all checkout data:**
```php
add_action('woocommerce_store_api_checkout_update_order_from_request', function($order, $request) {
    error_log('Checkout data: ' . print_r($request->get_params(), true));
}, 10, 2);
```

### Still Stuck?

1. **Check WooCommerce Status**: WooCommerce → Status → check for errors
2. **Plugin Conflicts**: Disable other plugins one by one to find conflicts
3. **Review Logs**: Check `wp-content/debug.log` for PHP errors
4. **Test Mode**: Use a payment gateway's test/sandbox mode
5. **Ask for Help**: [WooCommerce Community Slack](https://woocommerce.com/community-slack/) with your error logs

## Testing Checklist

Before considering your extension complete, verify these items:

### Functionality Tests
- [ ] Field appears on checkout page
- [ ] Field shows in correct location (order information section)
- [ ] Validation prevents empty submission (shows error message)
- [ ] Valid data allows order to complete successfully
- [ ] Data saves correctly to order

### Data Display Tests
- [ ] PO number appears in order confirmation email
- [ ] PO number appears in admin order details
- [ ] PO number displays correctly (uppercase, cleaned format)

### User Type Tests
- [ ] Works with guest checkout (no WordPress account)
- [ ] Works with logged-in customers
- [ ] Works with existing customers (repeat purchases)

### Compatibility Tests
- [ ] Mobile responsive (test on phone/tablet screen size)
- [ ] Works with different payment methods (test 2-3)
- [ ] Works with different shipping methods
- [ ] No JavaScript errors in browser console (F12 → Console tab)

### Edge Cases
- [ ] Very long PO numbers (test 50+ characters)
- [ ] Special characters in PO number (test: `PO-TEST@123#`)
- [ ] Multiple rapid form submissions (doesn't create duplicate data)

**Pro Tip:** Use WooCommerce's test mode and a payment gateway's sandbox/test environment to avoid processing real payments during testing.

---

## What You've Learned

Congratulations! You now know how to:

✓ Register a custom checkout field with `woocommerce_register_additional_checkout_field()`

✓ Add custom validation logic with `woocommerce_validate_additional_field`

✓ Sanitize field values with `woocommerce_sanitize_additional_field`

✓ Access field data from orders using `CheckoutFields` service

✓ Display custom field data in emails and admin

✓ Debug common issues with checkout extensions

## Next Steps

### Want to Build More?

→ **[Advanced Checkout Extensions](./03-advanced-checkout-extensions.md)** - Learn:
- Multiple field coordination
- Conditional field display
- Different field types (select, checkbox)
- Cross-field validation
- Integration with external APIs

### Need Reference Material?

→ **[Checkout Blocks API Reference](./04-checkout-blocks-api-reference.md)** - Complete hook and filter documentation

### Want to Customize the UI?

Currently, additional fields use default styling. If you need custom layouts or JavaScript interactions, you'll need to explore Inner Blocks (covered in the Advanced guide).

## Try These Challenges

Test your knowledge by modifying your extension:

**Easy:** Change the field to appear in the `'contact'` section instead of `'order'`

**Medium:** Add a second field for "Department" and make PO number only required if Department is "Finance"

**Hard:** Create a select field with options "Expedited", "Standard", "Economy" that saves the customer's preferred shipping speed

Answers and examples are in the [Advanced Checkout Extensions](./03-advanced-checkout-extensions.md) guide.

---

**You did it!** You've created a working checkout extension using only PHP. This same pattern works for delivery dates, gift messages, custom notes, business IDs, and hundreds of other use cases.
