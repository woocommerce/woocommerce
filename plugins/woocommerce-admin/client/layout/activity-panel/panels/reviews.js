/** @format */
/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Component, Fragment } from '@wordpress/element';

/**
 * Internal dependencies
 */
import ActivityHeader from '../activity-header';
import ProductImage from 'components/product-image';
import { ProductRating, ReviewRating, Rating } from 'components/rating';

class ReviewsPanel extends Component {
	render() {
		return (
			<Fragment>
				<ActivityHeader title={ __( 'Reviews', 'wc-admin' ) } />
				<ProductImage product={ null } />
				<ProductImage product={ { images: [] } } />
				<ProductImage
					product={ {
						images: [
							{
								src: 'https://i.cloudup.com/pt4DjwRB84-3000x3000.png',
							},
						],
					} }
				/>
				<div>
					Rating: <Rating rating={ 4 } totalStars={ 5 } />
				</div>
				<div>
					Rating: <Rating rating={ 2.5 } totalStars={ 6 } />
				</div>
				<div>
					ProductRating:{' '}
					<ProductRating
						product={ {
							average_rating: 2.5,
						} }
					/>
				</div>
				<div>
					ProductRating:{' '}
					<ProductRating
						product={ {
							average_rating: 4,
						} }
					/>
				</div>
				<div>
					ReviewRating:{' '}
					<ReviewRating
						review={ {
							rating: 4,
						} }
					/>
				</div>
				<div>
					ReviewRating:{' '}
					<ReviewRating
						review={ {
							rating: 1.5,
						} }
					/>
				</div>
			</Fragment>
		);
	}
}

export default ReviewsPanel;
