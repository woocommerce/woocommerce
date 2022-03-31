/**
 * Internal dependencies
 */
import Rating from '../';

export default {
	title: 'WooCommerce Admin/components/Rating',
	component: Rating,
	args: {
		rating: 4.5,
		totalStars: Rating.defaultProps.totalStars,
		size: Rating.defaultProps.size,
	},
};

export const Default = ( args ) => <Rating { ...args } />;
