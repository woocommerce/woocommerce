/**
 * External dependencies
 */
import { useCallback, useEffect, useState } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import type { ReadinessView } from './types';

export const VIEW_PATH = '/wc/v3/agent-readiness/view';
export const SCAN_PATH = '/wc/v3/agent-readiness/scan';

/**
 * Loads the view, and queues a check: the scan runs in the background, so after
 * queuing it the view is read again for the pending message.
 */
export function useReadinessView() {
	const [ view, setView ] = useState< ReadinessView | null >( null );
	const [ error, setError ] = useState< string | null >( null );
	const [ running, setRunning ] = useState( false );

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
