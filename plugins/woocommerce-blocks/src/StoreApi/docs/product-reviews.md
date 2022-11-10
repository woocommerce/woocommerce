# Product Reviews API <!-- omit in toc -->

## Table of Contents <!-- omit in toc -->

- [List Product Reviews](#list-product-reviews)

## List Product Reviews

This endpoint returns product reviews (comments) and can also show results from either specific products or specific categories.

```http
GET /products/reviews
GET /products/reviews?category_id=1,2,3
GET /products/reviews?product_id=1,2,3
GET /products/reviews?orderby=rating&order=desc
```

| Attribute     | Type    | Required | Description                                                                                         |
| :------------ | :------ | :------: | :-------------------------------------------------------------------------------------------------- |
| `page`        | integer |    no    | Current page of the collection.                                                                     |
| `per_page`    | integer |    no    | Maximum number of items to be returned in result set. Defaults to no limit if left blank.           |
| `offset`      | integer |    no    | Offset the result set by a specific number of items.                                                |
| `order`       | string  |    no    | Order sort attribute ascending or descending. Allowed values: `asc`, `desc`                         |
| `orderby`     | string  |    no    | Sort collection by object attribute. Allowed values : `date`, `date_gmt`, `id`, `rating`, `product` |
| `category_id` | string  |    no    | Limit result set to reviews from specific category IDs.                                             |
| `product_id`  | string  |    no    | Limit result set to reviews from specific product IDs.                                              |

```sh
curl "https://example-store.com/wp-json/wc/store/v1/products/collection-data?calculate_price_range=true&calculate_attribute_counts=pa_size,pa_color&calculate_rating_counts=true"
```

**Example response:**

```json
[
	{
		"id": 83,
		"date_created": "2022-01-12T15:42:14",
		"formatted_date_created": "January 12, 2022",
		"date_created_gmt": "2022-01-12T15:42:14",
		"product_id": 33,
		"product_name": "Beanie with Logo",
		"product_permalink": "https://store.local/product/beanie-with-logo/",
		"product_image": {
			"id": 56,
			"src": "https://store.local/wp-content/uploads/2021/11/beanie-with-logo-1.jpg",
			"thumbnail": "https://store.local/wp-content/uploads/2021/11/beanie-with-logo-1-324x324.jpg",
			"srcset": "https://store.local/wp-content/uploads/2021/11/beanie-with-logo-1.jpg 800w, https://store.local/wp-content/uploads/2021/11/beanie-with-logo-1-324x324.jpg 324w, https://store.local/wp-content/uploads/2021/11/beanie-with-logo-1-100x100.jpg 100w, https://store.local/wp-content/uploads/2021/11/beanie-with-logo-1-416x416.jpg 416w, https://store.local/wp-content/uploads/2021/11/beanie-with-logo-1-300x300.jpg 300w, https://store.local/wp-content/uploads/2021/11/beanie-with-logo-1-150x150.jpg 150w, https://store.local/wp-content/uploads/2021/11/beanie-with-logo-1-768x768.jpg 768w",
			"sizes": "(max-width: 800px) 100vw, 800px",
			"name": "beanie-with-logo-1.jpg",
			"alt": ""
		},
		"reviewer": "reviewer-name",
		"review": "<p>This is a fantastic product.</p>\n",
		"rating": 5,
		"verified": true,
		"reviewer_avatar_urls": {
			"24": "https://secure.gravatar.com/avatar/12345?s=24&d=mm&r=g",
			"48": "https://secure.gravatar.com/avatar/12345?s=48&d=mm&r=g",
			"96": "https://secure.gravatar.com/avatar/12345?s=96&d=mm&r=g"
		}
	}
]
```

<!-- FEEDBACK -->

---

[We're hiring!](https://woocommerce.com/careers/) Come work with us!

🐞 Found a mistake, or have a suggestion? [Leave feedback about this document here.](https://github.com/woocommerce/woocommerce-blocks/issues/new?assignees=&labels=type%3A+documentation&template=--doc-feedback.md&title=Feedback%20on%20./src/StoreApi/docs/product-reviews.md)

<!-- /FEEDBACK -->

