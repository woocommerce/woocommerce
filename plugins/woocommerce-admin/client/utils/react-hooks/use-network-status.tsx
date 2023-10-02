/**
 * External dependencies
 */
import { useState, useEffect } from '@wordpress/element';

export const useNetworkStatus = () => {
	const [ isNetworkOffline, setIsNetworkOffline ] = useState( false );

	useEffect( () => {
		const setOfflineBannerImage = () => {
			setIsNetworkOffline( true );
		};

		const removeOfflineBannerImage = () => {
			setIsNetworkOffline( false );
		};

		window.addEventListener( 'offline', setOfflineBannerImage );
		window.addEventListener( 'online', removeOfflineBannerImage );

		return () => {
			window.removeEventListener( 'offline', setOfflineBannerImage );
			window.removeEventListener( 'online', removeOfflineBannerImage );
		};
	}, [] );

	return isNetworkOffline;
};
