/**
 * External dependencies
 */
import { useCallback, useRef, useState } from '@wordpress/element';

/**
 * Internal dependencies
 */
import type { PurplePrototypeSettings, PurpleState } from './settings';
import { isForcedState, runPurpleAction, PurpleAction } from './purple-actions';
import { completeCustomizeStoreTask } from './task-completion';

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
	const [ state, setState ] = useState< PurpleState >(
		isForcedState( settings )
			? ( settings.state as PurpleState )
			: getRealState( settings )
	);
	const [ justActivated, setJustActivated ] = useState( false );
	const failedAction = useRef< PurpleAction >( 'install' );

	const run = useCallback(
		async ( action: PurpleAction ) => {
			const isInstall = action === 'install';
			setState( isInstall ? 'installing' : 'activating' );
			void completeCustomizeStoreTask();

			try {
				await runPurpleAction( settings, action );
				setState( isInstall ? 'installed' : 'active' );
				setJustActivated( ! isInstall );
			} catch {
				failedAction.current = action;
				setState( 'error' );
			}
		},
		[ settings ]
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
