/**
 * External dependencies
 */
import { render } from 'react-dom';

/**
 * Internal dependencies
 */
import FrontendBlock from './frontend-block.js';

const containers = document.querySelectorAll(
	'.wp-block-woocommerce-reviews-by-product'
);

if ( containers.length ) {
	// Use Array.forEach for IE11 compatibility
	Array.prototype.forEach.call( containers, ( el ) => {
		const attributes = {
			...el.dataset,
			showReviewDate: el.classList.contains( 'has-date' ),
			showReviewerName: el.classList.contains( 'has-name' ),
			showReviewImage: el.classList.contains( 'has-image' ),
			showReviewRating: el.classList.contains( 'has-rating' ),
			showReviewContent: el.classList.contains( 'has-content' ),
		};

		render( <FrontendBlock attributes={ attributes } />, el );
	} );
}
