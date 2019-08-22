/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Fragment } from 'react';
import PropTypes from 'prop-types';

/**
 * Internal dependencies
 */
import LoadMoreButton from '../../base/components/load-more-button';
import ReviewOrderSelect from '../../base/components/review-order-select';
import ReviewList from '../../base/components/review-list';
import withComponentId from '../../base/hocs/with-component-id';
import withReviews from '../../base/hocs/with-reviews';
import { ENABLE_REVIEW_RATING } from '../../constants';

/**
 * Block rendered in the frontend.
 */
const FrontendBlock = ( { attributes, componentId, onAppendReviews, onChangeOrderby, reviews, totalReviews } ) => {
	const { orderby } = attributes;

	if ( 0 === reviews.length ) {
		return null;
	}

	return (
		<Fragment>
			{ ( attributes.showOrderby && ENABLE_REVIEW_RATING ) && (
				<ReviewOrderSelect
					componentId={ componentId }
					defaultValue={ orderby }
					onChange={ onChangeOrderby }
				/>
			) }
			<ReviewList
				attributes={ attributes }
				componentId={ componentId }
				reviews={ reviews }
			/>
			{ ( attributes.showLoadMore && totalReviews > reviews.length ) && (
				<LoadMoreButton
					onClick={ onAppendReviews }
					screenReaderLabel={ __( 'Load more reviews', 'woo-gutenberg-products-block' ) }
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
	// from withComponentId
	componentId: PropTypes.number,
	// from withReviewsattributes
	reviews: PropTypes.array,
	totalReviews: PropTypes.number,
};

export default withComponentId( withReviews( FrontendBlock ) );
