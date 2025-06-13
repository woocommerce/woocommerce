/**
 * External dependencies
 */
import { apiFetch } from '@wordpress/data-controls';
import { select } from '@wordpress/data';

/**
 * Internal dependencies
 */
import {
	setPersonalizationTagsList,
	setIsFetchingPersonalizationTags,
} from './actions';
import { storeName } from './constants';

export function* getPersonalizationTagsList() {
	// Access the state to check if already fetching
	const state = yield select( storeName );
	const isAlreadyFetching = state.personalizationTags?.isFetching;

	// Exit if a fetch operation is already in progress
	if ( isAlreadyFetching ) {
		return;
	}

	// Mark as fetching
	yield setIsFetchingPersonalizationTags( true );

	try {
		const data = yield apiFetch( {
			path: `/woocommerce-email-editor/v1/get_personalization_tags`,
			method: 'GET',
		} );

		yield setPersonalizationTagsList( data.result );
	} finally {
		// Ensure fetching status is reset
		yield setIsFetchingPersonalizationTags( false );
	}
}
