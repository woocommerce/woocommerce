# Customize Store Test Failures Investigation

## Problem
46 Customize Store tests are failing with timeout errors when waiting for the "Finish customizing" button to appear.

## Root Cause Analysis

### Issue 1: Tests Pass in CI but Not in QIT
The Customize Store tests pass successfully in the GitHub Actions CI environment (e2e-pw) but fail in the QIT environment. Looking at the CI logs, all Customize Store tests pass when run with the proper setup.

**CI Success Evidence:**
- Tests pass with twentytwentyfour theme active
- Proper setup includes hiding the onboarding tour
- Tests run after complete site setup

### Issue 2: Loading Screen Gets Stuck in QIT
The Customize Store feature shows a loading screen with "Opening the doors" message that never completes loading when accessed through the store designer path in QIT.

**Technical Details:**
- The test waits for `.cys-fullscreen-iframe[style="opacity: 1;"]` to appear
- The iframe does exist and becomes visible (opacity: 1)
- However, the iframe content never fully loads - it remains stuck on the loading screen
- The "Finish customizing" button never appears because the assembler interface doesn't load

### Issue 3: Theme Requirement
The tests require `twentytwentyfour` theme to be active, which has been fixed in setup.sh.

## Findings

1. **Iframe Loading Issue**
   - URL Path: `/wp-admin/admin.php?page=wc-admin&path=%2Fcustomize-store%2Fdesign`
   - The iframe with class `cys-fullscreen-iframe` loads but content remains stuck
   - Loading indicators remain active indefinitely
   - No JavaScript errors in console that would indicate the cause

2. **Test Expectations**
   - Tests expect to find iframe with selector: `.cys-fullscreen-iframe[style="opacity: 1;"]`
   - Then wait for button with text "Finish customizing" inside that iframe
   - This button should appear within 25 seconds according to test timeout

3. **Environment State**
   - Active theme: twentytwentythree (should be twentytwentyfour)
   - WooCommerce version: 10.3.0-dev
   - WordPress version: 6.8.2

## Potential Fixes

### Fix 1: Activate Required Theme in Setup
The tests require `twentytwentyfour` theme but setup activates `twentytwentythree`.

**File**: `/storage/automattic/woocommerce/plugins/woocommerce/tests/e2e/bootstrap/setup.sh`
```bash
# Change line 48 from:
wp theme activate twentytwentythree --allow-root

# To:
wp theme activate twentytwentyfour --allow-root
```

### Fix 2: Add Customize Store Workaround
The loading screen issue might be related to the known Customize Store issues mentioned in the setup (GitHub #44766).

The workaround file `customize-store-workaround.php` is referenced but might not be properly set up or might need updates.

### Fix 3: Skip Tests Temporarily
Add `test.skip` to the failing Customize Store tests until the underlying issue is resolved.

```javascript
test.skip('test name here', async ({ page }) => {
    // QIT-SKIP: Customize Store loading screen gets stuck
    // TODO: Investigate why the assembler interface doesn't load properly
});
```

## Recommended Actions

1. **Immediate**: Skip the failing tests with proper documentation
2. **Short-term**: Fix the theme activation in setup.sh
3. **Long-term**: Investigate why the Customize Store loading screen gets stuck in the QIT environment

## Related Files
- Test files: `/tests/e2e/tests/customize-store/**/*.spec.js`
- Setup script: `/tests/e2e/bootstrap/setup.sh`
- Test helper: `/tests/e2e/tests/customize-store/assembler/assembler.page.js`

## Additional Notes
- The issue appears to be environment-specific to QIT
- The same tests likely work in the original e2e-pw environment
- The loading screen "Opening the doors" suggests an initialization issue