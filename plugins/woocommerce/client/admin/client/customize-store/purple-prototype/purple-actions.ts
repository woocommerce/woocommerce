/**
 * External dependencies
 */
import apiFetch from '@wordpress/api-fetch';

/**
 * Internal dependencies
 */
import type { PurplePrototypeSettings, PurpleState } from './settings';

const FAKE_DELAY_MS = 1500;

export type PurpleAction = 'install' | 'activate';

const wait = ( ms: number ) =>
	new Promise( ( resolve ) => setTimeout( resolve, ms ) );

export const isForcedState = ( settings: PurplePrototypeSettings ) =>
	settings.state !== 'real';

/**
 * Save a forced state after a fake step, so a reload shows where the flow got to.
 */
export const saveForcedState = (
	settings: PurplePrototypeSettings,
	state: PurpleState
) => {
	const body = new window.FormData();
	body.append( 'action', 'woocommerce_purple_cys_prototype_save_state' );
	body.append( '_ajax_nonce', settings.nonce );
	body.append( 'state', state );
	return window
		.fetch( settings.ajaxUrl, {
			method: 'POST',
			credentials: 'same-origin',
			body,
		} )
		.catch( () => undefined );
};

/**
 * Install or activate Purple. With the "Real" setting this calls the onboarding theme
 * endpoints. With a forced state it waits a moment and saves the new state instead.
 */
export const runPurpleAction = async (
	settings: PurplePrototypeSettings,
	action: PurpleAction
) => {
	if ( ! isForcedState( settings ) ) {
		await apiFetch( {
			path: `/wc-admin/onboarding/themes/${ action }`,
			method: 'POST',
			data: { theme: settings.purpleSlug },
		} );
		return;
	}

	await wait( FAKE_DELAY_MS );
	void saveForcedState(
		settings,
		action === 'install' ? 'installed' : 'active'
	);
};
