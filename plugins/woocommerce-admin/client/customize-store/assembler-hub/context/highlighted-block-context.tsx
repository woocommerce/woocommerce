/**
 * External dependencies
 */
import React, { createContext, useState } from '@wordpress/element';
import type { ReactNode } from 'react';

export const HighlightedBlockContext = createContext< {
	highlightedBlockClientId: string | null;
	setHighlightedBlockClientId: ( clientId: string | null ) => void;
	resetHighlightedBlockClientId: () => void;
} >( {
	highlightedBlockClientId: null,
	// eslint-disable-next-line @typescript-eslint/no-unused-vars
	setHighlightedBlockClientId: ( clientId: string | null ) => {
		// No op by default.
	},
	resetHighlightedBlockClientId: () => {
		// No op by default.
	},
} );

// A Provider that keeps track of which block is "focussed" in the Assembler Hub.
// This is used to highlight the block in the BlockEditor currently.
export const HighlightedBlockContextProvider = ( {
	children,
}: {
	children: ReactNode;
} ) => {
	// Create some state
	const [ highlightedBlockClientId, setHighlightedBlockClientId ] = useState<
		string | null
	>( null );

	const resetHighlightedBlockClientId = () => {
		setHighlightedBlockClientId( null );
	};

	return (
		<HighlightedBlockContext.Provider
			value={ {
				highlightedBlockClientId,
				setHighlightedBlockClientId,
				resetHighlightedBlockClientId,
			} }
		>
			{ children }
		</HighlightedBlockContext.Provider>
	);
};
