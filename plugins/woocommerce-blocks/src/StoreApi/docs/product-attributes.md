# Product Attributes API <!-- omit in toc -->

## Table of Contents <!-- omit in toc -->

- [List Product Attributes](#list-product-attributes)
- [Single Product Attribute](#single-product-attribute)

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

<!-- FEEDBACK -->

---

[We're hiring!](https://woocommerce.com/careers/) Come work with us!

🐞 Found a mistake, or have a suggestion? [Leave feedback about this document here.](https://github.com/woocommerce/woocommerce-blocks/issues/new?assignees=&labels=type%3A+documentation&template=--doc-feedback.md&title=Feedback%20on%20./src/StoreApi/docs/product-attributes.md)

<!-- /FEEDBACK -->

