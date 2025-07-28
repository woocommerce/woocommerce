/**
 * External dependencies
 */
import { apiFetch } from '@wordpress/data-controls';
import { select } from '@wordpress/data';
import { addQueryArgs } from '@wordpress/url';

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
	const postType = yield select( storeName ).getEmailPostType();

	// Exit if a fetch operation is already in progress
	if ( isAlreadyFetching ) {
		return;
	}

	// Mark as fetching
	yield setIsFetchingPersonalizationTags( true );

	try {
		const pathWithQuery = addQueryArgs(
			'/woocommerce-email-editor/v1/get_personalization_tags',
			{ postType, context: 'edit' }
		);

		const data = yield apiFetch( {
			path: pathWithQuery,
			method: 'GET',
		} );

		yield setPersonalizationTagsList( data.result );
	} finally {
		// Ensure fetching status is reset
		yield setIsFetchingPersonalizationTags( false );
	}
}
