/**
 * External dependencies
 */
import { mapValues, pick } from 'lodash';
import { useDispatch, useSelect } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { STORE_NAME } from './constants';

/**
 * Retrieve and decode the user's WooCommerce meta values.
 *
 * @param {Object} user WP User object.
 * @return {Object} User's WooCommerce preferences.
 */
const getWooCommerceMeta = ( user ) => {
	const wooMeta = user.woocommerce_meta || {};

	const userData = mapValues( wooMeta, ( data, key ) => {
		if ( ! data || data.length === 0 ) {
			return '';
		}
		try {
			return JSON.parse( data );
		} catch ( e ) {
			/* eslint-disable no-console */
			console.error(
				`Error parsing value '${ data }' for ${ key }`,
				e.message
			);
			/* eslint-enable no-console */
			return '';
		}
	} );

	return userData;
};

// Create wrapper for updating user's `woocommerce_meta`.
async function updateUserPrefs(
	receiveCurrentUser,
	user,
	saveUser,
	getLastEntitySaveError,
	userPrefs
) {
	// @todo Handle unresolved getCurrentUser() here.

	// Whitelist our meta fields.
	const userDataFields = [
		'categories_report_columns',
		'coupons_report_columns',
		'customers_report_columns',
		'orders_report_columns',
		'products_report_columns',
		'revenue_report_columns',
		'taxes_report_columns',
		'variations_report_columns',
		'dashboard_sections',
		'dashboard_chart_type',
		'dashboard_chart_interval',
		'dashboard_leaderboard_rows',
		'activity_panel_inbox_last_read',
		'homepage_layout',
		'homepage_stats',
		'android_app_banner_dismissed',
		'task_list_tracked_started_tasks',
		'help_panel_highlight_shown',
	];

	// Prep valid fields for update.
	const metaData = mapValues(
		pick( userPrefs, userDataFields ),
		JSON.stringify
	);

	if ( Object.keys( metaData ).length === 0 ) {
		return {
			error: new Error(
				'No valid woocommerce_meta keys were provided for update.'
			),
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
	let { saveUser } = dispatch;

	const userData = useSelect( ( select ) => {
		const {
			getCurrentUser,
			getEntity,
			getEntityRecord,
			getLastEntitySaveError,
			hasStartedResolution,
			hasFinishedResolution,
		} = select( STORE_NAME );

		return {
			isRequesting:
				hasStartedResolution( 'getCurrentUser' ) &&
				! hasFinishedResolution( 'getCurrentUser' ),
			user: getCurrentUser(),
			getCurrentUser,
			getEntity,
			getEntityRecord,
			getLastEntitySaveError,
		};
	} );

	const updateUserPreferences = ( userPrefs ) => {
		// WP 5.3.x doesn't have the User entity defined.
		if ( typeof saveUser !== 'function' ) {
			// Polyfill saveUser() - wrapper of saveEntityRecord.
			saveUser = async ( userToSave ) => {
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
		const currentUser = userData.getCurrentUser();
		return updateUserPrefs(
			receiveCurrentUser,
			currentUser,
			saveUser,
			userData.getLastEntitySaveError,
			userPrefs
		);
	};

	const userPreferences = userData.user
		? getWooCommerceMeta( userData.user )
		: {};

	return {
		isRequesting: userData.isRequesting,
		...userPreferences,
		updateUserPreferences,
	};
};
