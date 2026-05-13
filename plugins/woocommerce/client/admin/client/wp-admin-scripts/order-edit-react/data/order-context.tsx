/**
 * React context exposing the order entity + a refetch method.
 *
 * Kept intentionally light (no @wordpress/data store) for the v1 demo — single
 * `useEntity` hook surfaces the order and a `reload()` action.
 */

import { createContext, useContext, useEffect, useState, useCallback } from '@wordpress/element';
import type { ReactNode } from 'react';
import { fetchOrder, describeError } from './api';
import type { Order } from './types';

interface OrderContextValue {
	order: Order | null;
	loading: boolean;
	error: string | null;
	reload: () => Promise< void >;
	setOrder: ( order: Order ) => void;
}

const OrderContext = createContext< OrderContextValue | null >( null );

interface OrderProviderProps {
	orderId: number;
	children: ReactNode;
}

export function OrderProvider( { orderId, children }: OrderProviderProps ) {
	const [ order, setOrder ] = useState< Order | null >( null );
	const [ loading, setLoading ] = useState( true );
	const [ error, setError ] = useState< string | null >( null );

	const load = useCallback( async () => {
		setLoading( true );
		setError( null );
		try {
			setOrder( await fetchOrder( orderId ) );
		} catch ( err ) {
			setError( describeError( err ) );
		} finally {
			setLoading( false );
		}
	}, [ orderId ] );

	useEffect( () => {
		load();
	}, [ load ] );

	return (
		<OrderContext.Provider
			value={ {
				order,
				loading,
				error,
				reload: load,
				setOrder,
			} }
		>
			{ children }
		</OrderContext.Provider>
	);
}

export function useOrder(): OrderContextValue {
	const ctx = useContext( OrderContext );
	if ( ! ctx ) {
		throw new Error( 'useOrder must be used inside an OrderProvider' );
	}
	return ctx;
}
