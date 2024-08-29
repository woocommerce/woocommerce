/**
 * External dependencies
 */
import {
	createElement,
	createContext,
	useContext,
	useMemo,
} from '@wordpress/element';

export type LayoutContextType = {
	layoutString: string;
	extendLayout: ( item: string ) => LayoutContextType;
	layoutParts: string[];
	isDescendantOf: ( item: string ) => boolean;
};

type LayoutContextProviderProps = {
	children: React.ReactNode;
	value: LayoutContextType;
};

export const LayoutContext = createContext< LayoutContextType | undefined >(
	undefined
);

export const getLayoutContextValue = (
	layoutParts: LayoutContextType[ 'layoutParts' ] = []
): LayoutContextType => ( {
	layoutParts: [ ...layoutParts ],
	extendLayout: ( item ) => {
		const newLayoutPath = [ ...layoutParts, item ];

		return {
			...getLayoutContextValue( newLayoutPath ),
			layoutParts: newLayoutPath,
		};
	},
	layoutString: layoutParts.join( '/' ),
	isDescendantOf: ( item ) => layoutParts.includes( item ),
} );

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

export const useExtendLayout = ( item: string ) => {
	const { extendLayout } = useLayoutContext();

	return useMemo( () => extendLayout( item ), [ extendLayout, item ] );
};
