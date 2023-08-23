/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { getSetting } from '@woocommerce/settings';
import LoadMoreButton from '@woocommerce/base-components/load-more-button';
import {
	ReviewList,
	ReviewSortSelect,
} from '@woocommerce/base-components/reviews';
import withReviews from '@woocommerce/base-hocs/with-reviews';
import type { ChangeEventHandler, MouseEventHandler } from 'react';
import { Review } from '@woocommerce/base-components/reviews/types';

/**
 * Internal dependencies
 */
import { ReviewBlockAttributes } from './attributes';

interface FrontendBlockProps {
	attributes: ReviewBlockAttributes;
	onAppendReviews: MouseEventHandler;
	onChangeOrderby: ChangeEventHandler< HTMLSelectElement >;
	sortSelectValue: 'most-recent' | 'highest-rating' | 'lowest-rating';
	reviews: Review[];
	totalReviews: number;
}

const FrontendBlock = ( {
	attributes,
	onAppendReviews,
	onChangeOrderby,
	reviews,
	sortSelectValue,
	totalReviews,
}: FrontendBlockProps ) => {
	if ( reviews.length === 0 ) {
		return null;
	}

	const reviewRatingsEnabled = getSetting< boolean >(
		'reviewRatingsEnabled',
		true
	);

	return (
		<>
			{ attributes.showOrderby && reviewRatingsEnabled && (
				<ReviewSortSelect
					value={ sortSelectValue }
					onChange={ onChangeOrderby }
					readOnly
				/>
			) }
			<ReviewList attributes={ attributes } reviews={ reviews } />
			{ attributes.showLoadMore && totalReviews > reviews.length && (
				<LoadMoreButton
					onClick={ onAppendReviews }
					screenReaderLabel={ __(
						'Load more reviews',
						'woo-gutenberg-products-block'
					) }
				/>
			) }
		</>
	);
};

export default withReviews( FrontendBlock );
