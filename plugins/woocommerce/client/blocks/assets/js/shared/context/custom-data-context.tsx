/**
 * External dependencies
 */
import type { Context, PropsWithChildren } from 'react';
import { createContext, useContext } from '@wordpress/element';

export interface CustomData< T > {
	isLoading?: boolean;
	data?: T;
}

const contexts: Map< string, Context< CustomData< unknown > > > = new Map();

function get< T >( id: string ) {
	return contexts.get( id ) as Context< CustomData< T > >;
}

function create< T >( id: string, defaultValue: CustomData< T > ) {
	let DataContext = get< T >( id );

	if ( ! DataContext ) {
		DataContext = createContext< CustomData< T > >( defaultValue );
		contexts.set( id, DataContext as Context< CustomData< unknown > > );
	}

	return DataContext;
}

export function CustomDataProvider< T >( {
	children,
	id,
	...props
}: PropsWithChildren< { id: string } & CustomData< T > > ) {
	const DataContext = create< T >( id, { isLoading: false } );

	return (
		<DataContext.Provider value={ props }>
			{ props.isLoading ? (
				<div className="is-loading">{ children }</div>
			) : (
				children
			) }
		</DataContext.Provider>
	);
}

export function useCustomDataContext< T >( id: string ) {
	const DataContext = get< T >( id );
	return useContext( DataContext );
}
