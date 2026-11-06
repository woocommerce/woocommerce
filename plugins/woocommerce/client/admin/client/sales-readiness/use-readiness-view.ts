/**
 * External dependencies
 */
import { useCallback, useEffect, useRef, useState } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import type { ReadinessView } from './types';

export const VIEW_PATH = '/wc/v3/agent-readiness/view';
export const SCAN_PATH = '/wc/v3/agent-readiness/scan';

/**
 * How often the view is read again while a check is pending, so its results show once it finishes.
 */
export const POLL_MS = 5000;

/**
 * How long a pending check is polled for. The pending message already says when a check is stuck.
 */
export const POLL_FOR_MS = 10 * 60 * 1000;

/**
 * Loads the view, and queues a check: the scan runs in the background, so after
 * queuing it the view is read again for the pending message, and then every
 * POLL_MS while the check is pending, so the results replace it when it finishes.
 */
export function useReadinessView() {
	const [ view, setView ] = useState< ReadinessView | null >( null );
	const [ error, setError ] = useState< string | null >( null );
	const [ running, setRunning ] = useState( false );
	const pendingSince = useRef< number | null >( null );

	const load = useCallback( async () => {
		try {
			setView( await apiFetch< ReadinessView >( { path: VIEW_PATH } ) );
			setError( null );
		} catch {
			setError(
				__(
					'The check results could not be loaded. Reload the page and try again.',
					'woocommerce'
				)
			);
		}
	}, [] );

	useEffect( () => {
		void load();
	}, [ load ] );

	useEffect( () => {
		if ( ! view?.run.pending ) {
			pendingSince.current = null;
			return;
		}
		pendingSince.current ??= Date.now();
		if ( Date.now() - pendingSince.current >= POLL_FOR_MS ) {
			return;
		}
		const timer = setTimeout( () => void load(), POLL_MS );
		return () => clearTimeout( timer );
	}, [ view, load ] );

	const runCheck = useCallback( async () => {
		setRunning( true );
		try {
			await apiFetch( { path: SCAN_PATH, method: 'POST' } );
			await load();
		} catch {
			setError(
				__(
					'The check could not be started. Reload the page and try again.',
					'woocommerce'
				)
			);
		} finally {
			setRunning( false );
		}
	}, [ load ] );

	return { view, error, running, runCheck };
}
