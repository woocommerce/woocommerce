/**
 * External dependencies
 */
import { createElement, createContext, useContext } from '@wordpress/element';

export type LayoutContextType = {
	layoutString: string;
	updateLayoutPath: ( item: string ) => LayoutContextType;
	layoutPath: string[];
};

type LayoutContextProviderProps = {
	children: React.ReactNode;
	value: LayoutContextType;
};

export const LayoutContext = createContext< LayoutContextType | undefined >(
	undefined
);

export const getLayoutContextValue = (
	layoutPath: LayoutContextType[ 'layoutPath' ] = []
) => {
	return {
		layoutPath: [ ...layoutPath ],
		updateLayoutPath: ( item: string ) => {
			const newLayoutPath = [ ...layoutPath, item ];

			return {
				...getLayoutContextValue( newLayoutPath ),
				layoutPath: newLayoutPath,
			};
		},
		layoutString: layoutPath.join( '/' ),
	};
};

export const LayoutContextProvider: React.FC< LayoutContextProviderProps > = ( {
	children,
	value,
} ) => (
	<LayoutContext.Provider value={ value }>
		{ children }
	</LayoutContext.Provider>
);

export const useLayoutContext = () => {
	const layoutContext = useContext( LayoutContext );

	if ( layoutContext === undefined ) {
		throw new Error(
			'useLayoutContext must be used within a LayoutContextProvider'
		);
	}

	return layoutContext;
};
