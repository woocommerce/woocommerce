# TODO Documentation

This directory contains documentation for test issues, skipped tests, and other TODO items for the WooCommerce E2E test suite.

## TODO Comment Format

When skipping tests or adding workarounds, use the following standardized comment format to make them easily searchable:

```javascript
// TODO: [QIT-SKIP] Brief description of why test is skipped
// Expected: <expected behavior>
// Actual: <actual behavior>  
// See: todo/<relevant-doc>.md for full details
// Date: YYYY-MM-DD
```

### TODO Tags

- `[QIT-SKIP]` - Tests skipped specifically for QIT environment compatibility
- `[FLAKY]` - Tests that are intermittently failing
- `[DEPRECATED]` - Tests for deprecated functionality
- `[WORKAROUND]` - Temporary workarounds that need to be removed
- `[INVESTIGATE]` - Issues that need further investigation

## Searching for TODOs

To find all skipped tests or workarounds:

```bash
# Find all QIT-related skips
grep -r "TODO: \[QIT-SKIP\]" ../tests/

# Find all TODOs
grep -r "TODO: \[" ../tests/

# Find all test.skip() calls
grep -r "test.skip()" ../tests/
```

## Current TODO Items

1. **js-file-monitor.md** - Cart and Checkout JS file count mismatches (2025-09-12)
   - Tags: `[QIT-SKIP]`
   - Tests expecting 54 JS files but getting 57 due to new WP/WC features