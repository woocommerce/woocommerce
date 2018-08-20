/** @format */

/**
 * External dependencies
 */
import PropTypes from 'prop-types';

/**
 * Internal dependencies
 */
import Rating from './index';

const ReviewRating = ( { review, ...props } ) => {
	const rating = ( review && review.rating ) || 0;
	return <Rating rating={ rating } { ...props } />;
};

ReviewRating.propTypes = {
	review: PropTypes.object.isRequired,
};

export default ReviewRating;
