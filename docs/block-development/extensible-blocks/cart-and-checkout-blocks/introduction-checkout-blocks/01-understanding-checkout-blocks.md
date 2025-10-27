# Understanding WooCommerce Checkout Blocks

**Reading time: 5 minutes**

> **💡 Compatibility Note:** This guide covers WooCommerce 9.0+ with Checkout Blocks (bundled since WooCommerce 8.3+). If you're using an older version, some features may not be available. Check your WooCommerce version: **WooCommerce → Settings → Status**.

## What This Document Covers

By the end of this guide, you'll understand:
- The fundamental architectural shift from shortcode to blocks
- Why WooCommerce made this change
- The three core concepts powering checkout blocks
- Whether checkout blocks are right for your project

## The Shift: From Templates to Components

### Shortcode Checkout (Traditional)

The traditional WooCommerce checkout (`[woocommerce_checkout]`) works like a traditional web form:

```
Customer fills form → Clicks submit → Server validates → Creates order → Redirects
```

**Key characteristics:**
- Server-rendered PHP templates
- Sequential, synchronous processing
- Validation happens on submission
- Extensions modify output via template hooks and filters
- State exists only during form submission

### Checkout Blocks (Modern)

The checkout block is a React-based application that maintains continuous state:

```
Customer interacts → State updates → Server syncs → UI updates → (repeat) → Order created
```

**Key characteristics:**
- React components with real-time state management
- Continuous synchronization with server
- Validation happens as user types
- Extensions use blocks, slots, and data stores
- State persists throughout the checkout session

## Why This Change?

### 1. Better User Experience

**Real-time validation**: Customers see errors immediately, not after clicking "Place Order"

**Dynamic updates**: Change shipping method, see total update instantly without page reload

**Responsive by default**: Mobile-optimized layout without custom CSS

### 2. Flexibility

**Visual customization**: Store owners rearrange checkout sections in the block editor

**No code changes needed**: Move billing before shipping, add content blocks, reorder elements

### 3. Modern Development

**Component-based**: Reusable, testable pieces instead of monolithic templates

**State management**: Predictable data flow using WordPress Data stores

**API-driven**: Clear separation between frontend and backend

## The Three Core Concepts

### Concept 1: Components Instead of Templates

**Shortcode thinking:**
```php
// Modify template output
add_action('woocommerce_after_checkout_billing_form', 'add_custom_field');
function add_custom_field() {
    echo '<input name="custom_field" />';
}
```

**Blocks thinking:**
```php
// Register a field component
woocommerce_register_additional_checkout_field(array(
    'id' => 'myshop/custom-field',
    'label' => 'Custom Field',
    'location' => 'address'
));
```

The system handles rendering, placement, and styling automatically.

### Concept 2: State Synchronization Instead of Form Submission

**Shortcode flow:**
1. Customer fills entire form
2. Clicks "Place Order"
3. Server receives all data at once
4. Validates everything
5. Creates order or shows errors

**Blocks flow:**
1. Customer enters email → Syncs to server → Validates → Updates UI
2. Customer selects shipping → Syncs to server → Recalculates totals → Updates UI
3. Customer enters address → Syncs to server → Validates → Updates UI
4. Customer clicks "Place Order" → Final validation → Creates order

Data flows continuously, not in one big submission.

### Concept 3: Store API as the Bridge

All communication between the React frontend and PHP backend flows through the Store API:

```
React Component
    ↓ (action)
Data Store (wc/store/checkout)
    ↓ (HTTP request)
Store API Endpoint (/wc/store/v1/checkout)
    ↓
PHP Backend (your hooks & filters)
    ↓ (response)
Store API Endpoint
    ↓
Data Store
    ↓ (selector)
React Component (re-renders)
```

**What this means for you:**
- Your PHP validation hooks still work
- Your order processing logic is unchanged
- Data access goes through the Store API instead of `$_POST`

## Visual Architecture Comparison

### Shortcode Architecture
```
Browser                          Server
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

HTML Form                        PHP Template
  ↓                                ↑
User fills form                  woocommerce_checkout_fields
  ↓                              woocommerce_form_field
Clicks submit                    Template hooks
  ↓                                ↑
POST request          →          $_POST data
  ↓                                ↓
                                 Validation
                                   ↓
                                 Create Order
                                   ↓
Response (redirect)   ←          Order complete
```

### Blocks Architecture
```
Browser                          Server
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

React Components                 Store API Endpoints
  ↓                                ↑
Data Store                       /wc/store/v1/checkout
(wc/store/checkout)                ↑
  ↓                                ↓
State changes        ←→          Your PHP hooks
(continuous)                     CheckoutFields service
  ↓                                ↓
UI updates                       Validation/Processing
                                   ↓
                                 Create Order
```

## What Stays the Same?

Good news: Much of your existing knowledge transfers directly:

✅ **Order processing hooks work** - `woocommerce_checkout_create_order`, `woocommerce_checkout_order_processed`

✅ **Validation patterns work** - You still use `WP_Error` objects and validation hooks

✅ **Order meta data works** - Same methods to read/write order metadata

✅ **Payment gateway integration** - Core concepts remain (though implementation differs)

✅ **Shipping methods** - Registration and calculation unchanged

## What Changes?

❌ **No direct template modification** - Use blocks, slots, or additional fields instead

❌ **No `$_POST` access** - Data comes through Store API and CheckoutFields service

❌ **Different field registration** - Use `woocommerce_register_additional_checkout_field()`

❌ **JavaScript required for advanced UI** - React components instead of PHP templates

❌ **Different hooks for checkout flow** - Block-specific actions and filters

## Decision Tree: Should You Use Checkout Blocks?

### Use Checkout Blocks If:

✅ You're starting a new project

✅ You want modern, real-time validation UX

✅ Store owners need visual customization flexibility

✅ You're building for WooCommerce 8.3+

✅ Your extensions primarily add fields or modify data

✅ You want mobile-optimized checkout out of the box

### Stick with Shortcode Checkout If:

⚠️ You have heavy template customization that can't be migrated

⚠️ Your extensions deeply modify checkout HTML structure

⚠️ You need compatibility with older WooCommerce versions

⚠️ Third-party plugins you depend on don't support blocks yet

⚠️ Your development team isn't ready for React/JavaScript

### You Can Use Both

WooCommerce supports both checkout experiences simultaneously. Customers can use whichever checkout page you've set up. Many stores run both during migration periods.

## Extension Mechanisms at a Glance

Checkout blocks provide four main ways to extend functionality:

| Mechanism | Complexity | Use When | Requires JavaScript |
|-----------|-----------|----------|-------------------|
| **Additional Fields** | Low | Adding form fields | No |
| **Slot Fills** | Medium | Injecting content in predefined spots | Yes (minimal) |
| **Filters** | Medium | Modifying prices, labels, availability | Yes (minimal) |
| **Inner Blocks** | High | Custom UI components | Yes (React) |

**For most PHP developers:** Start with Additional Checkout Fields. They cover 80% of common extension needs without JavaScript.

## The Checkout Block File Structure

Understanding where things live in the codebase:

```
plugins/woocommerce/
├── src/Blocks/
│   └── Domain/Services/
│       └── CheckoutFields.php          ← PHP service for field access
│
└── client/blocks/
    ├── assets/js/blocks/checkout/
    │   ├── block.tsx                   ← Main checkout component
    │   ├── inner-blocks/               ← All inner block components
    │   │   ├── checkout-billing-address-block/
    │   │   ├── checkout-shipping-address-block/
    │   │   ├── checkout-payment-block/
    │   │   └── ...
    │   └── ...
    │
    └── packages/checkout/
        ├── blocks-registry/            ← Block registration system
        ├── slot/                       ← Slot/Fill implementation
        ├── filter-registry/            ← Filter system
        └── components/                 ← Shared components
```

## Data Flow Example: Adding a Phone Number

Let's trace how a simple field works through the system:

### 1. Registration (PHP)
```php
woocommerce_register_additional_checkout_field(array(
    'id' => 'myshop/phone',
    'label' => 'Phone Number',
    'location' => 'contact'
));
```

### 2. Rendering (Automatic)
- Checkout block detects registered field
- Renders input in contact section
- Applies styling automatically

### 3. User Input (Browser)
- Customer types: "(555) 123-4567"
- React updates data store: `{extensionData: {'myshop/phone': '(555) 123-4567'}}`
- Data store triggers sync

### 4. Sync to Server (Store API)
- PUT request to `/wc/store/v1/checkout`
- Body: `{additional_fields: {'myshop/phone': '(555) 123-4567'}}`

### 5. Processing (PHP)
```php
// Your sanitization hook runs
add_filter('woocommerce_sanitize_additional_field', function($value, $key) {
    if ($key === 'myshop/phone') {
        return filter_var($value, FILTER_SANITIZE_NUMBER_INT); // Returns: "5551234567"
    }
    return $value;
}, 10, 2);

// Your validation hook runs
add_filter('woocommerce_validate_additional_field', function($errors, $key, $value) {
    if ($key === 'myshop/phone' && strlen($value) !== 10) {
        $errors->add('invalid_phone', 'Phone must be 10 digits');
    }
    return $errors;
}, 10, 3);
```

### 6. Response (Store API → Browser)
- Validation passes: UI shows checkmark
- Validation fails: UI shows error message

### 7. Order Creation
- Customer clicks "Place Order"
- Field value automatically saved to order meta
- Access later via `CheckoutFields::get_field_from_object()`

**All of this happens automatically** once you register the field. No JavaScript, no templates, no manual saving.

## Common Misconceptions

### "I need to learn React to extend checkout blocks"

**False** - Additional checkout fields require only PHP. 80% of extensions don't need JavaScript.

### "My existing hooks won't work"

**Partially false** - Order processing hooks work fine. Template hooks don't apply, but there are block equivalents.

### "Blocks are slower than shortcodes"

**False** - Blocks are generally faster due to client-side validation and optimized rendering. Initial load might be slightly larger due to JavaScript, but interactions are much faster.

### "I can't customize the appearance"

**False** - Block editor provides visual customization, and you can add custom CSS. More flexibility than shortcodes in many ways.

### "Store API means extra server requests"

**True, but optimized** - Yes, there are more requests, but they're smaller, batched when possible, and create better UX. The trade-off is worth it.

## What You've Learned

✓ **The paradigm shift**: Templates → Components, Submission → Synchronization

✓ **The three core concepts**: Components, State, Store API

✓ **What stays the same**: Order processing, validation patterns, core hooks

✓ **What changes**: Field registration, data access, template approach

✓ **Decision factors**: When to use blocks vs shortcodes

✓ **Extension options**: Four mechanisms from simple to advanced

## Quick Debugging Tips

If you encounter issues while learning checkout blocks:

### Verify You're Using Checkout Blocks

```html
<!-- View page source and look for: -->
<div class="wp-block-woocommerce-checkout">  <!-- ✓ Blocks -->
<!-- NOT -->
[woocommerce_checkout]  <!-- ✗ Shortcode -->
```

Or check: **WooCommerce → Settings → Advanced → Checkout page** should say "Checkout" (not contain a shortcode).

### Check WooCommerce Version

Go to **WooCommerce → Status** and verify:
- WooCommerce version is 8.3 or higher
- No critical errors are showing

### Enable Debug Mode (If Needed)

Add to `wp-config.php` before "stop editing":

```php
define( 'WP_DEBUG', true );
define( 'WP_DEBUG_LOG', true );
define( 'WP_DEBUG_DISPLAY', false );
```

Errors will log to `wp-content/debug.log`.

### Browser Console Check

Press F12 → Console tab. If you see JavaScript errors (red text), they may prevent checkout blocks from working correctly.

### Need More Help?

- Each document in this series has detailed troubleshooting sections
- Document 2 includes step-by-step debugging procedures
- Document 4 has a comprehensive troubleshooting reference

---

## Next Steps

### Ready to Build?

→ **[Your First Checkout Extension](./02-your-first-checkout-extension.md)** - Add a custom field in 10 minutes

### Want More Context?

→ **[WooCommerce Blocks Overview](https://woocommerce.com/document/cart-checkout-blocks-support-status/)** - Official documentation

### Need to Deep Dive?

→ **[Checkout Blocks API Reference](./04-checkout-blocks-api-reference.md)** - Complete technical reference

---

**You now understand the foundation.** The checkout block architecture might seem different, but it's designed to be more powerful and flexible while maintaining compatibility with your PHP expertise. Let's build something!
