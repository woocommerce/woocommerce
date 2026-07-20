/**
 * External dependencies
 */
import { useCallback, useEffect, useState } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';

export interface ImportStatus {
	failed_count: number;
	failed_overflow_count: number;
	refund_double_count: number;
	refund_double_count_scan_complete: boolean;
	refund_double_count_fix_in_progress: boolean;
}

let inFlight: Promise< ImportStatus > | null = null;

/**
 * Fetch the analytics import status, sharing a single in-flight request so
 * several notices mounting together issue one GET instead of one each.
 */
function fetchImportStatus(): Promise< ImportStatus > {
	if ( ! inFlight ) {
		inFlight = apiFetch< ImportStatus >( {
			path: '/wc-analytics/imports/status',
		} ).finally( () => {
			inFlight = null;
		} );
	}
	return inFlight;
}

/**
 * Provide the analytics import status to a notice component.
 *
 * Fetches on mount (deduplicated across components mounting together) and
 * exposes a refetch for after an action completes. Fails silently — the
 * consuming notices are auxiliary affordances that render nothing without a
 * status.
 */
export function useImportStatus() {
	const [ status, setStatus ] = useState< ImportStatus | null >( null );

	const refetch = useCallback( async () => {
		try {
			setStatus( await fetchImportStatus() );
		} catch ( err ) {
			// Fail silently — the notices are auxiliary affordances.
		}
	}, [] );

	useEffect( () => {
		refetch();
	}, [ refetch ] );

	return { status, refetch };
}
