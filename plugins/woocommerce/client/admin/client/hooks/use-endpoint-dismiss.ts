/**
 * External dependencies
 */
import apiFetch from '@wordpress/api-fetch';
import { useState } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { createNoticesFromResponse } from '../lib/notices';
import type { DismissState } from './use-option-dismiss';

/**
 * Dismissal state persisted through a dedicated REST endpoint.
 *
 * Used when the dismissal cannot ride the (frozen) options REST API and instead
 * has a purpose-built endpoint. The initial state is supplied by the caller
 * (typically preloaded into wc-admin settings) so the card renders the correct
 * visibility without an extra request.
 *
 * The dismissal is optimistic: the card hides immediately, then the endpoint is
 * called. If the request fails the card is restored and an error notice is
 * surfaced so the failure is not silent.
 *
 * @param path    The REST path to POST to in order to persist the dismissal.
 * @param initial The initial dismissal state (e.g. from preloaded settings).
 * @return The current dismissal state and a callback to dismiss.
 */
export const useEndpointDismiss = (
	path: string,
	initial: boolean
): DismissState => {
	const [ isDismissed, setIsDismissed ] = useState< boolean >( initial );

	const onDismiss = () => {
		// Optimistically hide the card, then persist the dismissal site-wide.
		setIsDismissed( true );
		apiFetch( { path, method: 'POST' } ).catch( ( response ) => {
			// Restore the card and surface the failure so the state stays
			// accurate and the merchant is not left without feedback.
			setIsDismissed( false );
			createNoticesFromResponse( response );
		} );
	};

	return { isDismissed, hasResolved: true, onDismiss };
};
