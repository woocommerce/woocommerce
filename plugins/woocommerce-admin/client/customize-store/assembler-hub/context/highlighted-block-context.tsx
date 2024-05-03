/**
 * External dependencies
 */
import React, { createContext, useState } from '@wordpress/element';
import type { ReactNode } from 'react';

export const HighlightedBlockContext = createContext( {
	highlightedBlockIndex: -1,
	// eslint-disable-next-line @typescript-eslint/no-unused-vars
	setHighlightedBlockIndex: ( index: number ) => {
		// No op by default.
	},
	resetHighlightedBlockIndex: () => {
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
	const [ highlightedBlockIndex, setHighlightedBlockIndex ] = useState( -1 );

	const resetHighlightedBlockIndex = () => {
		setHighlightedBlockIndex( -1 );
	};

	return (
		<HighlightedBlockContext.Provider
			value={ {
				highlightedBlockIndex,
				setHighlightedBlockIndex,
				resetHighlightedBlockIndex,
			} }
		>
			{ children }
		</HighlightedBlockContext.Provider>
	);
};
