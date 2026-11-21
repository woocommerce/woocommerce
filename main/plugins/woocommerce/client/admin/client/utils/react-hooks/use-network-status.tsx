/**
 * External dependencies
 */
import { useState, useEffect } from '@wordpress/element';

export const useNetworkStatus = () => {
	// Initialize from navigator.onLine so pages loaded while offline
	// reflect the correct state immediately, not only after an online/offline
	// event fires. Falls back to false during SSR where navigator is undefined.
	const [ isNetworkOffline, setIsNetworkOffline ] = useState(
		typeof navigator !== 'undefined' && navigator.onLine === false
	);

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
