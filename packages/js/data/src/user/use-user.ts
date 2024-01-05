/**
 * External dependencies
 */
import { useSelect } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { STORE_NAME } from './constants';
import { WCUser } from './types';

/**
 * Custom react hook for shortcut methods around user.
 *
 * This is a wrapper around @wordpress/core-data's getCurrentUser().
 */
export const useUser = () => {
	const userData = useSelect( ( select ) => {
		// TODO: Update @types/wordpress__core-data to include the 'hasStartedResolution', 'hasFinishedResolution' method.
		const { getCurrentUser, hasStartedResolution, hasFinishedResolution } =
			select( STORE_NAME );

		return {
			isRequesting:
				hasStartedResolution( 'getCurrentUser' ) &&
				! hasFinishedResolution( 'getCurrentUser' ),
			// We register additional user data in backend so we need to use a type assertion here for WC user.
			user: getCurrentUser() as WCUser< 'capabilities' >,
			getCurrentUser,
		};
	} );

	const currentUserCan = ( capability: string ) => {
		if ( userData.user && userData.user.is_super_admin ) {
			return true;
		}

		if ( userData.user && userData.user.capabilities[ capability ] ) {
			return true;
		}

		return false;
	};

	return {
		currentUserCan,
		user: userData.user,
		isRequesting: userData.isRequesting,
	};
};
