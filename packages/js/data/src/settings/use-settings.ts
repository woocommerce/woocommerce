/**
 * External dependencies
 */
import { useSelect, useDispatch } from '@wordpress/data';
import { useCallback } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { store } from './';
import type { Settings } from './types';

export const useSettings = ( group: string, settingsKeys: string[] = [] ) => {
	const { requestedSettings, settingsError, isRequesting, isDirty } =
		useSelect(
			( select ) => {
				const {
					getLastSettingsErrorForGroup,
					getSettingsForGroup,
					getIsDirty,
					isUpdateSettingsRequesting,
				} = select( store );
				return {
					requestedSettings: getSettingsForGroup(
						group,
						settingsKeys
					),
					settingsError: Boolean(
						getLastSettingsErrorForGroup( group )
					),
					isRequesting: isUpdateSettingsRequesting( group ),
					isDirty: getIsDirty( group, settingsKeys ),
				};
			},
			[ group, ...settingsKeys.sort() ]
		);
	const {
		persistSettingsForGroup,
		updateAndPersistSettingsForGroup,
		updateSettingsForGroup,
	} = useDispatch( store );
	const updateSettings = useCallback(
		( name: string, data: Settings ) => {
			updateSettingsForGroup( group, { [ name ]: data } );
		},
		[ group ]
	);
	const persistSettings = useCallback( () => {
		// this action would simply persist all settings marked as dirty in the
		// store state and then remove the dirty record in the isDirtyMap
		persistSettingsForGroup( group );
	}, [ group ] );
	const updateAndPersistSettings = useCallback(
		( name: string, data: Settings ) => {
			updateAndPersistSettingsForGroup( group, { [ name ]: data } );
		},
		[ group ]
	);
	return {
		settingsError,
		isRequesting,
		isDirty,
		...requestedSettings,
		persistSettings,
		updateAndPersistSettings,
		updateSettings,
	};
};
