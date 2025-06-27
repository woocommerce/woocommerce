/**
 * Internal dependencies
 */
import RatingStars from './components/rating-stars';

export const previewOptions = [
	{
		label: <RatingStars stars={ 5 } />,
		value: '5',
		count: 35,
	},
	{
		label: <RatingStars stars={ 4 } />,
		value: '4',
		count: 20,
	},
	{
		label: <RatingStars stars={ 3 } />,
		value: '3',
		count: 3,
	},
	{
		label: <RatingStars stars={ 2 } />,
		value: '2',
		count: 6,
	},
	{
		label: <RatingStars stars={ 1 } />,
		value: '1',
		count: 1,
	},
];
