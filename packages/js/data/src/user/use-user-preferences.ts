/**
 * External dependencies
 */
import { mapValues } from 'lodash';
import { useDispatch, useSelect } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { STORE_NAME } from './constants';
import { WCUser, UserPreferences } from './types';

/**
 * Retrieve and decode the user's WooCommerce meta values.
 *
 * @param {Object} user WP User object.
 * @return {Object} User's WooCommerce preferences.
 */
const getWooCommerceMeta = ( user: WCUser ) => {
	const wooMeta = user.woocommerce_meta || {};

	const userData = mapValues( wooMeta, ( data, key ) => {
		if ( ! data || data.length === 0 ) {
			return '';
		}
		try {
			return JSON.parse( data );
		} catch ( e ) {
			if ( e instanceof Error ) {
				/* eslint-disable no-console */
				console.error(
					`Error parsing value '${ data }' for ${ key }`,
					e.message
				);
				/* eslint-enable no-console */
			} else {
				/* eslint-disable no-console */
				console.error(
					`Unexpected Error parsing value '${ data }' for ${ key } ${ e }`
				);
				/* eslint-enable no-console */
			}
			return '';
		}
	} );

	return userData;
};

// Create wrapper for updating user's `woocommerce_meta`.
async function updateUserPrefs(
	receiveCurrentUser: ( user: WCUser ) => void,
	user: WCUser,
	saveUser: ( userToSave: {
		id: number;
		woocommerce_meta: { [ key: string ]: boolean };
	} ) => WCUser,
	getLastEntitySaveError: (
		kind: string,
		name: string,
		recordId: number
	) => unknown,
	userPrefs: UserPreferences
) {
	// @todo Handle unresolved getCurrentUser() here.
	// Prep fields for update.
	const metaData = mapValues( userPrefs, JSON.stringify );

	if ( Object.keys( metaData ).length === 0 ) {
		return {
			error: new Error( 'Invalid woocommerce_meta data for update.' ),
			updatedUser: undefined,
		};
	}

	// Optimistically propagate new woocommerce_meta to the store for instant update.
	receiveCurrentUser( {
		...user,
		woocommerce_meta: {
			...user.woocommerce_meta,
			...metaData,
		},
	} );

	// Use saveUser() to update WooCommerce meta values.
	const updatedUser = await saveUser( {
		id: user.id,
		woocommerce_meta: metaData,
	} );

	if ( undefined === updatedUser ) {
		// Return the encountered error to the caller.
		const error = getLastEntitySaveError( 'root', 'user', user.id );

		return {
			error,
			updatedUser,
		};
	}

	// Decode the WooCommerce meta after save.
	const updatedUserResponse = {
		...updatedUser,
		woocommerce_meta: getWooCommerceMeta( updatedUser ),
	};

	return {
		updatedUser: updatedUserResponse,
	};
}

/**
 * Custom react hook for retrieving thecurrent user's WooCommerce preferences.
 *
 * This is a wrapper around @wordpress/core-data's getCurrentUser() and saveUser().
 */
export const useUserPreferences = () => {
	// Get our dispatch methods now - this can't happen inside the callback below.
	const dispatch = useDispatch( STORE_NAME );
	const { addEntities, receiveCurrentUser, saveEntityRecord } = dispatch;
	// eslint-disable-next-line @typescript-eslint/ban-ts-comment
	// @ts-ignore
	let { saveUser } = dispatch;

	const userData = useSelect( ( select ) => {
		const {
			getCurrentUser,
			getEntity,
			getEntityRecord,
			// eslint-disable-next-line @typescript-eslint/ban-ts-comment
			// @ts-ignore
			getLastEntitySaveError,
			// eslint-disable-next-line @typescript-eslint/ban-ts-comment
			// @ts-ignore
			hasStartedResolution,
			// eslint-disable-next-line @typescript-eslint/ban-ts-comment
			// @ts-ignore
			hasFinishedResolution,
		} = select( STORE_NAME );

		return {
			isRequesting:
				hasStartedResolution( 'getCurrentUser' ) &&
				! hasFinishedResolution( 'getCurrentUser' ),
			user: getCurrentUser() as WCUser,
			getCurrentUser,
			getEntity,
			getEntityRecord,
			getLastEntitySaveError,
		};
	} );

	const updateUserPreferences = <
		T extends Record< string, unknown > = UserPreferences
	>(
		userPrefs: UserPreferences | T
	) => {
		// WP 5.3.x doesn't have the User entity defined.
		if ( typeof saveUser !== 'function' ) {
			// eslint-disable-next-line @typescript-eslint/ban-ts-comment
			// @ts-ignore
			saveUser = async ( userToSave: {
				id: number;
				woocommerce_meta: { [ key: string ]: boolean };
			} ) => {
				const entityDefined = Boolean(
					userData.getEntity( 'root', 'user' )
				);
				if ( ! entityDefined ) {
					// Add the User entity so saveEntityRecord works.
					await addEntities( [
						{
							name: 'user',
							kind: 'root',
							baseURL: '/wp/v2/users',
							plural: 'users',
						},
					] );
				}

				// Fire off the save action.
				await saveEntityRecord( 'root', 'user', userToSave );

				// Respond with the updated user.
				return userData.getEntityRecord(
					'root',
					'user',
					userToSave.id
				);
			};
		}
		// Get most recent user before update.
		const currentUser = userData.getCurrentUser() as WCUser;
		return updateUserPrefs(
			receiveCurrentUser,
			currentUser,
			saveUser,
			userData.getLastEntitySaveError,
			userPrefs
		);
	};

	const userPreferences: UserPreferences = userData.user
		? getWooCommerceMeta( userData.user )
		: {};

	return {
		isRequesting: userData.isRequesting,
		...userPreferences,
		updateUserPreferences,
	};
};
