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
 * A view that came with the page is used as is, without loading it again.
 * Records one viewed event per mount, the Run check click, and failures.
 */
export function useReadinessView(
	surface: Surface,
	initialView: ReadinessView | null = null
) {
	const [ view, setView ] = useState< ReadinessView | null >( initialView );
	const [ error, setError ] = useState< string | null >( null );
	// True when the results didn't load, so the page shows no Check again and offers Reload page.
	const [ reload, setReload ] = useState( false );
	const [ running, setRunning ] = useState( false );
	const viewed = useRef( false );
	const pendingSince = useRef< number | null >( null );
	// True after a view has been shown: poll failures must not spam Tracks, replace the view or stop polling.
	const hasView = useRef( initialView !== null );
	const mounted = useRef( true );

	const load = useCallback( async () => {
		try {
			const loaded = await apiFetch< ReadinessView >( {
				path: VIEW_PATH,
			} );
			if ( ! mounted.current ) {
				return;
			}
			setView( loaded );
			setError( null );
			setReload( false );
			hasView.current = true;
			if ( ! viewed.current ) {
				viewed.current = true;
				trackReadiness( 'viewed', viewedProps( loaded, surface ) );
			}
		} catch {
			if ( ! mounted.current || hasView.current ) {
				return;
			}
			trackReadiness( 'error', { surface, action: 'load' } );
			setReload( true );
			setError(
				__(
					'Your check results didn’t load. Reload the page to try again.',
					'woocommerce'
				)
			);
		}
	}, [ surface ] );

	const preloaded = useRef( initialView );
	useEffect( () => {
		mounted.current = true;
		if ( ! preloaded.current ) {
			void load();
		} else if ( ! viewed.current ) {
			viewed.current = true;
			trackReadiness(
				'viewed',
				viewedProps( preloaded.current, surface )
			);
		}
		return () => {
			mounted.current = false;
		};
	}, [ load, surface ] );

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
		trackReadiness( 'run_check_clicked', {
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
					'The check didn’t start. Try again in a few minutes.',
					'woocommerce'
				)
			);
		} finally {
			setRunning( false );
		}
	}, [ load, surface, view?.intro ] );

	return { view, error, reload, running, runCheck };
}
