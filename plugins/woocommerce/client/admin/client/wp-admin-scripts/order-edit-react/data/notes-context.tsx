/**
 * React context for order notes + create/delete actions.
 */

import { createContext, useContext, useEffect, useState, useCallback } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import type { ReactNode } from 'react';
import {
	fetchOrderNotes,
	createOrderNote,
	deleteOrderNote,
	describeError,
} from './api';
import type { OrderNote } from './types';

interface NotesContextValue {
	notes: OrderNote[];
	loading: boolean;
	error: string | null;
	reload: () => Promise< void >;
	addNote: ( body: string, customerVisible: boolean ) => Promise< OrderNote >;
	removeNote: ( noteId: number ) => Promise< void >;
}

const NotesContext = createContext< NotesContextValue | null >( null );

interface NotesProviderProps {
	orderId: number;
	children: ReactNode;
}

export function NotesProvider( { orderId, children }: NotesProviderProps ) {
	const [ notes, setNotes ] = useState< OrderNote[] >( [] );
	const [ loading, setLoading ] = useState( true );
	const [ error, setError ] = useState< string | null >( null );

	const load = useCallback( async () => {
		setLoading( true );
		setError( null );
		try {
			setNotes( await fetchOrderNotes( orderId ) );
		} catch ( err ) {
			setError( describeError( err ) );
		} finally {
			setLoading( false );
		}
	}, [ orderId ] );

	useEffect( () => {
		load();
	}, [ load ] );

	const addNote = useCallback(
		async ( body: string, customerVisible: boolean ) => {
			const note = await createOrderNote( orderId, body, customerVisible );
			// When the note was sent to the customer, also record a
			// system-attributed tracking entry so the History timeline
			// shows that an email went out. customer_note=false +
			// added_by_user=false routes it to History via our filter.
			if ( customerVisible ) {
				try {
					await createOrderNote(
						orderId,
						__( 'Customer note email sent.', 'woocommerce' ),
						false,
						false
					);
				} catch ( e ) {
					// Tracking entry is best-effort — if it fails the user
					// note itself is still saved.
				}
			}
			await load();
			return note;
		},
		[ orderId, load ]
	);

	const removeNote = useCallback(
		async ( noteId: number ) => {
			await deleteOrderNote( orderId, noteId );
			await load();
		},
		[ orderId, load ]
	);

	return (
		<NotesContext.Provider
			value={ { notes, loading, error, reload: load, addNote, removeNote } }
		>
			{ children }
		</NotesContext.Provider>
	);
}

export function useNotes(): NotesContextValue {
	const ctx = useContext( NotesContext );
	if ( ! ctx ) {
		throw new Error( 'useNotes must be used inside a NotesProvider' );
	}
	return ctx;
}
