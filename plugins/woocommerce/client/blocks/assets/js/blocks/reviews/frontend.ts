/**
 * External dependencies
 */
import { renderFrontend } from '@woocommerce/base-utils';

/**
 * Internal dependencies
 */
import FrontendContainerBlock from './frontend-container-block';

const selector = `
	.wp-block-woocommerce-all-reviews,
	.wp-block-woocommerce-reviews-by-product,
	.wp-block-woocommerce-reviews-by-category
`;

const getProps = ( el: HTMLElement ) => {
	const showOrderby = el.dataset.showOrderby === 'true';
	const showLoadMore = el.dataset.showLoadMore === 'true';
	const parsedOffset = Number( el.dataset.offset ?? 0 );
	const offset =
		Number.isInteger( parsedOffset ) && parsedOffset >= 0
			? parsedOffset
			: 0;

	const isFilteredReviewsBlock =
		el.classList.contains( 'wp-block-woocommerce-reviews-by-product' ) ||
		el.classList.contains( 'wp-block-woocommerce-reviews-by-category' );

	return {
		attributes: {
			offset,
			isFilteredReviewsBlock,
			showOrderby,
			showLoadMore,
			showReviewDate: el.classList.contains( 'has-date' ),
			showReviewerName: el.classList.contains( 'has-name' ),
			showReviewImage: el.classList.contains( 'has-image' ),
			showReviewRating: el.classList.contains( 'has-rating' ),
			showReviewContent: el.classList.contains( 'has-content' ),
			showProductName: el.classList.contains( 'has-product-name' ),
		},
	};
};

renderFrontend( { selector, Block: FrontendContainerBlock, getProps } );
