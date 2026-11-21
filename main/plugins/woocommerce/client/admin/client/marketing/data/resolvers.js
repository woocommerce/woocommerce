/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { apiFetch } from '@wordpress/data-controls';

/**
 * Internal dependencies
 */
import {
	handleFetchError,
	receiveRecommendedPlugins,
	receiveMiscRecommendations,
	receiveBlogPosts,
	setError,
} from './actions';
import { API_NAMESPACE } from './constants';

export function* getRecommendedPlugins( category ) {
	try {
		const categoryParam = yield category ? `&category=${ category }` : '';
		const response = yield apiFetch( {
			path: `${ API_NAMESPACE }/recommended?per_page=50${ categoryParam }`,
		} );

		if ( response ) {
			yield receiveRecommendedPlugins( response, category );
		} else {
			throw new Error();
		}
	} catch ( error ) {
		yield handleFetchError(
			error,
			__(
				'There was an error loading recommended extensions.',
				'woocommerce'
			)
		);
	}
}

export function* getMiscRecommendations() {
	try {
		const response = yield apiFetch( {
			path: `${ API_NAMESPACE }/misc-recommendations`,
		} );

		if ( response ) {
			yield receiveMiscRecommendations( response );
		} else {
			throw new Error();
		}
	} catch ( error ) {
		yield handleFetchError(
			error,
			__(
				'There was an error loading misc recommendations',
				'woocommerce'
			)
		);
	}
}

export function* getBlogPosts( category ) {
	try {
		const categoryParam = yield category ? `?category=${ category }` : '';
		const response = yield apiFetch( {
			path: `${ API_NAMESPACE }/knowledge-base${ categoryParam }`,
			method: 'GET',
		} );

		if ( response ) {
			yield receiveBlogPosts( response, category );
		} else {
			throw new Error();
		}
	} catch ( error ) {
		yield setError( category, error );
	}
}
