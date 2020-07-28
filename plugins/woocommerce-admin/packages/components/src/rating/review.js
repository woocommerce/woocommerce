/**
 * External dependencies
 */
import PropTypes from 'prop-types';

/**
 * Internal dependencies
 */
import Rating from './index';

/**
 * Display a set of stars representing the review's rating.
 *
 * @param root0
 * @param root0.review
 * @return {Object} -
 */
const ReviewRating = ( { review, ...props } ) => {
	const rating = ( review && review.rating ) || 0;
	return <Rating rating={ rating } { ...props } />;
};

ReviewRating.propTypes = {
	/**
	 * A review object containing a `rating`.
	 * See https://woocommerce.github.io/woocommerce-rest-api-docs/#retrieve-product-reviews.
	 */
	review: PropTypes.object.isRequired,
};

export default ReviewRating;
