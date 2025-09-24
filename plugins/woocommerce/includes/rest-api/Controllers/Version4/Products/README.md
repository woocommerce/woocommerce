# WooCommerce REST API v4 - Products Endpoint

This directory contains the implementation of the WooCommerce REST API v4 Products endpoint (`/wp-json/wc/v4/products`).

## Overview

The v4 Products endpoint starts as a **copy-paste of the v3 implementation** and will be incrementally enhanced with new features. This approach allows for rapid development while maintaining backward compatibility (for now) and enables easy tracking of changes through dedicated PRs.

**Current Status**: Experimental feature requiring the `rest-api-v4` feature flag.

## Architecture

- **Namespace**: `wc/v4`
- **Future Goal**: Migrate to extend WP_REST_Controller directly instead of WooCommerce base classes, and move the API codebase under the src directory with the Automattic\WooCommerce namespace.

## Development Philosophy

As discussed in the team conversation:

- **Copy-paste first**: Start with exact v3 functionality 
- **Separate PRs for changes**: Each enhancement gets its own PR for easy tracking and review
- **Incremental improvements**: Add new fields and functionality gradually
- **Future breaking changes expected**: As v4 evolves, breaking changes may be introduced for improved functionality

## Change Log

### 2025-09-08 - Add Add to Cart Field

**Summary**: Added new `add_to_cart` field to product responses providing comprehensive add-to-cart information. The field includes an object with `url`, `description`, `text`, and `single_text` properties that correspond to the product's add-to-cart methods. This enhancement improves frontend integration by providing all necessary add-to-cart data in a single API call, particularly beneficial for block-based implementations and external product types.

**Field Structure**:

```json
{
  "add_to_cart": {
    "url": "string",         // Add to cart URL
    "description": "string", // Add to cart description  
    "text": "string",        // Add to cart text
    "single_text": "string" // Add to cart single text
  }
}
```

**PR**: [#60715](https://github.com/woocommerce/woocommerce/pull/60715)  

**Breaking Changes**: None

### 2025-09-02 - Move Experimental Price Fields from v3 to v4

**Summary**: Moved experimental `min_price` and `max_price` fields from v3/products endpoint to v4/products endpoint. These fields were previously named `__experimental_min_price` and `__experimental_max_price` in v3 and are now available as `min_price` and `max_price` in v4. The fields are particularly useful for grouped products to display price ranges.
**PR**: [#60703](https://github.com/woocommerce/woocommerce/pull/60703)  

**Breaking Changes**: None (exact v3 copy)

### 2025-09-01 - Initial Implementation (Copy-paste from v3)

**Summary**: Created v4/products/ endpoint as direct copy of v3 implementation  
**PR**: [#60690](https://github.com/woocommerce/woocommerce/pull/60690)  

**Breaking Changes**: None (exact v3 copy)

---

## Future Changes Template

When adding new changes, please use this format:

### YYYY-MM-DD - Brief Change Description

**Summary**: Detailed description of what was changed  
**PR**: [#XXXXX](https://github.com/woocommerce/woocommerce/pull/XXXXX)  

**Breaking Changes**: Description of any breaking changes or "None"
