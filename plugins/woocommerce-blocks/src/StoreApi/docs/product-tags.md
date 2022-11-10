# Product Tags API <!-- omit in toc -->

## Table of Contents <!-- omit in toc -->

- [List Product Tags](#list-product-tags)

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

<!-- FEEDBACK -->

---

[We're hiring!](https://woocommerce.com/careers/) Come work with us!

🐞 Found a mistake, or have a suggestion? [Leave feedback about this document here.](https://github.com/woocommerce/woocommerce-blocks/issues/new?assignees=&labels=type%3A+documentation&template=--doc-feedback.md&title=Feedback%20on%20./src/StoreApi/docs/product-tags.md)

<!-- /FEEDBACK -->

