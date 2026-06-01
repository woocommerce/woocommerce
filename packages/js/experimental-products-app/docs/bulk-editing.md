# Bulk Editing

Bulk editing in the experimental products app is built on top of the quick edit drawer. The same `ProductEdit` surface handles both single-product quick edits and multi-product bulk edits. When more than one product ID is selected, the form is made bulk-aware by merging product data, showing mixed field state, filtering fields to the shared editable set, and applying changes to every selected product.

## Entry Point

The DataViews quick edit action supports bulk selection. When it runs, it writes the selected IDs and the drawer state into the URL:

```text
/products?postId=12,34&quickEdit=true
```

The relevant pieces are:

- `quickEditAction()` in `src/dataviews-actions/actions.tsx`, which sets `postId` and `quickEdit=true`.
- `useLayoutAreas()` in `src/router.tsx`, which opens `ProductEdit` when `quickEdit` is true.
- `ProductEdit` in `src/product-edit/index.tsx`, which reads `postId`, resolves the selected products, and renders the drawer.

## Product Resolution

`ProductEdit` resolves selected products from two places:

- The current product list records, including embedded variations.
- The WordPress core data store, when a selected product is not already in the list or has unsaved edits.

For variations, the drawer keeps editing tied to the parent product record. A selected variation is read from the parent product's `_embedded.variations` when an edited parent record exists. This keeps variation edits in one place before save.

## Field Selection

Bulk edit uses the normal product field registry, then narrows it to fields that can safely apply to every selected product.

`getProductEditFields()` removes fields that are display-only summaries or counts, such as `price_summary`, `inventory_summary`, and `images_count`.

`getVisibleProductEditFields()` then applies the bulk rules:

- Only fields supported by every selected product type are shown.
- Fields hidden by per-field `isVisible()` logic are shown only when every selected product passes that visibility check.
- `sku` is hidden during bulk editing because each product needs a unique value.
- Parent-owned fields, such as `name`, `categories`, `tags`, and `catalog_visibility`, are hidden when the selection includes variations.
- Sellable fields, such as prices and sale dates, are hidden when the selection includes a variable parent product.
- Cost of goods is shown only when the feature is enabled and the selected products expose the data.

The final field list is pruned into the product-type form layout by `getProductTypeFormFields()`.

## Merged Form Data

The form needs one data object even when many products are selected. `buildProductBulkEditData()` creates that object and also records per-field state.

It starts with `buildMergedProductEditData()`:

- Shared values are preserved. If every selected product has `status: "publish"`, the form gets `status: "publish"`.
- Mixed string values fall back to an empty string.
- Mixed arrays fall back to an empty array.
- Mixed `null` values stay `null`.
- Other mixed values fall back to `undefined`.

Then `buildProductBulkEditData()` adds `fieldStates` for each visible field:

- `isMixed` is true when selected products have different values.
- `isEmpty` is true when all selected products share an empty value.
- `value` contains the shared value when there is one.
- `placeholder` is `Mixed` when the field has mixed values.

This lets the UI show the current shared value when one exists, or a neutral mixed state when the selected products differ.

## Bulk-Aware Controls

`getBulkEnhancedProductEditFields()` adjusts field definitions only when more than one product is selected.

For regular fields:

- Mixed placeholders are passed through to the field.
- The product name field is no longer required, so a mixed or empty name does not block bulk editing.
- Mixed boolean fields use an indeterminate checkbox. Selecting it applies the chosen boolean value to every selected product.

For numeric bulk fields:

- The field is split into an operation control and a value control.
- The value control is disabled while the operation is `dont_change`.
- The value placeholder shows either `Mixed` or the shared existing value.

`injectBulkNumericOperationFormFields()` wraps those paired controls in a row so each numeric field is presented as:

```text
Operation | Value
```

The operation control is created by `createBulkNumericOperationField()` in `src/product-edit/bulk-numeric-control.tsx`.

## Change Handling

Single-product edits are simple: `onChange()` immediately calls `editEntityRecord()` with the field changes.

Bulk edits split changes into two groups:

- Non-numeric changes are applied immediately to every selected product.
- Numeric bulk operation changes are stored in local `bulkEditData` until save.

The numeric fields are deferred because operations like "increase by 10%" depend on each product's original value. Applying the same raw form value immediately would lose that per-product calculation.

`applySelectedProductChanges()` handles applying changes to the selected records:

- Product changes call `editEntityRecord( 'root', 'product', product.id, changes )`.
- Variation changes update the parent product's `_embedded.variations`.
- Multiple variation edits for the same parent are grouped before the parent record is edited.

## Numeric Operations

The numeric bulk fields are:

- `regular_price`
- `sale_price`
- `cost_of_goods_sold`
- `stock_quantity`

All numeric fields support:

- `dont_change`
- `set`
- `increase`
- `decrease`

Money fields also support:

- `increase_percent`
- `decrease_percent`

`stock_quantity` does not support percentage operations.

On save, `getBulkNumericChangesForProduct()` calculates the final value for each selected product:

- `set` uses the entered value.
- `increase` and `decrease` add or subtract the entered amount from the product's current value.
- Percentage operations apply the percentage to the product's current value.
- Values are clamped to zero or higher.
- Money values are rounded and formatted using the store currency precision.
- Stock quantity is rounded to an integer.
- Cost of goods updates the nested `cost_of_goods_sold.values[0].defined_value`.

Before applying numeric edits, `validateBulkNumericEdits()` projects the calculated changes onto each selected product and validates prices. This catches cases such as a sale price becoming greater than or equal to the regular price.

## Save Flow

When the user clicks Save:

1. Invalid numeric input blocks the save with a snackbar notice.
2. Pending numeric operations are validated against every selected product.
3. Valid numeric operations are converted into per-product edits and applied to the core data store.
4. `saveSelectedProducts()` persists the selected records.
5. The drawer shows a success or error notice based on how many products saved.
6. On full success, the drawer closes and clears the quick edit URL state.

`saveSelectedProducts()` treats products and variations differently:

- Regular products are saved through `saveEditedEntityRecord( 'root', 'product', productId )`.
- Variations are saved sequentially with `PUT /wc/v3/products/{parentId}/variations/{variationId}`.
- After a variation saves, it is merged back into the parent product's embedded variations.
- Parent products that own saved variations are saved after their variations.

Variations are saved sequentially because each saved variation is merged into the current parent snapshot. Saving them concurrently could merge against stale parent data and overwrite another variation's update.

## Cancel And Close

Closing the drawer clears unsaved core data edits for the selected products or their parent products, resets local `bulkEditData`, removes `quickEdit` from the URL, and navigates back to the product list route.

## Key Files

| File | Role |
| --- | --- |
| `src/dataviews-actions/actions.tsx` | Opens quick edit or bulk edit by writing selected IDs to the URL. |
| `src/router.tsx` | Mounts `ProductEdit` and controls whether the drawer is open. |
| `src/product-edit/index.tsx` | Owns the drawer, form data, change handling, validation, notices, and save trigger. |
| `src/product-edit/utils.ts` | Defines editable fields, product-type form layouts, field visibility rules, variation helpers, and merged data. |
| `src/product-edit/bulk-edit.ts` | Builds bulk field state and calculates numeric bulk edits. |
| `src/product-edit/bulk-numeric-control.tsx` | Defines the numeric operation select control. |
| `src/product-edit/save.ts` | Persists products and variations. |
| `src/product-edit/utils.test.ts` | Covers merged data, field visibility, variation helpers, and numeric bulk edit calculations. |
