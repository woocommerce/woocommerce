# Product Tags API 

## List Product Tags

```http
GET /products/tags
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
