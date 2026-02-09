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

```sh
curl "https://example-store.com/wp-json/wc/store/v1/products/attributes/1/terms"
```

**Example response:**

```json
[
	{
		"id": 22,
		"name": "Blue",
		"slug": "blue",
		"count": 5
	},
	{
		"id": 48,
		"name": "Burgundy",
		"slug": "burgundy",
		"count": 1
	}
]
```
