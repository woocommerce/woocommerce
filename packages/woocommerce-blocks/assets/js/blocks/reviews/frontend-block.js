/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Fragment } from 'react';
import PropTypes from 'prop-types';
import { ENABLE_REVIEW_RATING } from '@woocommerce/block-settings';

/**
 * Internal dependencies
 */
import LoadMoreButton from '../../base/components/load-more-button';
import ReviewOrderSelect from '../../base/components/review-order-select';
import ReviewList from '../../base/components/review-list';
import withReviews from '../../base/hocs/with-reviews';

/**
 * Block rendered in the frontend.
 */
const FrontendBlock = ( { attributes, onAppendReviews, onChangeOrderby, reviews, totalReviews } ) => {
	const { orderby } = attributes;

	if ( 0 === reviews.length ) {
		return null;
	}

	return (
		<Fragment>
			{ ( attributes.showOrderby !== 'false' && ENABLE_REVIEW_RATING ) && (
				<ReviewOrderSelect
					defaultValue={ orderby }
					onChange={ onChangeOrderby }
				/>
			) }
			<ReviewList
				attributes={ attributes }
				reviews={ reviews }
			/>
			{ ( attributes.showLoadMore !== 'false' && totalReviews > reviews.length ) && (
				<LoadMoreButton
					onClick={ onAppendReviews }
					screenReaderLabel={ __( 'Load more reviews', 'woocommerce' ) }
				/>
			) }
		</Fragment>
	);
};

FrontendBlock.propTypes = {
	/**
	 * The attributes for this block.
	 */
	attributes: PropTypes.object.isRequired,
	onAppendReviews: PropTypes.func,
	onChangeArgs: PropTypes.func,
	// from withReviewsattributes
	reviews: PropTypes.array,
	totalReviews: PropTypes.number,
};

export default withReviews( FrontendBlock );
