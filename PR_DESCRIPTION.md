# Fix coupon cache key inconsistency causing similar codes to conflict

## Problem

When using similar coupon codes like "cat bird" (with space) and "catbird" (without space), whichever code is applied first works correctly, but the second similar code fails with a "coupon not found" error.

**Reproduction Steps:**
1. Create two coupons: "cat bird" and "catbird"  
2. Apply one coupon in checkout
3. Remove the applied code and try the second code
4. The second code fails with "Coupon Not Found" error

## Root Cause

The issue stems from inconsistent cache key generation between coupon lookup and database queries:

- **Cache keys** use raw coupon codes: `coupon_id_from_code_cat bird` vs `coupon_id_from_code_catbird`
- **Database lookups** use sanitized codes via `wc_sanitize_coupon_code()` which may normalize similar codes to the same value

This mismatch causes cache conflicts where similar codes interfere with each other.

## Solution

Ensure consistency by using the same sanitized coupon code for both cache keys and database lookups.

**Files Modified:**
- `includes/wc-coupon-functions.php`: Use sanitized code for cache keys in `wc_get_coupon_id_by_code()`
- `includes/data-stores/class-wc-coupon-data-store-cpt.php`: Update cache invalidation to use sanitized codes

## Benefits

- ✅ Fixes reported issue with similar coupon codes
- ✅ Maintains cache consistency across all coupon operations  
- ✅ No breaking changes or performance impact
- ✅ Minimal, targeted fix with proper cache invalidation

## Testing

Included test file (`test_coupon_cache_fix.php`) demonstrates the fix by simulating cache key generation and coupon lookup scenarios.

**Test scenarios:**
- Cache key uniqueness for similar codes
- Coupon lookup simulation with fixed caching behavior
- Verification that each code returns correct coupon ID

Fixes #59487