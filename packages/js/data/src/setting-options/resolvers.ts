/**
 * External dependencies
 */
import apiFetch from '@wordpress/api-fetch';

/**
 * Internal dependencies
 */
import { receiveGroups, receiveSettings, setError } from './actions';
import type { Setting, SettingsGroup } from './types';
import type { ThunkArgs } from './actions';
import { NAMESPACE } from '../constants';
import { STORE_NAME } from './';

/**
 * Resolver for the getGroups selector. Fetches all available settings groups.
 */
export const getGroups =
	() =>
	async ( { dispatch }: ThunkArgs ) => {
		try {
			const groups: SettingsGroup[] = await apiFetch( {
				path: '/wc/v3/settings',
			} );

			dispatch( receiveGroups( groups ) );
			return groups;
		} catch ( error ) {
			throw error;
		}
	};

/**
 * Resolver for the getSettings selector. Fetches settings for a specific group.
 */
export const getSettings =
	( groupId: string ) =>
	async ( { dispatch }: ThunkArgs ) => {
		const lock = await dispatch.__unstableAcquireStoreLock(
			STORE_NAME,
			[ 'settings', groupId ],
			{ exclusive: false }
		);

		try {
			const settings: Setting[] = await apiFetch( {
				path: `${ NAMESPACE }/settings/${ groupId }`,
			} );

			dispatch( receiveSettings( groupId, settings ) );
			return settings;
		} catch ( error ) {
			dispatch(
				setError(
					groupId,
					null,
					error instanceof Error
						? error
						: new Error( String( error ) )
				)
			);
			throw error;
		} finally {
			dispatch.__unstableReleaseStoreLock( lock );
		}
	};

/**
 * Resolver for the getSetting selector. Fetches a specific setting.
 */
export const getSetting =
	( groupId: string, settingId: string ) =>
	async ( { dispatch }: ThunkArgs ) => {
		const lock = await dispatch.__unstableAcquireStoreLock(
			STORE_NAME,
			[ 'settings', groupId, settingId ],
			{ exclusive: false }
		);

		try {
			const setting: Setting = await apiFetch( {
				path: `${ NAMESPACE }/settings/${ groupId }/${ settingId }`,
			} );

			dispatch( receiveSettings( groupId, [ setting ] ) );
			return setting;
		} catch ( error ) {
			dispatch(
				setError(
					groupId,
					settingId,
					error instanceof Error
						? error
						: new Error( String( error ) )
				)
			);
			throw error;
		} finally {
			dispatch.__unstableReleaseStoreLock( lock );
		}
	};

/**
 * Resolver for the getSettingValue selector. Triggers getSetting resolver.
 *
 * @param groupId   - The settings group ID
 * @param settingId - The setting ID
 */
export const getSettingValue =
	( groupId: string, settingId: string ) => async ( args: ThunkArgs ) => {
		const setting = await getSetting( groupId, settingId )( args );
		return setting?.value;
	};
