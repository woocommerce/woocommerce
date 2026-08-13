# Product Attributes API 

## List Product Attributes

```http
GET /products/attributes
```

There are no parameters required for this endpoint.

```sh
curl "https://example-store.com/wp-json/wc/store/v1/products/attributes"
```

Example response:

```json
[
	{
		"id": 1,
		"name": "Color",
		"taxonomy": "pa_color",
		"type": "select",
		"order": "menu_order",
		"has_archives": false
	},
	{
		"id": 2,
		"name": "Size",
		"taxonomy": "pa_size",
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

```sh
curl "https://example-store.com/wp-json/wc/store/v1/products/attributes/1"
```

**Example response:**

```json
{
	"id": 1,
	"name": "Color",
	"taxonomy": "pa_color",
	"type": "select",
	"order": "menu_order",
	"has_archives": false
}
```
