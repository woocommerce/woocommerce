# Product Attribute Terms API

```http
GET /products/attributes/:id/terms
GET /products/attributes/:id/terms?orderby=slug
```

| Attribute | Type    | Required | Description                                                                                                   |
| :-------- | :------ | :------: |:--------------------------------------------------------------------------------------------------------------|
| `id`      | integer |   Yes    | The ID of the attribute to retrieve terms for.                                                                |
| `order`   | string  |    no    | Order ascending or descending. Allowed values: `asc`, `desc`                                                  |
| `orderby` | string  |    no    | Sort collection by object attribute. Allowed values: `id`, `name`, `name_num`, `slug`, `count`, `menu_order`. |

## Visual response fields

The following fields are included only for `wc-visual` attribute terms.
Other attribute types keep the default term response without `__experimentalVisual`.

| Attribute                    | Type   | Description                                                                                                    |
| :--------------------------- | :----- | :------------------------------------------------------------------------------------------------------------- |
| `__experimentalVisual`       | object | Experimental visual swatch data for `wc-visual` attribute terms.                                               |
| `__experimentalVisual.type`  | string | Visual swatch type. Allowed values: `color`, `image`, `none`.                                                  |
| `__experimentalVisual.value` | string | Visual swatch value. Returns a hex color for `color`, an image URL for `image`, or an empty string for `none`. |

```sh
curl "https://example-store.com/wp-json/wc/store/v1/products/attributes/1/terms"
```

**Example response for visual attribute terms:**

```json
[
	{
		"id": 22,
		"name": "Blue",
		"slug": "blue",
		"description": "",
		"parent": 0,
		"count": 5,
		"__experimentalVisual": {
			"type": "color",
			"value": "#1e73be"
		}
	},
	{
		"id": 48,
		"name": "Burgundy",
		"slug": "burgundy",
		"description": "",
		"parent": 0,
		"count": 1,
		"__experimentalVisual": {
			"type": "image",
			"value": "https://example-store.com/wp-content/uploads/2026/06/burgundy-swatch.jpg"
		}
	}
]
```
