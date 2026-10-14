/**
 * External dependencies
 */
import { useCallback, useRef, useState } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';

/**
 * Internal dependencies
 */
import type { PurplePrototypeSettings, PurpleState } from './settings';

const FAKE_DELAY_MS = 1500;

type PurpleAction = 'install' | 'activate';

const wait = ( ms: number ) =>
	new Promise( ( resolve ) => setTimeout( resolve, ms ) );

/**
 * Save a forced state after a fake step, so a reload shows where the flow got to.
 */
const saveForcedState = (
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

const getRealState = ( settings: PurplePrototypeSettings ): PurpleState => {
	if ( settings.purpleActive ) {
		return 'active';
	}
	return settings.purpleInstalled ? 'installed' : 'default';
};

/**
 * Purple card state. With the "Real" setting, install and activate call the onboarding theme
 * endpoints. With a forced state, they step through the states after a short fake delay.
 */
export const usePurpleState = ( settings: PurplePrototypeSettings ) => {
	const isForced = settings.state !== 'real';
	const [ state, setState ] = useState< PurpleState >(
		isForced ? ( settings.state as PurpleState ) : getRealState( settings )
	);
	const [ justActivated, setJustActivated ] = useState( false );
	const failedAction = useRef< PurpleAction >( 'install' );

	const run = useCallback(
		async ( action: PurpleAction ) => {
			const isInstall = action === 'install';
			setState( isInstall ? 'installing' : 'activating' );

			try {
				if ( isForced ) {
					await wait( FAKE_DELAY_MS );
				} else {
					await apiFetch( {
						path: `/wc-admin/onboarding/themes/${ action }`,
						method: 'POST',
						data: { theme: settings.purpleSlug },
					} );
				}
				const nextState = isInstall ? 'installed' : 'active';
				setState( nextState );
				setJustActivated( ! isInstall );
				if ( isForced ) {
					void saveForcedState( settings, nextState );
				}
			} catch {
				failedAction.current = action;
				setState( 'error' );
			}
		},
		[ isForced, settings ]
	);

	return {
		state,
		// True once Purple is activated from this screen, as opposed to already active on load.
		justActivated,
		install: () => run( 'install' ),
		activate: () => run( 'activate' ),
		retry: () => run( failedAction.current ),
	};
};
