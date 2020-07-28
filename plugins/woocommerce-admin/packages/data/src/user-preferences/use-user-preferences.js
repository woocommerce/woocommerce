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

	const userData = mapValues( wooMeta, ( data ) => {
		if ( ! data || data.length === 0 ) {
			return '';
		}
		return JSON.parse( data );
	} );

	return userData;
};

/**
 * Custom react hook for retrieving thecurrent user's WooCommerce preferences.
 *
 * This is a wrapper around @wordpress/core-data's getCurrentUser() and saveUser().
 */
export const useUserPreferences = () => {
	// Get our dispatch methods now - this can't happen inside the callback below.
	const { receiveCurrentUser, saveUser } = useDispatch( STORE_NAME );

	const { isRequesting, userPreferences, updateUserPreferences } = useSelect(
		( select ) => {
			const {
				getCurrentUser,
				getLastEntitySaveError,
				hasStartedResolution,
				hasFinishedResolution,
			} = select( STORE_NAME );

			// Use getCurrentUser() to get WooCommerce meta values.
			const user = getCurrentUser();
			const userData = getWooCommerceMeta( user );

			// Create wrapper for updating user's `woocommerce_meta`.
			const updateUserPrefs = async ( userPrefs ) => {
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
					'homepage_stats',
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
					const error = getLastEntitySaveError(
						'root',
						'user',
						user.id
					);

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
			};

			return {
				isRequesting:
					hasStartedResolution( 'getCurrentUser' ) &&
					! hasFinishedResolution( 'getCurrentUser' ),
				userPreferences: userData,
				updateUserPreferences: updateUserPrefs,
			};
		}
	);

	return {
		isRequesting,
		...userPreferences,
		updateUserPreferences,
	};
};
