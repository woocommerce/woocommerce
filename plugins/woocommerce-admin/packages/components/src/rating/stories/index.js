/**
 * External dependencies
 */
import { number } from '@storybook/addon-knobs';

/**
 * Internal dependencies
 */
import Rating from '../';

export default {
	title: 'WooCommerce Admin/components/Rating',
	component: Rating,
};

export const Default = () => (
	<Rating
		rating={ number( 'Rating', 4.5 ) }
		totalStars={ number( 'Total Stars', Rating.defaultProps.totalStars ) }
		size={ number( 'Size', Rating.defaultProps.size ) }
	/>
);
