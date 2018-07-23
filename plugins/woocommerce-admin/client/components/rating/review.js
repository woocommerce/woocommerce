/** @format */

/**
 * External dependencies
 */
import { Component } from '@wordpress/element';
import PropTypes from 'prop-types';

/**
 * Internal dependencies
 */
import { Rating } from './index';

class ReviewRating extends Component {
	render() {
		const { review, restOfProps } = this.props;
		const rating = ( review && review.rating ) || 0;
		return <Rating rating={ rating } { ...restOfProps } />;
	}
}

ReviewRating.propTypes = {
	review: PropTypes.object.isRequired,
};

export default ReviewRating;
