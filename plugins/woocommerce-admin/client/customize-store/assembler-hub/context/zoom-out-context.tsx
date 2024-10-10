/**
 * External dependencies
 */
import React, { createContext, useState } from '@wordpress/element';
import type { ReactNode } from 'react';

export const ZoomOutContext = createContext< {
	isZoomedOut: boolean;
	toggleZoomOut: () => void;
} >( {
	isZoomedOut: false,
	// eslint-disable-next-line @typescript-eslint/no-unused-vars
	toggleZoomOut: () => {
		// No op by default.
	},
} );

export const ZoomOutContextProvider = ( {
	children,
}: {
	children: ReactNode;
} ) => {
	const [ isZoomedOut, setIsZoomedOut ] = useState< boolean >( false );

	const toggleZoomOut = () => {
		setIsZoomedOut( ! isZoomedOut );
	};

	return (
		<ZoomOutContext.Provider
			value={ {
				isZoomedOut,
				toggleZoomOut,
			} }
		>
			{ children }
		</ZoomOutContext.Provider>
	);
};
