`Rating` (component)
====================

Use `Rating` to display a set of stars, filled, empty or half-filled, that represents a
rating in a scale between 0 and the prop `totalStars` (default 5).

Props
-----

### `rating`

- Type: Number
- Default: `0`

Number of stars that should be filled. You can pass a partial number of stars like `2.5`.

### `totalStars`

- Type: Number
- Default: `5`

The total number of stars the rating is out of.

### `size`

- Type: Number
- Default: `18`

The size in pixels the stars should be rendered at.

### `className`

- Type: String
- Default: null

Additional CSS classes.

`ProductRating` (component)
===========================

Display a set of stars representing the product's average rating.



Props
-----

### `product`

- **Required**
- Type: Object
- Default: null

A product object containing a `average_rating`.
See https://woocommerce.github.io/woocommerce-rest-api-docs/#products.

`ReviewRating` (component)
==========================

Display a set of stars representing the review's rating.



Props
-----

### `review`

- **Required**
- Type: Object
- Default: null

A review object containing a `rating`.
See https://woocommerce.github.io/woocommerce-rest-api-docs/#retrieve-product-reviews.

