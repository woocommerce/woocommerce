---
post_title: Product customs data
sidebar_label: Customs data
sidebar_position: 4
---

# Product customs data

WooCommerce stores three optional customs fields on products and variations. They help merchants keep a description of goods for cross-border shipping. They do not calculate duties or produce shipping labels.

## Set customs details in the product editor

Customs fields appear in the **Shipping** tab, so any product type that shows that tab has them. Virtual products and variations have no customs fields in the editor.

Open a product in the classic product editor. In **Product data → Shipping → Customs**, enter:

-   **Commodity code:** The HS (Harmonized System) code: an HS6 code or a longer country-specific code, such as `0901.21.0010`. WooCommerce removes punctuation and spaces and stores `0901210010`. The code must contain 6–14 digits; letters are rejected. Keep leading zeros.
-   **Country of origin:** The country where the product was made. The stored value is a two-letter ISO country code such as `BR`.
-   **Customs description:** Plain text for customs forms, up to 35 characters. HTML tags are removed, and runs of spaces or line breaks become a single space. Letters in any language, numbers, spaces, punctuation and standard keyboard symbols such as `&`, `%` or `$` are allowed; emoji and other symbols, such as `™` or `€`, are rejected. A `<` followed directly by text, as in `<5kg`, is treated as the start of a tag and removed.

All three fields can be left empty. For a variable product, expand a variation and set its customs values in the variation's shipping fields. A blank variation field inherits the parent product's value. Enter a value to override the parent; clear it to inherit again. Duplicating a product copies its values and its variations' overrides.

## Import and export CSV files

Product CSV files use the columns **Commodity code (HS code)**, **Country of origin**, and **Customs description**. The machine-name headers `commodity_code`, `country_of_origin`, and `customs_description` are also accepted on import. These columns work for product and variation rows.

-   A blank cell in a mapped customs column clears the value. On a variation row, this means the variation inherits the parent's value.
-   Omit a column to leave that value unchanged for every row.
-   Invalid values fail the row with a field-specific error; other valid rows still import.
-   Exports contain an empty cell when no value is stored. Variation rows contain only the variation's own overrides.

Spreadsheet apps can drop leading zeros from codes such as `0901210010`. Format the commodity code column as text before editing and saving the file.

## Use the REST API

The `/wc/v3/products` and `/wc/v3/products/{product_id}/variations` endpoints expose `customs_commodity_code`, `customs_country_of_origin`, and `customs_description`. The same fields are available on the `/wc-analytics/products` endpoints.

-   The default `view` context returns resolved values. Variations include values inherited from the parent product.
-   `context=edit` returns stored values. A variation that inherits a value returns `null`.
-   Omit a field from a POST or PUT request to preserve it. Send `null` or an empty string to clear it.
-   Invalid values return HTTP 400 with a `woocommerce_product_invalid_customs_*` error code. Non-string values return `rest_invalid_param`, except inside batch requests, where the item error uses the `woocommerce_product_invalid_customs_*` code.
-   In batch requests, an invalid item returns an error object inside a 200 response, and the other items are still applied.

```json
{
	"customs_commodity_code": "0901.21.0010",
	"customs_country_of_origin": "br",
	"customs_description": "Roasted coffee"
}
```

The saved values are `0901210010`, `BR`, and `Roasted coffee`.

## Use the PHP methods and filters

`WC_Product` provides `get_customs_commodity_code()`, `get_customs_country_of_origin()`, and `get_customs_description()`, with matching setters. In the default `view` context, a variation getter returns the parent's value when the variation has none. Pass `'edit'` to get the stored value.

The getters run the `woocommerce_product_get_customs_*` filters for products and the `woocommerce_product_variation_get_customs_*` filters for variations. The variation filters also run for inherited values.

To clear a value, call the setter with `null` or `''`. `set_props()` ignores `null`, so pass `''` when clearing through it. The values use the protected meta keys `_customs_commodity_code`, `_customs_country_of_origin`, and `_customs_description`; use the CRUD methods to read or change them.

## Usage tracking

When usage tracking is enabled, WooCommerce's periodic tracker counts published products with a commodity code, country of origin, or customs description, and published variations with their own commodity code or country of origin. It sends only aggregate counts, without product IDs or field values.
