/**
 * External dependencies
 */
import { createElement } from '@wordpress/element';

/**
 * Internal dependencies
 */
import Rating from './index';

export type ProductRatingProps = {
	product: {
		average_rating?: number;
	};
};

/**
 * Display a set of stars representing the product's average rating.
 */
export default function ProductRating( {
	product,
	...props
}: ProductRatingProps ) {
	const rating = ( product && product.average_rating ) || 0;
	return <Rating rating={ rating } { ...props } />;
}
