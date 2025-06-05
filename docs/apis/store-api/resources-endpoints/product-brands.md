# Product Brands API 

## List Product Brands

```http
GET /products/brands
```

There are no parameters required for this endpoint.

```sh
curl "https://example-store.com/wp-json/wc/store/v1/products/brands"
```

Example response:

```json
[
	{
		"id": 16,
		"name": "Nike",
		"slug": "nike",
		"description": "This is the Nike brand.",
		"parent": 0,
		"count": 11,
		"image": {
			"id": 55,
			"src": "https://store.local/wp-content/uploads/2021/11/nike-logo.jpg",
			"thumbnail": "https://store.local/wp-content/uploads/2021/11/nike-logo-324x324.jpg",
			"srcset": "https://store.local/wp-content/uploads/2021/11/nike-logo.jpg 800w, https://store.local/wp-content/uploads/2021/11/nike-logo-324x324.jpg 324w, https://store.local/wp-content/uploads/2021/11/nike-logo-100x100.jpg 100w, https://store.local/wp-content/uploads/2021/11/nike-logo-416x416.jpg 416w, https://store.local/wp-content/uploads/2021/11/nike-logo-300x300.jpg 300w, https://store.local/wp-content/uploads/2021/11/nike-logo-150x150.jpg 150w, https://store.local/wp-content/uploads/2021/11/nike-logo-768x768.jpg 768w",
			"sizes": "(max-width: 800px) 100vw, 800px",
			"name": "nike-logo.jpg",
			"alt": ""
		},
		"review_count": 2,
		"permalink": "https://store.local/product-brand/nike/"
	},
	{
		"id": 21,
		"name": "Adidas",
		"slug": "adidas",
		"description": "",
		"parent": 0,
		"count": 1,
		"image": null,
		"review_count": 1,
		"permalink": "https://store.local/product-brand/adidas/"
	}
]
```

## Single Product Brand

Get a single brand.

```http
GET /products/brands/:id
```

or 

```http
GET /products/brands/:slug
```

| Parameter   | Type    | Required | Description                                                          |
| :--------   | :------ | :------: |:---------------------------------------------------------------------|
| `identifier` | string |   Yes    | The identifier of the brand to retrieve. Can be an brand ID or slug. |

```sh
curl "https://example-store.com/wp-json/wc/store/v1/products/brands/1"
```

or

```sh
curl "https://example-store.com/wp-json/wc/store/v1/products/brands/adidas"
```

**Example response:**

```json
{
	"id": 1,
	"name": "Adidas",
	"slug": "adidas",
	"description": "",
	"parent": 0,
	"count": 1,
	"image": null,
	"review_count": 1,
	"permalink": "https://store.local/product-brand/adidas/"
}
```
