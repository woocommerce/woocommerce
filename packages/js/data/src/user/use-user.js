/**
 * External dependencies
 */
import { useSelect } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { STORE_NAME } from './constants';

/**
 * Custom react hook for shortcut methods around user.
 *
 * This is a wrapper around @wordpress/core-data's getCurrentUser().
 */
export const useUser = () => {
	const userData = useSelect( ( select ) => {
		const { getCurrentUser, hasStartedResolution, hasFinishedResolution } =
			select( STORE_NAME );

		return {
			isRequesting:
				hasStartedResolution( 'getCurrentUser' ) &&
				! hasFinishedResolution( 'getCurrentUser' ),
			user: getCurrentUser(),
			getCurrentUser,
		};
	} );

	const currentUserCan = ( capability ) => {
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
