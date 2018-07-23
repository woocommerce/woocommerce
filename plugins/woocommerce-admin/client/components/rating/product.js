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

class ProductRating extends Component {
	render() {
		const { product, restOfProps } = this.props;
		const rating = ( product && product.average_rating ) || 0;
		return <Rating rating={ rating } { ...restOfProps } />;
	}
}

ProductRating.propTypes = {
	product: PropTypes.object.isRequired,
};

export default ProductRating;
