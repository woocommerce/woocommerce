# Variable products in the product abilities

Status: prototype on `wooai/variable-products`, built on the custom product types spike (PR 70050). Item 3 of the extensible abilities plan for WooCommerce 11.4.

## What a client can do

| Task | Call |
| --- | --- |
| Create a variable product with its variations | `product-create` with `type: "variable"`, `attributes` and `variations[]` |
| Change one variation | `product-update` with the variation id and `type: "variation"` |
| Change, add or delete many variations at once | `product-update` on the parent with `type: "variable"` and `variations[]` |
| Add one variation to an existing product | `product-create` with `type: "variation"`, `parent_id` and `attributes` |
| Read or list variations | `products-query` with a variation id, or with `parent_id` |
| Delete one variation | `product-delete` with the variation id |

A variation is a product with `type: "variation"`. Its class extends `WC_Product_Simple`, so it takes price, stock, `virtual` and `downloadable` like a simple product, plus `attributes`. A variable parent takes the common fields, `attributes` and `variations`. The output of every product carries `parent_id` and `attributes`, and a variable parent carries `variation_ids`.

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

Each variation picks one option of every attribute. The call refuses a variation that picks an option the parent does not have, two variations with the same options, and two variations with the same SKU. Each `variations[]` item accepts the `extensions` input of the product resource, so extension fields and validators run on every variation.

### Bulk update through the parent

```json
{
  "id": 123,
  "type": "variable",
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
2. An ability that saves more than one object returns the extra objects from `pending_objects()`. The pipeline runs the extension fields and the validators on each of them before the first save. For a variable product these are the variations the input names.
3. `save()` runs inside `wc_transaction_query( 'start' )`. An error or an exception from `save()` rolls the transaction back, and the pipeline drops the object caches of every touched object, because the saves filled them with rows the rollback removed. Success commits.
4. `wc_transaction_query()` counts its depth. The first start opens a transaction, a start inside it sets a savepoint, and each commit or rollback closes its own level. An ability inside checkout, or checkout inside an ability, rolls back only its own part.
5. For a variable product, `save()` saves the parent, sets the parent id on each new variation and saves it, deletes the variations marked for deletion, then syncs the parent's price range and stock status from its children. The sync saves the parent a second time, the same way the product editor does.

The permissions of a variation are checked on its parent. A published variation of a draft parent is as hidden as the parent, and whoever may edit the parent may edit its variations.

## How the tests prove the rollback

The test bootstrap used to turn `WC_USE_TRANSACTIONS` off, because a second `START TRANSACTION` from code under test commits the transaction that `WP_UnitTestCase` wraps around each test. It now enters that transaction one time after loading WooCommerce, so every transaction in code under test is a savepoint inside it. A test makes the second variation throw during its save and reads the posts table with `$wpdb`: zero rows, and the query log shows `SAVEPOINT wc_1` followed by `ROLLBACK TO SAVEPOINT wc_1`.

Every `wc_transaction_query()` caller must stay balanced. One commit without a start drops the depth to zero, and the test framework's transaction guard then fails every later test. `WC_Tests_Core_Functions::test_wc_transaction_query` had such a cleanup commit and now commits directly.

## What Wholesale Suite told us

Wholesale Suite (`woocommerce-wholesale-prices` on WordPress.org, 20,000 installs) stores a wholesale price per role on each variation, and derives a flag, a min and max price and a list of priced variations on the parent. Its REST layer subclasses the Core variations controller, and its own `set-wholesale-price` ability accepts `simple` and `variation` only, with "set it on each variation" for a variable parent. Three things followed from reading it:

- **Addressing variations one by one is the model they chose.** It matches `type: "variation"` in `product-update`.
- **Bulk writes need one call.** A comment in their `sync_variable_parent()` reports 10,000 product hydrations for pricing 100 variations one call at a time, because every write rebuilds the parent, while the editor pays once per submit. That is why the parent update takes `variations[]`.
- **Some writes touch two objects.** Their variation write also rewrites the parent's derived meta. An `update_callback` changes one object in memory, so that second write belongs in a save hook of the object, such as `woocommerce_after_product_object_save`. The hook runs inside the transaction, so it rolls back with the save. The `register_field()` docblock says so.

## Open items

- **Global attributes.** The `attributes` input always builds local attributes. A global attribute is a taxonomy such as `pa_size` with shared terms, and it drives layered navigation, attribute archives and the editor dropdowns. When the name matches a registered global attribute, set the attribute as a taxonomy and resolve the options to term ids, as `WC_REST_Products_V2_Controller::prepare_attributes()` does. Variation options then compare against term slugs.
- **Updates need `type` for per-type fields.** The update schema is a `oneOf` per type, so a price change needs `type: "simple"` or `type: "variation"`, and `variations` needs `type: "variable"`, although Core knows the type after loading the product. Proposed fix: one flat update schema, and a runtime refusal of fields that do not apply to the loaded product with the existing `woocommerce_product_field_unsupported` error. This is open question 5 of the custom product types spike.
- **The bulk update response lists ids.** A variable parent returns `variation_ids`, so a client that changed fifty prices reads them back with `products-query` and `parent_id`. Embedding the changed variations in the response is a small addition if clients need it.
- **Limits of the transaction.** A plugin that opens a transaction with a raw query is outside the depth counter. A `commit` without a `start` from a plugin shifts the depth for the rest of the request. MyISAM tables ignore the transaction. A read replica can make the variation data store miss a parent that the primary has not replicated yet. Side effects of save hooks outside the database are not undone. These are documented on `ActionableAbility` and are the same trade as checkout.
- **Attributes are local to the call.** Changing the parent's attributes does not revalidate existing variations that the input does not name.
