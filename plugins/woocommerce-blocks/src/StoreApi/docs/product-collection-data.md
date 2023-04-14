# Product Collection Data API

This endpoint allows you to get aggregate data from a collection of products, for example, the min and max price in a collection of products (ignoring pagination). This is used by blocks for product filtering widgets, since counts are based on the product catalog being viewed.

```http
GET /products/collection-data
GET /products/collection-data?calculate_price_range=true
GET /products/collection-data?calculate_attribute_counts[0][query_type]=or&calculate_attribute_counts[0][taxonomy]=pa_color
GET /products/collection-data?calculate_rating_counts=true
```

| Attribute                    | Type   | Required | Description                                                                                                                                                                                                |
| :--------------------------- | :----- | :------: | :--------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| `calculate_price_range`      | bool   |    No    | Returns the min and max price for the product collection. If false, only `null` will be returned. |
| `calculate_attribute_counts` | object |    No    | Returns attribute counts for a list of attribute taxonomies you pass in via this parameter. Each should be provided as an object with keys "taxonomy" and "query_type". If empty, `null` will be returned. |
| `calculate_rating_counts`    | bool   |    No    | Returns the counts of products with a certain average rating, 1-5. If false, only `null` will be returned. |

**In addition to the above attributes**, all product list attributes are supported. This allows you to get data for a certain subset of products. See [the products API list products section](products.md#list-products) for the full list.

```sh
curl "https://example-store.com/wp-json/wc/store/v1/products/collection-data?calculate_price_range=true&calculate_attribute_counts=pa_size,pa_color&calculate_rating_counts=true"
```

**Example response:**

```json
{
	"price_range": [
		"currency_minor_unit": 2,
		"min_price": "0",
		"max_price": "9000",
		"currency_code": "USD",
		"currency_decimal_separator": ".",
		"currency_minor_unit": 2,
		"currency_prefix": "$",
		"currency_suffix": "",
		"currency_symbol": "$",
		"currency_thousand_separator": ",",
	],
	"attribute_counts": [
		{
			"term": 22,
			"count": 4
		},
		{
			"term": 23,
			"count": 3
		},
		{
			"term": 24,
			"count": 4
		}
	],
	"rating_counts": [
		{
			"rating": 3,
			"count": 1
		},
		{
			"rating": 4,
			"count": 1
		}
	]
}
```

<!-- FEEDBACK -->

---

[We're hiring!](https://woocommerce.com/careers/) Come work with us!

🐞 Found a mistake, or have a suggestion? [Leave feedback about this document here.](https://github.com/woocommerce/woocommerce-blocks/issues/new?assignees=&labels=type%3A+documentation&template=--doc-feedback.md&title=Feedback%20on%20./src/StoreApi/docs/product-collection-data.md)

<!-- /FEEDBACK -->

