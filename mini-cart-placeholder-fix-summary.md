# Mini Cart Placeholder Image Fix - Issue #59736

## Problem
The experimental Mini Cart block (IAPI) was not showing placeholder images for products without images, while the current Mini Cart block correctly displays placeholders using `PLACEHOLDER_IMG_SRC` from `@woocommerce/settings`.

## Root Cause Analysis
The issue was in the `itemThumbnail` getter in `plugins/woocommerce/client/blocks/assets/js/blocks/mini-cart/iapi-frontend.ts`:

**Before (problematic code):**
```javascript
get itemThumbnail(): string {
    return cartItemState.cartItem.images[ 0 ]?.thumbnail || '';
},
```

When no thumbnail was available, this returned an empty string instead of the placeholder image URL.

## Solution Implemented

### 1. Added Import
Added the `PLACEHOLDER_IMG_SRC` import to the iapi-frontend.ts file:
```javascript
import { PLACEHOLDER_IMG_SRC } from '@woocommerce/settings';
```

### 2. Updated the Getter
Modified the `itemThumbnail` getter to use the placeholder:
```javascript
get itemThumbnail(): string {
    return cartItemState.cartItem.images[ 0 ]?.thumbnail || PLACEHOLDER_IMG_SRC;
},
```

### 3. Added Changelog Entry
Created `plugins/woocommerce/changelog/fix-59736` documenting the fix.

## How This Works
- `PLACEHOLDER_IMG_SRC` is made available from the backend via `AssetDataRegistry.php` 
- It contains the URL from `wc_placeholder_img_src()` which respects the site's placeholder image settings
- This follows the same pattern used by other components like `ProductImage` in the cart-checkout components

## Testing
The fix follows the established pattern used throughout the codebase for placeholder images and should automatically display placeholder images for products without thumbnails in the experimental Mini Cart block.

## Files Modified
1. `plugins/woocommerce/client/blocks/assets/js/blocks/mini-cart/iapi-frontend.ts` - Main fix
2. `plugins/woocommerce/changelog/fix-59736` - Changelog entry