# Diagnostic Tests for WooCommerce Duplicate Meta Issue

## The Problem
When using `include('temp.php')` in WP Shell, calling `add_meta_data()` once creates 3 identical meta entries.
When running the same code directly in WP Shell (without include), it works correctly.

## Tests to Run

### Test 1: Compare execution methods

**Method A - Direct in WP Shell (WORKS):**
```bash
wp shell
>>> $orders = wc_get_orders(['limit'=>1,'type'=>'shop_order']);
>>> $orders[0]->add_meta_data('_test_direct', 'value');
>>> $orders[0]->save();
>>> exit
```

**Method B - Using include in WP Shell (FAILS - creates 3 entries):**
```bash
wp shell
>>> include('/workspace/temp.php');
>>> exit
```

**Method C - Using wp eval-file (Test this):**
```bash
wp eval-file /workspace/temp-eval-test.php
```

### Test 2: Trace database operations
```bash
wp shell
>>> include('/workspace/temp-check-context.php');
```

Check the output - it will show EVERY time the meta is inserted into the database with a full backtrace.

### Test 3: Simplest single order test
```bash
wp shell
>>> include('/workspace/temp-single-order-test.php');
```

This uses only 1 order and traces before/after counts.

### Test 4: Full trace with query monitoring
```bash
wp shell
>>> include('/workspace/temp-trace-save.php');
```

## What to Look For

The key question is: **Why does `include()` cause different behavior than direct execution?**

Possible causes:
1. The file is being evaluated multiple times somehow
2. There's output buffering or some WP Shell mechanism that reruns code
3. There's a shutdown hook or deferred save mechanism
4. WP Shell's readline/history is somehow re-executing the include

## Next Steps

Please run the tests above and share:
1. The output from `temp-check-context.php` - especially the backtrace
2. The count from `temp-single-order-test.php`
3. Whether `wp eval-file` has the same problem

This will tell us exactly where the duplication is happening.
