---
post_title: Product CSV importer compatibility for custom fields
sidebar_label: Product CSV importer compatibility
---

# Product CSV importer compatibility for custom fields

WooCommerce 11.2.2 changes how the built-in product CSV importer applies mapped columns to product objects. This guide is for developers maintaining an extension that registers custom import columns. It does not describe the separate Product CSV Import Suite extension.

## Check whether your extension is affected

The importer passes only keys returned by the product object's `get_data_keys()` to `set_props()`. Earlier versions could pass other mapped keys to a matching `set_*()` method. If your extension depends on such a setter without declaring the corresponding product data key, that setter is no longer called by this path.

Review the extension's mapping and import callbacks on a staging store. Import a small file containing a known custom value and check the product after the import. Test both new products and updates if your extension supports both. Back up the test data before importing.

## Adapt a custom column

If the field is a product property, define the corresponding product data key in the product's `extra_data` and provide the appropriate getter, setter, and persistence behavior for your product type.

Alternatively, handle your mapped value with the `woocommerce_product_import_pre_insert_product_object` filter. The filter receives the product object and the processed import data before the object is saved. Return the product object from the callback. Validate and sanitize the mapped value for the field before setting it. Import processing and earlier filters can modify the data, so don't assume it is an untouched CSV row.

The following example registers a **Supplier reference** column, maps that exact CSV header automatically, and saves its value as product metadata. Add the code to your extension, using a unique prefix and field name for your own integration:

```php
/**
 * Add the custom field to the importer mapping options.
 *
 * @param array $options Available mapping options.
 * @return array
 */
function example_register_supplier_reference( $options ) {
	$options['example_supplier_reference'] = __( 'Supplier reference', 'example-extension' );
	return $options;
}
add_filter( 'woocommerce_csv_product_import_mapping_options', 'example_register_supplier_reference' );

/**
 * Map the matching CSV header to the custom field.
 *
 * @param array $columns Default column mappings.
 * @return array
 */
function example_map_supplier_reference( $columns ) {
	$columns['Supplier reference'] = 'example_supplier_reference';
	return $columns;
}
add_filter( 'woocommerce_csv_product_import_mapping_default_columns', 'example_map_supplier_reference' );

/**
 * Save the mapped value before the importer saves the product.
 *
 * @param WC_Product $product Product being imported.
 * @param array      $data    Processed import data.
 * @return WC_Product
 */
function example_import_supplier_reference( $product, $data ) {
	if ( array_key_exists( 'example_supplier_reference', $data ) ) {
		$product->update_meta_data( '_example_supplier_reference', sanitize_text_field( $data['example_supplier_reference'] ) );
	}
	return $product;
}
add_filter( 'woocommerce_product_import_pre_insert_product_object', 'example_import_supplier_reference', 10, 2 );
```

The callback runs for each imported product, but changes metadata only when the mapped column is present in that row. It writes an empty value when the mapped CSV cell is blank. If your extension needs different update behavior or supports only specific product types, add those checks before updating metadata. Adapt validation to your field's expected format.

For ordinary product metadata, the built-in importer also supports columns prefixed with `meta:`. See [Product CSV Importer and Exporter](https://woocommerce.com/document/product-csv-importer-exporter/#custom-columns-and-metadata) for the merchant workflow. Adding custom columns and import callbacks requires extension code.

## Verify the result

1. Register and map the custom column in your extension.
2. Import a small CSV with a known value into a staging store.
3. Reload the imported product and check that the value was saved in the expected property or metadata. If you support updates, repeat the test for an existing product.
4. If the value is missing, check the column mapping, your declared data key or filter callback, and any validation errors. Correct the extension or CSV and repeat the test on staging.

