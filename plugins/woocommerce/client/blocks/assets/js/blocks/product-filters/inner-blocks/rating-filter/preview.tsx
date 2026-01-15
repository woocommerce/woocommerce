/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { FilterOptionItem } from '../../types';
import RatingStars from './components/rating-stars';

export const previewOptions: FilterOptionItem[] = [
	{
		label: <RatingStars stars={ 5 } />,
		ariaLabel: __( 'Rated 5 out of 5', 'woocommerce' ),
		value: '5',
		count: 35,
	},
	{
		label: <RatingStars stars={ 4 } />,
		ariaLabel: __( 'Rated 4 out of 5', 'woocommerce' ),
		value: '4',
		count: 20,
	},
	{
		label: <RatingStars stars={ 3 } />,
		ariaLabel: __( 'Rated 3 out of 5', 'woocommerce' ),
		value: '3',
		count: 3,
	},
	{
		label: <RatingStars stars={ 2 } />,
		ariaLabel: __( 'Rated 2 out of 5', 'woocommerce' ),
		value: '2',
		count: 6,
	},
	{
		label: <RatingStars stars={ 1 } />,
		ariaLabel: __( 'Rated 1 out of 5', 'woocommerce' ),
		value: '1',
		count: 1,
	},
];
