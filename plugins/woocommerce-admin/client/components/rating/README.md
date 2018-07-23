Rating
============

Use `Rating` to display a set of stars, filled, empty or half-filled, that represents a rating in a scale between 0 and the prop `totalStars` (default 5).

`ProductRating` and `ReviewRating` components are also avaiable, which will pull the correct information out of `product` and `review` objects respectively. See https://woocommerce.github.io/woocommerce-rest-api-docs/.

## How to use:

```jsx
import { ReviewRating, ProductRating, Rating } from 'components/rating';

render: function() {
  return (
	<div>
		<div><Rating rating={ 4 } totalStars={ 5 } /></div>
		<div><Rating rating={ 2.5 } totalStars={ 6 } /></div>
		<div><ProductRating product={ {
		average_rating: 2.5,
		} } /></div>
		<div><ProductRating product={ {
		average_rating: 4,
		} } /></div>
		<div><ReviewRating review={ {
		rating: 4,
		} } /></div>
		<div><ReviewRating review={ {
		rating: 1.5,
		} } /></div>
	</div>
  );
}
```

## ReviewRating Props

Other props will be passed down to `Rating`.

* `review` (required): A review object containing a `rating`. See https://woocommerce.github.io/woocommerce-rest-api-docs/#retrieve-product-reviews.

 ## ProductRating Props

Other props will be passed down to `Rating`.

* `product` (required): A product object containing a `average_rating`. See https://woocommerce.github.io/woocommerce-rest-api-docs/#products.

## Rating Props

* `rating`: Number of stars that should be filled. You can pass a partial number of stars like `2.5`.
* `totalStars`: Default 5. The total number of stars the rating is out of.
* `size`: Default 18. The size in pixels the stars should be rendered at.
* `className`: Additional CSS classes.