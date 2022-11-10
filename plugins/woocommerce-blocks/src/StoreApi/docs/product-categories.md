# Product Categories API <!-- omit in toc -->

## Table of Contents <!-- omit in toc -->

- [List Product Categories](#list-product-categories)
- [Single Product Category](#single-product-category)

## List Product Categories

```http
GET /products/categories
```

There are no parameters required for this endpoint.

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

<!-- FEEDBACK -->

---

[We're hiring!](https://woocommerce.com/careers/) Come work with us!

🐞 Found a mistake, or have a suggestion? [Leave feedback about this document here.](https://github.com/woocommerce/woocommerce-blocks/issues/new?assignees=&labels=type%3A+documentation&template=--doc-feedback.md&title=Feedback%20on%20./src/StoreApi/docs/product-categories.md)

<!-- /FEEDBACK -->

