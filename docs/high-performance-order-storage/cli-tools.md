---
post_title: HPOS CLI Tools
tags: reference
---
## Overview

With HPOS we introduced a set of [WP-CLI commands](https://developer.woocommerce.com/docs/category/wc-cli/) living under the `wp wc hpos` and `wp wc cot` namespaces.

The following table provides an overview of what each command does, while more details and examples can be found below.

Keep in mind that the commands themselves have documentation and examples that can be accessed via WP-CLI's help by passing the `--help` flag.

|Command|Use this command to...|
|----|-----|
|`wc hpos status`|Get an overview of all HPOS matters on your site.|
|`wc cot enable`|Enable HPOS (and possibly compatibility mode).|
|`wc cot disable`|Disable HPOS (and possibly compatibility mode).|
|`wc cot count_unmigrated`|Get a count of all orders pending sync.|
|`wc cot sync`|Performantly sync orders from the currently active order storage to the other.|
|`wc cot verify_cot_data`|Verify data between datastores.|
|`wc hpos diff`|Get an user friendly version of the differences for an order between both order storages.|
|`wc hpos backfill`|Copy over whole orders or specific bits of order data from any order storage to the other.|
|`wc hpos cleanup`|Remove order data from legacy tables.|

## Usage and examples

### `wc hpos status`

Use this to get an overview of HPOS settings and status on your site. The command will output whether HPOS and compatibility mode are enabled or not, and other useful information such as orders pending sync or subject to cleanup.

**Note:** Remember that, if desired, orders pending sync can be synced using [`wc cot sync`](#wc-cot-sync) and, similarly, you can perform a cleanup on those subject to cleanup (provided compatibility mode is disabled) by running [`wc hpos cleanup all`](#wc-hpos-cleanup).


#### Example 1 - HPOS status output

```plaintext
$ wp wc hpos status
HPOS enabled?: yes
Compatibility mode enabled?: no
Unsynced orders: 651
Orders subject to cleanup: 348
```

### `wc cot enable`

Use this command to enable HPOS and compatibility mode (if desired) from the command line.

#### Example 1 - Enable HPOS via CLI

Enables HPOS and compatibility mode too (`--with-sync` flag).

```plaintext
$ wp wc cot enable --with-sync
Running pre-enable checks...
Success: Sync enabled.
Success: HPOS enabled.
```

### `wc cot disable`

Similarly to the prior command, this can be used to disable HPOS.

#### Example 1 - Attempt to disable HPOS (with orders pending sync)

If there are any orders pending sync, you won't be allowed to disable HPOS until those orders have been synced (via `wp wc cot sync`).

```plaintext
$ wp wc cot disable
Running pre-disable checks...
Error: [Failed] There are orders pending sync. Please run `wp wc cot sync` to sync pending orders.
```

#### Example 2 - Disable HPOS

```plaintext
$ wp wc cot disable
Running pre-disable checks...
Success: HPOS disabled.
```

### `wc cot count_unmigrated`

Prints the number of orders pending sync.

#### Example 1 - Obtain number of orders pending sync

```plaintext
$ wp wc cot count_unmigrated
There are 651 orders to be synced.
```

### `wc cot sync`

This command can be used to migrate orders from the posts order storage to HPOS (or viceversa) based on the current settings in WC > Settings > Advanced > Features.
That is, it'll sync orders from your currently selected datastore to the other one.

Note that enabling compatibility mode in the settings will eventually take care of migrating all orders, but this command can be used to do that more performantly.

If you need more control over which datastore to use as source (or destination) regardless of settings, or want to migrate just a few orders or properties, use [`wp wc hpos backfill`](#wc-hpos-backfill) instead.

#### Example 1 - Sync all orders

```plaintext
$ wp wc cot sync
There are 999 orders to be synced.
Order Data Sync  100% [============================================================================================] 0:08 / 0:08
Sync completed.
Success: 999 orders were synced in 14 seconds.
```


### `wc cot verify_cot_data`

Use this command to check that order data in both the legacy (posts) datastore and HPOS is in sync. This is only relevant if you have "compatibility mode" enabled and orders might've been modified outside of the usual WooCommerce flows.

This command operates on all orders. For a user friendlier alternative that operates on individual orders, refer to [`wp wc hpos diff`](#wc-hpos-diff).

#### Example 1 - Verify data on a migrated site

All orders are identical between datastores.

```plaintext
$ wp wc cot verify_cot_data
Order Data Verification  100% [====================================================================================] 0:00 / 0:00
Verification completed.
Success: 999 orders were verified in 0 seconds.
```

#### Example 2 - Verification failures

An order (with ID 100126) fails verification due to differences in order total, tax, modification date and billing information.

```plaintext
$ wp wc cot verify_cot_data
Order Data Verification  100% [====================================================================================] 0:00 / 0:00
Verification completed.
Error: 999 orders were verified in 0 seconds. 1 error found: {
    "100126": [
        {
            "column": "post_modified_gmt",
            "original_value": "2024-04-04 15:32:27",
            "new_value": "2024-04-05 15:19:56"
        },
        {
            "column": "_order_tax",
            "original_value": "74",
            "new_value": "0"
        },
        {
            "column": "_order_total",
            "original_value": "567.25",
            "new_value": "0"
        },
        {
            "order_id": 100126,
            "meta_key": "_billing_address_index",
            "orig_meta_values": [
                "Hans Howell Moore Ltd 325 Ross Drive  Wilfridhaven WA 23322 NF heidi.koch@example.net +17269674166"
            ],
            "new_meta_values": [
                "Hans X Howell Moore Ltd 325 Ross Drive  Wilfridhaven WA 23322 NF heidi.koch@example.net +17269674166"
            ]
        }
    ]
}. Please review the error above.
```

#### Example 3 - Re-migrate during verification

The verification command also admits a `--re-migrate` flag that will attempt to sync orders that have differences. This could effectively overwrite an order in the database, so use with care.

```plaintext
$ wp wc cot verify_cot_data --re-migrate
Order Data Verification  100% [====================================================================================] 0:00 / 0:00
Verification completed.
Success: 999 orders were verified in 0 seconds.
```

### `wc hpos diff`

If you have enabled compatibility mode or migrated orders using `wp wc cot sync`, all of your orders should be in an identical state in both datastores (the legacy one and HPOS), but errors can happen. Also, manually modifying orders in the database or use of HPOS-incompatible plugins can result in orders deviating.

The `wp wc hpos diff`  tool can be used to look into those (possible) differences, which can be useful to determine whether re-migrating to/from either datastore should be done, or a more careful approach needs to be taken.

The tool itself doesn't reconcile the differences. For that you should use [`wp wc hpos backfill`](#wc-hpos-backfill).

#### Example 1 - No difference between orders

Order is the same in both datastores (legacy and HPOS).

```plaintext
$ wp wc hpos diff 100087
Success: No differences found.
```

#### Example 2 - Mismatch in order properties between datastores

This examples shows that order `100126`  differs in various fields between both datastores. For example, its HPOS version has status `completed` while the post is still in `pending` status. Similarly, there are differences in other fields and there's even some metadata (`post_only_meta`) that only exists in the post/legacy version.

Any other order fields or metadata not listed are understood to be equal in both order versions.

```plaintext
$ wp wc hpos diff 100126
Warning: Differences found for order 100126:
+--------------------+---------------------------+---------------------------+
| property           | hpos                      | post                      |
+--------------------+---------------------------+---------------------------+
| status             | completed                 | pending                   |
| total              | 567.25                    | 267.25                    |
| date_modified      | 2024-04-04T15:32:27+00:00 | 2024-04-04T19:00:26+00:00 |
| billing_first_name | Hans                      | Jans                      |
| post_only_meta     |                           | why not?                  |
+--------------------+---------------------------+---------------------------+
```

#### Example 3 - JSON output

You can also get the output in various formats (`json`, `csv` or `list` -the default-), which can be useful for exporting differences from various orders to a file.

```plaintext
$ wp wc hpos diff 100126 --format=json
Warning: Differences found for order 100126:
[{"property":"status","hpos":"completed","post":"pending"},{"property":"total","hpos":"567.25","post":"267.25"},{"property":"date_modified","hpos":"2024-04-04T15:32:27+00:00","post":"2024-04-04T19:00:26+00:00"},{"property":"billing_first_name","hpos":"Hans","post":"Jans"},{"property":"post_only_meta","hpos":"","post":"why not?"}]
```

### `wc hpos backfill`

The backfill command can be used to selectively migrate order data (or whole orders) from either the legacy or HPOS datastore to the other one. It's very useful to reconcile sync or migration mishaps.

The exact syntax for this command is as follows:

```plaintext
wp wc hpos backfill <order_id> --from=<datastore> --to=<datastore> [--meta_keys=<meta_keys>] [--props=<props>]
```

You have to specify which datastore to use as source (either `posts` or `hpos`) and which one to use as destination. The `--meta_keys` and `--props` arguments receive a comma separated list of meta keys and order properties, which can be used to move only certain data from one datastore to the other, instead of the whole order.

Note that `wp wc hpos backfill` differs from `wp wc cot sync` in various ways:

- You can specify which order to operate on, which gives you more control for one-off operations.
- It lets you move order data between datastores irrespective of what the current order storage is in WC settings. In contrast, `wp wc cot sync` will only sync data from the current datastore to the other.
- In addition to letting you migrate full orders, it lets you choose which bits of data (order fields or metadata) to migrate.

#### Example 1 - Migrate a full order from HPOS to posts

```plaintext
$ wp wc hpos backfill 99709
Success: Order 99709 backfilled from hpos to posts.
```

#### Example 2 - Migrate metadata

Continuing with example 2 from the previous section, we can see how to migrate just one key of metadata from posts to HPOS.

```plaintext
$ wp wc hpos backfill 100126 --from=posts --to=hpos --meta_keys=post_only_meta
Success: Order 100126 backfilled from posts to hpos.
```

If you now run `wp wc hpos diff` on this order, you can see that the bit of metadata is no longer listed as a difference.

```plaintext
$ wp wc hpos diff 100126
Warning: Differences found for order 100126:
+--------------------+---------------------------+---------------------------+
| property           | hpos                      | post                      |
+--------------------+---------------------------+---------------------------+
| status             | completed                 | pending                   |
| total              | 567.25                    | 267.25                    |
| date_modified      | 2024-04-04T15:32:27+00:00 | 2024-04-04T19:00:26+00:00 |
| billing_first_name | Hans                      | Jans                      |
+--------------------+---------------------------+---------------------------+
```

#### Example 3

Also following the previous example, we can now reconcile all the data as we see fit. For example, we can migrate the status from posts to HPOS and the other bits of info in the other direction.
In the end, the orders will be identical, as can be confirmed through `wp wc hpos diff`.

1. Sync order status from posts to HPOS. This means the order will become "pending" in both datastores.

   ```plaintext
   $ wp wc hpos backfill 100126 --from=posts --to=hpos --props=status
   Success: Order 100126 backfilled from posts to hpos.
   ```

2. Sync the other properties from HPOS to posts.

   ```plaintext
   $ wp wc hpos backfill 100126 --from=hpos --to=posts --props=total,date_modified,billing_first_name
   Success: Order 100126 backfilled from hpos to posts.
   ```

3. Are we all sync'ed yet?

   ```plaintext
   $ wp wc hpos diff 100126
   Success: No differences found.
   ```

### `wc hpos cleanup`

The cleanup command can be used to remove order data from legacy tables when HPOS is enabled and compatibility mode is disabled.

Given this is a destructive operation, the tool won't do anything by default. You'll have to specify an order ID, a range of order IDs or `all` to operate on all orders.

The tool will also verify orders before removal, stopping if the post version seems more recent than the HPOS one. This allows closer inspection of those differences (for example, with `wp wc hpos diff`) and reconciling the data (with [`wp wc hpos backfill`](#wc-hpos-backfill)) before the deletion is executed.

**Note:** This command won't remove placeholder records (posts with type `shop_order_placehold`) from the posts table. We're working on allowing this in the near future, but for now leave placeholders so that datastores can be switched if necessary. Metadata is removed, which is where most data is stored in the legacy order storage, so the remaining placeholder post is very lightweight.

#### Example 1 - Error during cleanup

Cleaning up of an order that seems more recent on the posts datastore is prevented by default.

```plaintext
$ wp wc hpos cleanup 100126
Starting cleanup for 1 order...
Warning: An error occurred while cleaning up order 100126: Data in posts table appears to be more recent than in HPOS tables.
```

You can investigate the differences with `wp wc hpos diff`:

```plaintext
$ wp wc hpos diff 100126
Warning: Differences found for order 100126:
+---------------+---------------------------+---------------------------+
| property      | hpos                      | post                      |
+---------------+---------------------------+---------------------------+
| date_modified | 2024-04-05T15:19:56+00:00 | 2024-04-05T16:39:26+00:00 |
+---------------+---------------------------+---------------------------+
```

If reconciling is not necessary, the `--force` flag can be used to skip the verification checks:

```plaintext
$ wp wc hpos cleanup 100126 --force
Starting cleanup for 1 order...
HPOS cleanup  100% [=====================================================================================================================] 0:00 / 0:00
Success: Cleanup completed for 1 order.
```

#### Example 2 - Cleaning up a range of order IDs

```plaintext
$ wp wc hpos cleanup 90000-100000
Starting cleanup for 865 orders...
HPOS cleanup  100% [=====================================================================================================================] 0:01 / 0:12
Success: Cleanup completed for 865 orders.
```

#### Example 3 -Cleaning up all orders

```plaintext
$ wp wc hpos cleanup all
Starting cleanup for 999 orders...
HPOS cleanup  100% [=====================================================================================================================] 0:01 / 0:05
Success: Cleanup completed for 999 orders.
```

