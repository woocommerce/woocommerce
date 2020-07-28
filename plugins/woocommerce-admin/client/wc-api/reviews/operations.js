/**
 * External dependencies
 */
import { addQueryArgs } from '@wordpress/url';
import apiFetch from '@wordpress/api-fetch';

/**
 * Internal dependencies
 */
import {
	isResourcePrefix,
	getResourceIdentifier,
	getResourceName,
} from '../utils';
import { NAMESPACE } from '../constants';

function read( resourceNames, fetch = apiFetch ) {
	return [
		...readReviews( resourceNames, fetch ),
		...readReviewQueries( resourceNames, fetch ),
	];
}

function readReviewQueries( resourceNames, fetch ) {
	const filteredNames = resourceNames.filter( ( name ) =>
		isResourcePrefix( name, 'review-query' )
	);

	return filteredNames.map( async ( resourceName ) => {
		const query = getResourceIdentifier( resourceName );
		const url = addQueryArgs( `${ NAMESPACE }/products/reviews`, query );

		try {
			const response = await fetch( {
				parse: false,
				path: url,
			} );

			const reviews = await response.json();
			const totalCount = parseInt(
				response.headers.get( 'x-wp-total' ),
				10
			);
			const ids = reviews.map( ( review ) => review.id );
			const reviewResources = reviews.reduce( ( resources, review ) => {
				resources[ getResourceName( 'review', review.id ) ] = {
					data: review,
				};
				return resources;
			}, {} );

			return {
				[ resourceName ]: {
					data: ids,
					totalCount,
				},
				...reviewResources,
			};
		} catch ( error ) {
			return { [ resourceName ]: { error } };
		}
	} );
}

function readReviews( resourceNames, fetch ) {
	const filteredNames = resourceNames.filter( ( name ) =>
		isResourcePrefix( name, 'review' )
	);
	return filteredNames.map( ( resourceName ) =>
		readReview( resourceName, fetch )
	);
}

function readReview( resourceName, fetch ) {
	const id = getResourceIdentifier( resourceName );
	const url = `${ NAMESPACE }/products/reviews/${ id }`;

	return fetch( { path: url } )
		.then( ( review ) => {
			return { [ resourceName ]: { data: review } };
		} )
		.catch( ( error ) => {
			return { [ resourceName ]: { error } };
		} );
}

export default {
	read,
};
