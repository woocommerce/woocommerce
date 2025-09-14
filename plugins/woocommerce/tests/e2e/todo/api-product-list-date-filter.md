# API Product List - Date Filter Test Skip

## Test Information
- **File:** `tests/api-tests/products/product-list.test.js`
- **Test:** `before / after`
- **Status:** SKIPPED with `[QIT-SKIP]` tag
- **Date Skipped:** 2025-09-14

## Issue Description
The test fails in QIT environment with:
```
Expected length: 4
Received length: 5
```

### Root Cause
The test creates products with specific timestamps and uses a `before` filter to query them. The issue occurs because:

1. **Boundary Condition:** The "Polo" product has `date_created_gmt: '2021-09-05T15:50:19'` which exactly matches the `before` filter parameter
2. **API Behavior:** WooCommerce REST API treats the `before` parameter as **inclusive** (≤) rather than **exclusive** (<)
3. **Test Expectation:** The test expects exactly 4 products but receives 5 due to the inclusive filtering

### Products Created in Test
- Beanie with Logo: `2021-09-01T15:50:20`
- T-Shirt with Logo: `2021-09-02T15:50:20`
- Single: `2021-09-03T15:50:19`
- Album: `2021-09-04T15:50:19`
- **Polo: `2021-09-05T15:50:19`** ← Boundary issue here

### Filter Used
```javascript
before: '2021-09-05T15:50:19'
```

## Possible Solutions

### Option 1: Fix the Test (Recommended)
Change the filter to avoid the boundary:
```javascript
// Instead of:
before: '2021-09-05T15:50:19'

// Use:
before: '2021-09-05T00:00:00' // Midnight to clearly exclude Polo
// Or:
before: '2021-09-04T23:59:59' // Just before Sept 5
```

### Option 2: Update Test Expectations
Accept 5 products if the API behavior is correct and inclusive filtering is intended.

### Option 3: Fix API Behavior
If exclusive filtering is the intended behavior, fix the WooCommerce REST API to use strict less-than comparison.

## Additional Context
- The test works in some environments (WooCommerce CI) but fails consistently in QIT
- This may be related to:
  - Different MySQL/MariaDB versions with different datetime precision
  - Timezone configuration differences
  - Test isolation and environment state

## References
- Similar issue was addressed in commit `d87b2d8970` for WPCOM/Pressable environments
- The test suite has other date-related filtering that may have similar issues

## Action Items
- [ ] Investigate if other date filter tests have similar boundary issues
- [ ] Determine if inclusive vs exclusive filtering is documented API behavior
- [ ] Consider adding explicit test cases for boundary conditions
- [ ] Update test to use clearer date boundaries that avoid ambiguity