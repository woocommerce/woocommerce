Rating
===

Use `Rating` to display a set of stars, filled, empty or half-filled, that represents a
rating in a scale between 0 and the prop `totalStars` (default 5).

## Usage

```jsx
<Rating rating={ 2.5 } totalStars={ 6 } />
```

### Props

Name | Type | Default | Description
--- | --- | --- | ---
`rating` | Number | `0` | Number of stars that should be filled. You can pass a partial number of stars like `2.5`
`totalStars` | Number | `5` | The total number of stars the rating is out of
`size` | Number | `18` | The size in pixels the stars should be rendered at
`className` | String | `null` | Additional CSS classes


ProductRating
===

Display a set of stars representing the product's average rating.

## Usage

```jsx
// Use a real WooCommerce Product here.
const product = { average_rating: 3.5 };

<ProductRating product={ product } />
```

### Props

Name | Type | Default | Description
--- | --- | --- | ---
`product` | Object | `null` | (required) A product object containing a `average_rating`. See https://woocommerce.github.io/woocommerce-rest-api-docs/#products


ReviewRating
===

Display a set of stars representing the review's rating.

## Usage

```jsx
// Use a real WooCommerce Review here.
const review = { rating: 5 };

<ReviewRating review={ review } />
```

### Props

Name | Type | Default | Description
--- | --- | --- | ---
`review` | Object | `null` | (required) A review object containing a `rating`. See https://woocommerce.github.io/woocommerce-rest-api-docs/#retrieve-product-reviews
