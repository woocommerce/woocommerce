/**
 * External dependencies
 */
import apiFetch from '@wordpress/api-fetch';
import classNames from 'classnames';
import { ENABLE_REVIEW_RATING } from '@woocommerce/block-settings';

export const getOrderArgs = ( orderValue ) => {
	if ( ENABLE_REVIEW_RATING ) {
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

export const getBlockClassName = ( blockClassName, attributes ) => {
	const { className, showReviewDate, showReviewerName, showReviewContent, showProductName, showReviewImage, showReviewRating } = attributes;

	return classNames(
		blockClassName,
		className,
		{
			'has-image': showReviewImage,
			'has-name': showReviewerName,
			'has-date': showReviewDate,
			'has-rating': showReviewRating,
			'has-content': showReviewContent,
			'has-product-name': showProductName,
		}
	);
};
