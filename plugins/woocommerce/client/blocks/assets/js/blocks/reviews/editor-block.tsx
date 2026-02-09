/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Component } from '@wordpress/element';
import { Disabled } from '@wordpress/components';
import { getSetting } from '@woocommerce/settings';
import ErrorPlaceholder, {
	ErrorObject,
} from '@woocommerce/editor-components/error-placeholder';
import LoadMoreButton from '@woocommerce/base-components/load-more-button';
import {
	ReviewList,
	ReviewSortSelect,
} from '@woocommerce/base-components/reviews';
import type { Review } from '@woocommerce/base-components/reviews/types';
import withReviews from '@woocommerce/base-hocs/with-reviews';

/**
 * Internal dependencies
 */
import { ReviewBlockAttributes } from './attributes';

interface EditorBlockProps {
	attributes: ReviewBlockAttributes;
	reviews: Review[];
	totalReviews: number;
	error?: ErrorObject;
	isLoading: boolean;
	noReviewsPlaceholder: React.ComponentType< {
		attributes: EditorBlockProps[ 'attributes' ];
	} >;
}

/**
 * Block rendered in the editor.
 */
class EditorBlock extends Component< EditorBlockProps > {
	render() {
		const {
			attributes,
			error,
			isLoading,
			noReviewsPlaceholder: NoReviewsPlaceholder,
			reviews,
			totalReviews,
		} = this.props;

		if ( error ) {
			return (
				<ErrorPlaceholder
					className="wc-block-featured-product-error"
					error={ error }
					isLoading={ isLoading }
				/>
			);
		}

		if ( reviews.length === 0 && ! isLoading ) {
			return <NoReviewsPlaceholder attributes={ attributes } />;
		}

		const reviewRatingsEnabled = getSetting( 'reviewRatingsEnabled', true );

		return (
			<Disabled>
				{ attributes.showOrderby && reviewRatingsEnabled && (
					<ReviewSortSelect
						readOnly
						value={ attributes.orderby }
						onChange={ () => null }
					/>
				) }

				<ReviewList attributes={ attributes } reviews={ reviews } />
				{ attributes.showLoadMore && totalReviews > reviews.length && (
					<LoadMoreButton
						screenReaderLabel={ __(
							'Load more reviews',
							'woocommerce'
						) }
						onClick={ () => null }
					/>
				) }
			</Disabled>
		);
	}
}

export default withReviews( EditorBlock );
