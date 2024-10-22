/**
 * External dependencies
 */
import { createContext, useState } from '@wordpress/element';
import { ReactNode } from 'react';

type SelectedBlockContextType = {
	selectedBlockRef: HTMLElement | null;
	setSelectedBlockRef: ( ref: HTMLElement | null ) => void;
};

export const SelectedBlockContext = createContext< SelectedBlockContextType >( {
	selectedBlockRef: null,
	setSelectedBlockRef: () => {},
} );

export const SelectedBlockContextProvider = ( {
	children,
}: {
	children: ReactNode;
} ) => {
	const [ selectedBlockRef, setSelectedBlock ] =
		useState< HTMLElement | null >( null );

	const setSelectedBlockRef = ( ref: HTMLElement | null ) => {
		setSelectedBlock( ref );
	};

	return (
		<SelectedBlockContext.Provider
			value={ {
				selectedBlockRef,
				setSelectedBlockRef,
			} }
		>
			{ children }
		</SelectedBlockContext.Provider>
	);
};
