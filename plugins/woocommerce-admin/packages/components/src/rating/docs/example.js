/**
 * External dependencies
 */
import { ReviewRating, ProductRating, Rating } from '@woocommerce/components';

export default () => {
	const product = { average_rating: 3.5 };
	const review = { rating: 5 };

	return (
		<div>
			<div>
				<Rating rating={ 4 } totalStars={ 5 } />
			</div>
			<div>
				<Rating rating={ 2.5 } totalStars={ 6 } />
			</div>
			<div>
				<ProductRating product={ product } />
			</div>
			<div>
				<ReviewRating review={ review } />
			</div>
		</div>
	);
};
