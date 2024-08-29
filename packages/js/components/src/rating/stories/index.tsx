/**
 * External dependencies
 */
import { createElement } from '@wordpress/element';

/**
 * Internal dependencies
 */
import Rating from '..';

export default {
	title: 'WooCommerce Admin/components/Rating',
	component: Rating,
	args: {
		rating: 4.5,
		totalStars: 5,
		size: 18,
	},
};

export const Default = ( args ) => <Rating { ...args } />;
