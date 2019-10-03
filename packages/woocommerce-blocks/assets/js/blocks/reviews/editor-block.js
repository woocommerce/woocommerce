/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Component } from 'react';
import PropTypes from 'prop-types';
import { Disabled } from '@wordpress/components';
import { ENABLE_REVIEW_RATING } from '@woocommerce/block-settings';

/**
 * Internal dependencies
 */
import ApiErrorPlaceholder from '../../components/api-error-placeholder';
import LoadMoreButton from '../../base/components/load-more-button';
import ReviewList from '../../base/components/review-list';
import ReviewOrderSelect from '../../base/components/review-order-select';
import withReviews from '../../base/hocs/with-reviews';

/**
 * Block rendered in the editor.
 */
class EditorBlock extends Component {
	static propTypes = {
		/**
		 * The attributes for this block.
		 */
		attributes: PropTypes.object.isRequired,
		// from withReviews
		reviews: PropTypes.array,
		totalReviews: PropTypes.number,
	}

	render() {
		const { attributes, error, isLoading, noReviewsPlaceholder: NoReviewsPlaceholder, reviews, totalReviews } = this.props;

		if ( error ) {
			return (
				<ApiErrorPlaceholder
					className="wc-block-featured-product-error"
					error={ error }
					isLoading={ isLoading }
				/>
			);
		}

		if ( 0 === reviews.length && ! isLoading ) {
			return <NoReviewsPlaceholder attributes={ attributes } />;
		}

		return (
			<Disabled>
				{ ( attributes.showOrderby && ENABLE_REVIEW_RATING ) && (
					<ReviewOrderSelect
						readOnly
						value={ attributes.orderby }
					/>
				) }
				<ReviewList
					attributes={ attributes }
					reviews={ reviews }
				/>
				{ ( attributes.showLoadMore && totalReviews > reviews.length ) && (
					<LoadMoreButton
						screenReaderLabel={ __( 'Load more reviews', 'woocommerce' ) }
					/>
				) }
			</Disabled>
		);
	}
}

export default withReviews( EditorBlock );
