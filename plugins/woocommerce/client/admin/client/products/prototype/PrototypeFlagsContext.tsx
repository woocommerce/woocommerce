/**
 * External dependencies
 */
import { createContext, useState, ReactNode } from 'react';

const STORAGE_KEY = 'wc_prototype_flags';

export type FlagDefinition = {
	key: string;
	label: string;
	defaultValue: boolean;
};

export const FLAG_DEFINITIONS: FlagDefinition[] = [
	{ key: 'exampleFeature', label: 'Example feature', defaultValue: false },
];

type PrototypeFlagsContextValue = {
	flags: Record< string, boolean >;
	flagDefinitions: FlagDefinition[];
	toggleFlag: ( key: string ) => void;
};

export const PrototypeFlagsContext =
	createContext< PrototypeFlagsContextValue >( {
		flags: {},
		flagDefinitions: FLAG_DEFINITIONS,
		toggleFlag: () => {},
	} );

function loadFromStorage(
	definitions: FlagDefinition[]
): Record< string, boolean > {
	try {
		const stored = JSON.parse(
			localStorage.getItem( STORAGE_KEY ) || '{}'
		) as Record< string, unknown >;
		return Object.fromEntries(
			definitions.map( ( { key, defaultValue } ) => [
				key,
				typeof stored[ key ] === 'boolean'
					? stored[ key ]
					: defaultValue,
			] )
		);
	} catch {
		return Object.fromEntries(
			definitions.map( ( { key, defaultValue } ) => [
				key,
				defaultValue,
			] )
		);
	}
}

export function PrototypeFlagsProvider( {
	children,
}: {
	children: ReactNode;
} ) {
	const [ flags, setFlags ] = useState< Record< string, boolean > >( () =>
		loadFromStorage( FLAG_DEFINITIONS )
	);

	const toggleFlag = ( key: string ) => {
		setFlags( ( prev ) => {
			const next = { ...prev, [ key ]: ! prev[ key ] };
			try {
				localStorage.setItem( STORAGE_KEY, JSON.stringify( next ) );
			} catch {}
			return next;
		} );
	};

	return (
		<PrototypeFlagsContext.Provider
			value={ { flags, flagDefinitions: FLAG_DEFINITIONS, toggleFlag } }
		>
			{ children }
		</PrototypeFlagsContext.Provider>
	);
}
