/**
 * External dependencies
 */
import { useState, useEffect } from '@wordpress/element';

export const useNetworkStatus = () => {
	const [ isNetworkOffline, setIsNetworkOffline ] = useState( false );

	useEffect( () => {
		const offlineEventHandler = () => {
			setIsNetworkOffline( true );
		};

		const onlineEventHandler = () => {
			setIsNetworkOffline( false );
		};

		window.addEventListener( 'offline', offlineEventHandler );
		window.addEventListener( 'online', onlineEventHandler );

		return () => {
			window.removeEventListener( 'offline', offlineEventHandler );
			window.removeEventListener( 'online', onlineEventHandler );
		};
	}, [] );

	return isNetworkOffline;
};
