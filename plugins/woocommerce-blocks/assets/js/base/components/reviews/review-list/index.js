/**
 * External dependencies
 */
import PropTypes from 'prop-types';
import { getSetting } from '@woocommerce/settings';

/**
 * Internal dependencies
 */
import ReviewListItem from '../review-list-item';
import './style.scss';

const ReviewList = ( { attributes, reviews } ) => {
	const showAvatars = getSetting( 'showAvatars', true );
	const reviewRatingsEnabled = getSetting( 'reviewRatingsEnabled', true );
	const showReviewImage =
		( showAvatars || attributes.imageType === 'product' ) &&
		attributes.showReviewImage;
	const showReviewRating =
		reviewRatingsEnabled && attributes.showReviewRating;
	const attrs = {
		...attributes,
		showReviewImage,
		showReviewRating,
	};

	return (
		<ul className="wc-block-review-list wc-block-components-review-list">
			{ reviews.length === 0 ? (
				<ReviewListItem attributes={ attrs } />
			) : (
				reviews.map( ( review, i ) => (
					<ReviewListItem
						key={ review.id || i }
						attributes={ attrs }
						review={ review }
					/>
				) )
			) }
		</ul>
	);
};

ReviewList.propTypes = {
	attributes: PropTypes.object.isRequired,
	reviews: PropTypes.array.isRequired,
};

export default ReviewList;
