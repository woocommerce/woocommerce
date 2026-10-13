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
				setState( isInstall ? 'installed' : 'active' );
			} catch {
				failedAction.current = action;
				setState( 'error' );
			}
		},
		[ isForced, settings.purpleSlug ]
	);

	return {
		state,
		install: () => run( 'install' ),
		activate: () => run( 'activate' ),
		retry: () => run( failedAction.current ),
	};
};
