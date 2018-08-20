/** @format */

/**
 * External dependencies
 */
import PropTypes from 'prop-types';

/**
 * Internal dependencies
 */
import Rating from './index';

const ProductRating = ( { product, ...props } ) => {
	const rating = ( product && product.average_rating ) || 0;
	return <Rating rating={ rating } { ...props } />;
};

ProductRating.propTypes = {
	product: PropTypes.object.isRequired,
};

export default ProductRating;
