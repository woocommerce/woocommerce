/**
 * Snackbar host — listens for `wc-react-order-edit:snackbar` events fired by
 * other components (modal saves, note actions, etc.) and renders a `<Snackbar>`
 * floating at the bottom of the viewport.
 *
 * Decoupled from the components that fire snackbars so we don't have to thread
 * a "show snackbar" callback through every modal.
 */

import { useEffect, useState, useCallback } from '@wordpress/element';
import { Snackbar } from '@wordpress/components';

interface SnackMessage {
	id: number;
	message: string;
}

export function SnackbarHost() {
	const [ stack, setStack ] = useState< SnackMessage[] >( [] );

	const dismiss = useCallback( ( id: number ) => {
		setStack( ( prev ) => prev.filter( ( m ) => m.id !== id ) );
	}, [] );

	useEffect( () => {
		const handler = ( ev: Event ) => {
			const detail = ( ev as CustomEvent< { message: string } > ).detail;
			if ( ! detail?.message ) {
				return;
			}
			const id = Date.now() + Math.random();
			setStack( ( prev ) => [ ...prev, { id, message: detail.message } ] );
			// Auto-dismiss after 4s.
			setTimeout( () => dismiss( id ), 4000 );
		};
		window.addEventListener( 'wc-react-order-edit:snackbar', handler );
		return () => window.removeEventListener( 'wc-react-order-edit:snackbar', handler );
	}, [ dismiss ] );

	if ( stack.length === 0 ) {
		return null;
	}

	return (
		<div className="wc-react-order-edit__snackbars" aria-live="polite">
			{ stack.map( ( m ) => (
				<Snackbar key={ m.id } onDismiss={ () => dismiss( m.id ) }>
					{ m.message }
				</Snackbar>
			) ) }
		</div>
	);
}
