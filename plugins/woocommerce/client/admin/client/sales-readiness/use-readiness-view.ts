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
import { trackReadiness, viewedProps, type Surface } from './tracks';

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
 * Records one viewed event per mount, the Run check click, and failures.
 */
export function useReadinessView( surface: Surface ) {
	const [ view, setView ] = useState< ReadinessView | null >( null );
	const [ error, setError ] = useState< string | null >( null );
	const [ running, setRunning ] = useState( false );
	const viewed = useRef( false );
	const pendingSince = useRef< number | null >( null );

	const load = useCallback( async () => {
		try {
			const loaded = await apiFetch< ReadinessView >( {
				path: VIEW_PATH,
			} );
			setView( loaded );
			setError( null );
			if ( ! viewed.current ) {
				viewed.current = true;
				trackReadiness( 'viewed', viewedProps( loaded, surface ) );
			}
		} catch {
			trackReadiness( 'error', { surface, action: 'load' } );
			setError(
				__(
					'The check results could not be loaded. Reload the page and try again.',
					'woocommerce'
				)
			);
		}
	}, [ surface ] );

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
		trackReadiness( 'run_check_click', {
			surface,
			first_check: !! view?.intro,
		} );
		setRunning( true );
		try {
			await apiFetch( { path: SCAN_PATH, method: 'POST' } );
			await load();
		} catch {
			trackReadiness( 'error', { surface, action: 'run' } );
			setError(
				__(
					'The check could not be started. Reload the page and try again.',
					'woocommerce'
				)
			);
		} finally {
			setRunning( false );
		}
	}, [ load, surface, view?.intro ] );

	return { view, error, running, runCheck };
}
