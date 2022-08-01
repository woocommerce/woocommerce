# Product Attribute Terms API

```http
GET /products/attributes/:id/terms
GET /products/attributes/:id/terms&orderby=slug
```

| Attribute | Type    | Required | Description                                                                   |
| :-------- | :------ | :------: | :---------------------------------------------------------------------------- |
| `id`      | integer |   Yes    | The ID of the attribute to retrieve terms for.                                |
| `order`   | string  |    no    | Order ascending or descending. Allowed values: `asc`, `desc`                  |
| `orderby` | string  |    no    | Sort collection by object attribute. Allowed values: `name`, `slug`, `count`. |

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

<!-- FEEDBACK -->

---

[We're hiring!](https://woocommerce.com/careers/) Come work with us!

🐞 Found a mistake, or have a suggestion? [Leave feedback about this document here.](https://github.com/woocommerce/woocommerce-blocks/issues/new?assignees=&labels=type%3A+documentation&template=--doc-feedback.md&title=Feedback%20on%20./src/StoreApi/docs/product-attribute-terms.md)

<!-- /FEEDBACK -->

