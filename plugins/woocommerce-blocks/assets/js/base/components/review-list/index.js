/**
 * External dependencies
 */
import PropTypes from 'prop-types';

/**
 * Internal dependencies
 */
import ReviewListItem from '../review-list-item';
import { ENABLE_REVIEW_RATING, SHOW_AVATARS } from '../../../constants';
import './style.scss';

const ReviewList = ( { attributes, componentId, reviews } ) => {
	const showReviewImage = ( SHOW_AVATARS || attributes.imageType === 'product' ) && attributes.showReviewImage;
	const showReviewRating = ENABLE_REVIEW_RATING && attributes.showReviewRating;
	const attrs = {
		...attributes,
		showReviewImage,
		showReviewRating,
	};

	return (
		<ul
			key={ `wc-block-review-list-${ componentId }` }
			className="wc-block-review-list"
		>
			{ reviews.length === 0 ?
				(
					<ReviewListItem attributes={ attrs } />
				) : (
					reviews.map( ( review, i ) => (
						<ReviewListItem key={ review.id || i } attributes={ attrs } review={ review } />
					) )
				)
			}
		</ul>
	);
};

ReviewList.propTypes = {
	attributes: PropTypes.object.isRequired,
	componentId: PropTypes.number.isRequired,
	reviews: PropTypes.array.isRequired,
};

export default ReviewList;
