/**
 * External dependencies
 */
import { useState, useEffect, useCallback, useRef } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import type { ImportStatus, UseImportStatusReturn } from './types';

/**
 * Polling interval in milliseconds (5 seconds)
 */
const POLL_INTERVAL = 5000;

/**
 * Custom hook to fetch and manage analytics import status
 *
 * Features:
 * - Fetches import status from /wc-analytics/imports/status
 * - Auto-polls every 5s when import_in_progress_or_due is true
 * - Stops polling when flag becomes false
 * - Provides triggerImport function to manually trigger imports
 * - Clean cleanup on unmount
 *
 * @return {UseImportStatusReturn} Import status state and actions
 */
export function useImportStatus(): UseImportStatusReturn {
	const [ status, setStatus ] = useState< ImportStatus | null >( null );
	const [ isLoading, setIsLoading ] = useState( true );
	const [ error, setError ] = useState< string | null >( null );
	const [ isTriggeringImport, setIsTriggeringImport ] = useState( false );
	const intervalRef = useRef< number | null >( null );

	/**
	 * Fetch import status from API
	 */
	const fetchStatus = useCallback( async () => {
		try {
			const data = await apiFetch< ImportStatus >( {
				path: '/wc-analytics/imports/status',
				method: 'GET',
			} );
			setStatus( data );
			setError( null );
		} catch ( err ) {
			setError(
				err instanceof Error
					? err.message
					: __( 'Failed to fetch status', 'woocommerce' )
			);
		} finally {
			setIsLoading( false );
		}
	}, [] );

	/**
	 * Trigger a manual import
	 */
	const triggerImport = useCallback( async () => {
		setIsTriggeringImport( true );
		try {
			await apiFetch( {
				path: '/wc-analytics/imports/trigger',
				method: 'POST',
			} );
			// Immediately refetch to get updated status
			await fetchStatus();
		} catch ( err ) {
			setError(
				err instanceof Error
					? err.message
					: __( 'Failed to trigger import', 'woocommerce' )
			);
			throw err; // Re-throw so component can handle it
		} finally {
			setIsTriggeringImport( false );
		}
	}, [ fetchStatus ] );

	/**
	 * Initial fetch on mount
	 */
	useEffect( () => {
		fetchStatus();
	}, [ fetchStatus ] );

	/**
	 * Polling lifecycle management
	 * Start polling when import_in_progress_or_due is true
	 * Stop polling when it becomes false
	 */
	useEffect( () => {
		if ( status?.import_in_progress_or_due ) {
			// Start polling
			intervalRef.current = window.setInterval(
				fetchStatus,
				POLL_INTERVAL
			);
		} else if ( intervalRef.current ) {
			clearInterval( intervalRef.current );
			intervalRef.current = null;
		}

		// Cleanup on unmount or when dependencies change
		return () => {
			if ( intervalRef.current ) {
				clearInterval( intervalRef.current );
				intervalRef.current = null;
			}
		};
	}, [ status?.import_in_progress_or_due, fetchStatus ] );

	return {
		status,
		isLoading,
		error,
		triggerImport,
		isTriggeringImport,
	};
}
