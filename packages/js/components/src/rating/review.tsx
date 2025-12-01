/**
 * External dependencies
 */
import { createElement } from '@wordpress/element';

/**
 * Internal dependencies
 */
import Rating from './index';

type ReviewRatingProps = {
	/**
	 * A review object containing a `rating`.
	 * See https://woocommerce.github.io/woocommerce-rest-api-docs/#retrieve-product-reviews.
	 */
	review: {
		rating?: number;
	};
};

/**
 * Display a set of stars representing the review's rating.
 */
export default function ReviewRating( {
	review,
	...props
}: ReviewRatingProps ) {
	return <Rating rating={ review.rating || 0 } { ...props } />;
}
