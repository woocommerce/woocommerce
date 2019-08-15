/**
 * External dependencies
 */
import apiFetch from '@wordpress/api-fetch';

const enableReviewRating = !! ( typeof wc_product_block_data !== 'undefined' && wc_product_block_data.enableReviewRating );

export const getOrderArgs = ( orderValue ) => {
	if ( enableReviewRating ) {
		if ( orderValue === 'lowest-rating' ) {
			return {
				order: 'asc',
				orderby: 'rating',
			};
		}
		if ( orderValue === 'highest-rating' ) {
			return {
				order: 'desc',
				orderby: 'rating',
			};
		}
	}

	return {
		order: 'desc',
		orderby: 'date_gmt',
	};
};

export const getReviews = ( args ) => {
	return apiFetch( {
		path: '/wc/blocks/products/reviews?' + Object.entries( args ).map( ( arg ) => arg.join( '=' ) ).join( '&' ),
		parse: false,
	} ).then( ( response ) => {
		return response.json().then( ( reviews ) => {
			const totalReviews = parseInt( response.headers.get( 'x-wp-total' ), 10 );
			return { reviews, totalReviews };
		} );
	} );
};
