# Variable products in the product abilities

Status: prototype on `wooai/variable-products`, built on the custom product types spike (PR 70050). Item 3 of the extensible abilities plan for WooCommerce 11.4.

## What a client can do

| Task | Call |
| --- | --- |
| Create a variable product with its variations | `product-create` with `type: "variable"`, `attributes` and `variations[]` |
| Change one variation | `product-update` with the variation id, for price, stock and the other fields. Its options change through the parent's `variations[]`, because the parent path checks the options against the other variations. |
| Change, add or delete many variations at once | `product-update` on the parent with `variations[]` |
| Add one variation to an existing product | `product-create` with `type: "variation"`, `parent_id` and `attributes` |
| Read or list variations | `products-query` with a variation id, or with `parent_id` |
| Delete one variation | `product-delete` with the variation id |

A variation is a product with `type: "variation"`. Its class extends `WC_Product_Simple`, so it takes price, stock, `virtual` and `downloadable` like a simple product, plus `attributes` on create. A variable parent takes the common fields, `attributes` and `variations`. The output of every product carries `parent_id` and `attributes`, and a variable parent carries `variation_ids`. An update needs no `type`: `product-update` loads the product and refuses a field that its type does not take, such as `variations` on a simple product. Its `attributes` property takes the parent shape only, and a direct update of a variation refuses `attributes` with `woocommerce_product_field_unsupported`.

### Create

```json
{
  "name": "Shirt",
  "type": "variable",
  "attributes": [ { "name": "Size", "options": [ "S", "M" ] } ],
  "variations": [
    { "attributes": [ { "name": "Size", "option": "S" } ], "sku": "shirt-s", "regular_price": "10.00" },
    { "attributes": [ { "name": "Size", "option": "M" } ], "sku": "shirt-m", "regular_price": "12.00",
      "extensions": { "subscriptions": { "period": "month" } } }
  ]
}
```

An attribute whose `name` or `id` matches a global attribute, such as `pa_size`, becomes a taxonomy attribute with the term ids of its options, as the REST API builds it, and the save creates missing terms. A variation picks a global attribute's option by term slug, and the output gives each attribute its global `id`, or 0 for a local attribute.

Each variation picks one option of every attribute. The call refuses a variation that picks an option the parent does not have, two variations with the same options, and two variations with the same SKU. Each `variations[]` item accepts the `extensions` input of the product resource, so extension fields and validators run on every variation.

### Bulk update through the parent

```json
{
  "id": 123,
  "variations": [
    { "id": 124, "regular_price": "8.00" },
    { "attributes": [ { "name": "Size", "option": "L" } ], "regular_price": "15.00" },
    { "id": 125, "delete": true }
  ]
}
```

An item with an `id` changes that variation. An item without one creates a variation. `delete` removes one. Variations the input does not name stay as they are. The whole list saves in one transaction with one parent sync, so a price change across fifty variations lands whole or not at all.

## How it works

1. `ActionableAbility` runs load, change, extension fields, validators, then save. The change step builds everything in memory and saves nothing.
2. The object that `load()` returns is the primary object. An ability that saves more objects returns them from `change()`: each secondary object with its `extensions` input and, when it differs from the ability's resource, the name of its resource. The pipeline runs the extension fields and the validators of each object's own resource before the first save. For a variable product the secondary objects are the variations the input names.
3. `save()` runs inside `wc_transaction_query( 'start' )`. An error or an exception from `save()` rolls the transaction back, and the pipeline drops the object caches of every touched object, because the saves filled them with rows the rollback removed. Success commits.
4. `wc_transaction_query()` counts its depth. The first start opens a transaction, a start inside it sets a savepoint, and each commit or rollback closes its own level. An ability inside checkout, or checkout inside an ability, rolls back only its own part.
5. After the last save, still inside the transaction, the pipeline runs the syncs that extensions add with `ExtensibleAbilities::register_sync()`. A sync receives every saved object, the primary object first, and writes what the saved objects imply elsewhere. It runs once per call, and only when an object of its resource was saved. A refusal rolls the call back.
6. For a variable product, `save()` saves the parent, sets the parent id on each new variation and saves it, deletes the variations marked for deletion, then syncs the parent's price range and stock status from its children. The sync saves the parent a second time, the same way the product editor does.

The permissions of a variation are checked on its parent. A published variation of a draft parent is as hidden as the parent, and whoever may edit the parent may edit its variations.

## How the tests prove the rollback

The test bootstrap used to turn `WC_USE_TRANSACTIONS` off, because a second `START TRANSACTION` from code under test commits the transaction that `WP_UnitTestCase` wraps around each test. It now enters that transaction one time after loading WooCommerce, so every transaction in code under test is a savepoint inside it. A test makes the second variation throw during its save and reads the posts table with `$wpdb`: zero rows, and the query log shows `SAVEPOINT wc_1` followed by `ROLLBACK TO SAVEPOINT wc_1`.

Every `wc_transaction_query()` caller must stay balanced. One commit without a start drops the depth to zero, and the test framework's transaction guard then fails every later test. `WC_Tests_Core_Functions::test_wc_transaction_query` had such a cleanup commit and now commits directly.

## What Wholesale Suite told us

Wholesale Suite (`woocommerce-wholesale-prices` on WordPress.org, 20,000 installs) stores a wholesale price per role on each variation, and derives a flag, a min and max price and a list of priced variations on the parent. Its REST layer subclasses the Core variations controller, and its own `set-wholesale-price` ability accepts `simple` and `variation` only, with "set it on each variation" for a variable parent. Three things followed from reading it:

- **Addressing variations one by one is the model they chose.** It matches `product-update` with a variation id.
- **Bulk writes need one call.** A comment in their `sync_variable_parent()` reports 10,000 product hydrations for pricing 100 variations one call at a time, because every write rebuilds the parent, while the editor pays once per submit. That is why the parent update takes `variations[]`.
- **Some writes touch two objects.** Their variation write also rewrites the parent's derived meta. An `update_callback` changes one object in memory, so the second write needs a step of its own: a sync that `ExtensibleAbilities::register_sync()` adds. It runs once per call, after every object of the call is saved and before the transaction commits, with every saved object and the ability. A sync that returns a `WP_Error` or throws refuses the call, and every save rolls back. For a bulk update of fifty variations the sync runs once, so the parent syncs one time, which is the editor's cost model. The `register_field()` docblock points to it.

## Open items

- **The bulk update response lists ids.** A variable parent returns `variation_ids`, so a client that changed fifty prices reads them back with `products-query` and `parent_id`. Embedding the changed variations in the response is a small addition if clients need it.
- **Limits of the transaction.** A plugin that opens a transaction with a raw query is outside the depth counter. A `commit` without a `start` from a plugin shifts the depth for the rest of the request. MyISAM tables ignore the transaction. A read replica can make the variation data store miss a parent that the primary has not replicated yet. Side effects of save hooks outside the database are not undone. These are documented on `ActionableAbility` and are the same trade as checkout.
- **Attributes are local to the call.** Changing the parent's attributes does not revalidate existing variations that the input does not name.
