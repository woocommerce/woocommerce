# Product Attributes API <!-- omit in toc -->

- [List Product Attributes](#list-product-attributes)
- [Single Product Attribute](#single-product-attribute)

## List Product Attributes

```http
GET /products/attributes
```

There are no parameters required for this endpoint.

```http
curl "https://example-store.com/wp-json/wc/store/products/attributes"
```

Example response:

```json
[
	{
		"id": 1,
		"name": "Color",
		"slug": "pa_color",
		"type": "select",
		"order": "menu_order",
		"has_archives": false
	},
	{
		"id": 2,
		"name": "Size",
		"slug": "pa_size",
		"type": "select",
		"order": "menu_order",
		"has_archives": false
	}
]
```

## Single Product Attribute

Get a single attribute taxonomy.

```http
GET /products/attributes/:id
```

| Attribute | Type    | Required | Description                          |
| :-------- | :------ | :------: | :----------------------------------- |
| `id`      | integer |   Yes    | The ID of the attribute to retrieve. |

```http
curl "https://example-store.com/wp-json/wc/store/products/attributes/1"
```

**Example response:**

```json
{
	"id": 1,
	"name": "Color",
	"slug": "pa_color",
	"type": "select",
	"order": "menu_order",
	"has_archives": false
}
```
