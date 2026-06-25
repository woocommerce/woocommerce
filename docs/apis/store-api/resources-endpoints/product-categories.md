# Product Categories API 

## List Product Categories

```http
GET /products/categories
```

| Attribute    | Type    | Required | Description                                                                                                           |
| :----------- | :------ | :------: | :-------------------------------------------------------------------------------------------------------------------- |
| `context`    | string  |    No    | Scope under which the request is made; determines fields present in response.                                         |
| `page`       | integer |    No    | Current page of the collection. Defaults to `1`.                                                                      |
| `per_page`   | integer |    No    | Maximum number of items to be returned in result set. Defaults to no limit. Values between `0` and `100` are allowed. |
| `search`     | string  |    No    | Limit results to those matching a string.                                                                             |
| `exclude`    | array   |    No    | Ensure result set excludes specific IDs.                                                                              |
| `include`    | array   |    No    | Limit result set to specific IDs.                                                                                     |
| `order`      | string  |    No    | Sort ascending or descending. Allowed values: `asc`, `desc`. Defaults to `asc`.                                       |
| `orderby`    | string  |    No    | Sort by term property. Allowed values: `name`, `slug`, `count`. Defaults to `name`.                                   |
| `hide_empty` | boolean |    No    | If true, empty terms will not be returned. Defaults to `true`.                                                        |
| `parent`     | integer |    No    | Limit results to those with a specific parent ID.                                                                     |

```sh
curl "https://example-store.com/wp-json/wc/store/v1/products/categories"
```

Example response:

```json
[
	{
		"id": 16,
		"name": "Clothing",
		"slug": "clothing",
		"description": "This is the clothing category.",
		"parent": 0,
		"count": 11,
		"image": {
			"id": 55,
			"src": "https://store.local/wp-content/uploads/2021/11/t-shirt-with-logo-1.jpg",
			"thumbnail": "https://store.local/wp-content/uploads/2021/11/t-shirt-with-logo-1-324x324.jpg",
			"srcset": "https://store.local/wp-content/uploads/2021/11/t-shirt-with-logo-1.jpg 800w, https://store.local/wp-content/uploads/2021/11/t-shirt-with-logo-1-324x324.jpg 324w, https://store.local/wp-content/uploads/2021/11/t-shirt-with-logo-1-100x100.jpg 100w, https://store.local/wp-content/uploads/2021/11/t-shirt-with-logo-1-416x416.jpg 416w, https://store.local/wp-content/uploads/2021/11/t-shirt-with-logo-1-300x300.jpg 300w, https://store.local/wp-content/uploads/2021/11/t-shirt-with-logo-1-150x150.jpg 150w, https://store.local/wp-content/uploads/2021/11/t-shirt-with-logo-1-768x768.jpg 768w",
			"sizes": "(max-width: 800px) 100vw, 800px",
			"name": "t-shirt-with-logo-1.jpg",
			"alt": ""
		},
		"review_count": 2,
		"permalink": "https://store.local/product-category/clothing/"
	},
	{
		"id": 21,
		"name": "Decor",
		"slug": "decor",
		"description": "",
		"parent": 0,
		"count": 1,
		"image": null,
		"review_count": 1,
		"permalink": "https://store.local/product-category/decor/"
	}
]
```

## Single Product Category

Get a single category.

```http
GET /products/categories/:id
```

| Category | Type    | Required | Description                         |
| :------- | :------ | :------: | :---------------------------------- |
| `id`     | integer |   Yes    | The ID of the category to retrieve. |

```sh
curl "https://example-store.com/wp-json/wc/store/v1/products/categories/1"
```

**Example response:**

```json
{
	"id": 1,
	"name": "Decor",
	"slug": "decor",
	"description": "",
	"parent": 0,
	"count": 1,
	"image": null,
	"review_count": 1,
	"permalink": "https://store.local/product-category/decor/"
}
```
