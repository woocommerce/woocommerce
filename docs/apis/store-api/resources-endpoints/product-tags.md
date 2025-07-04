# Product Tags API 

## List Product Tags

```http
GET /products/tags
```

There are no parameters required for this endpoint.

```sh
curl "https://example-store.com/wp-json/wc/store/v1/products/tags"
```

Example response:

```json
[
	{
		"id": 1,
		"name": "Test Tag",
		"slug": "test-tag",
		"description": "",
		"parent": 0,
		"count": 1
	},
	{
		"id": 2,
		"name": "Another Tag",
		"slug": "another-tag",
		"description": "",
		"parent": 0,
		"count": 1
	}
]
```
