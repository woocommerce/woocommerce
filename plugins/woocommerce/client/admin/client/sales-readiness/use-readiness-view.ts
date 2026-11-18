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
 * A view that came with the page is used as is, without loading it again.
 */
export function useReadinessView( initialView: ReadinessView | null = null ) {
	const [ view, setView ] = useState< ReadinessView | null >( initialView );
	const [ error, setError ] = useState< string | null >( null );
	const [ running, setRunning ] = useState( false );
	const pendingSince = useRef< number | null >( null );
	// True after a view has been shown: poll failures must not replace it or stop polling.
	const hasView = useRef( initialView !== null );

	const load = useCallback( async () => {
		try {
			const loaded = await apiFetch< ReadinessView >( {
				path: VIEW_PATH,
			} );
			setView( loaded );
			setError( null );
			hasView.current = true;
		} catch {
			if ( hasView.current ) {
				return;
			}
			setError(
				__(
					'Your check results didn’t load. Reload the page to try again.',
					'woocommerce'
				)
			);
		}
	}, [] );

	const preloaded = useRef( initialView );
	useEffect( () => {
		if ( ! preloaded.current ) {
			void load();
		}
	}, [ load ] );

	useEffect( () => {
		if ( ! view?.run.pending ) {
			pendingSince.current = null;
			return;
		}
		pendingSince.current ??= Date.now();
		const timer = setInterval( () => {
			if ( Date.now() - ( pendingSince.current ?? 0 ) >= POLL_FOR_MS ) {
				clearInterval( timer );
				return;
			}
			void load();
		}, POLL_MS );
		return () => clearInterval( timer );
	}, [ view?.run.pending, load ] );

	const runCheck = useCallback( async () => {
		setRunning( true );
		try {
			await apiFetch( { path: SCAN_PATH, method: 'POST' } );
			await load();
		} catch {
			setError(
				__(
					'The check didn’t start. Reload the page to try again.',
					'woocommerce'
				)
			);
		} finally {
			setRunning( false );
		}
	}, [ load ] );

	return { view, error, running, runCheck };
}
