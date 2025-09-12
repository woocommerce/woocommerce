# Customize Store Tests - Iframe Loading Issue

## Status: SKIPPED
All 46 Customize Store tests are currently skipped in the QIT environment.

## Problem
The Customize Store feature fails to load properly in the QIT environment. When clicking "Start designing", the loading screen gets stuck at "Opening the doors" and the iframe never loads the assembler interface.

## Root Cause
The iframe at `/wp-admin/admin.php?page=wc-admin&path=%2Fcustomize-store%2Fassembler-hub` shows the wrong content - it displays the initial Customize Store page instead of the assembler interface, creating an infinite loop.

## Investigation Findings

### What Works
- Tests pass successfully in GitHub Actions CI environment
- WooCommerce build assets are correct and complete (customize-store.js exists)
- Beta Tester plugin is installed and built
- Theme (twentytwentyfour) is correctly activated

### What Doesn't Work
- Iframe content loads incorrectly in QIT
- Loading screen progression stops at "Opening the doors"
- The "Finish customizing" button never appears

## Temporary Solution
Tests are skipped via `playwright.config.js`:
```javascript
testIgnore: [
    '**/api-tests/**',
    '**/tests/customize-store/**/*.spec.js' // QIT-SKIP: Customize Store tests fail due to iframe loading issues
]
```

## Affected Tests (46 total)
- assembler/color-picker.spec.js (5 tests)
- assembler/font-picker.spec.js (5 tests)
- assembler/footer.spec.js (4 tests)
- assembler/full-composability.spec.js (9 tests)
- assembler/header.spec.js (4 tests)
- assembler/homepage.spec.js (4 tests)
- assembler/logo-picker/logo-picker.spec.js (8 tests)
- assembler-hub.spec.js (3 tests)
- intro.spec.js (2 tests)
- loading-screen/loading-screen.spec.js (1 test)
- transitional.spec.js (2 tests)

## Next Steps
1. Investigate why the iframe loads different content in QIT vs CI
2. Check if there are any QIT-specific security or iframe policies
3. Consider if this is related to the site URL structure (Cloudflare tunnel vs localhost)
4. Review if there are any missing server-side configurations

## Related Files
- `/tests/e2e/tests/customize-store/**/*.spec.js` - All test files
- `/tests/e2e/tests/customize-store/assembler/assembler.page.js` - Page object with waitForLoadingScreenFinish method
- `/tests/e2e/playwright.config.js` - Configuration with testIgnore pattern