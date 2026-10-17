/**
 * External dependencies
 */
import { store as coreStore } from '@wordpress/core-data';
import { useSelect } from '@wordpress/data';
import { useViewConfig } from '@wordpress/views';
import { useEffect, useState } from 'react';

/**
 * Internal dependencies
 */
import { applyFieldModules, loadFields, toError } from './definition';
import type { ResolutionSelectors, SettingsScreen } from './definition';

/**
 * Load a screen's fields once, reporting a failed request as an error.
 */
export function useScreenFields( screen: SettingsScreen ) {
	const [ state, setState ] = useState< {
		fields?: ReturnType< typeof applyFieldModules >;
		error?: Error;
	} >( {} );

	useEffect( () => {
		let current = true;
		const { kind, name } = screen.entity;
		loadFields( kind, name ).then(
			( fields ) => {
				if ( current ) {
					setState( { fields } );
				}
			},
			( error: unknown ) => {
				if ( current ) {
					setState( { error: toError( error ) } );
				}
			}
		);
		return () => {
			current = false;
		};
	}, [ screen ] );

	return state;
}

/**
 * Load a screen's form from View Config through core-data.
 *
 * A screen without a configured layout gets an empty form once the request finishes.
 */
export function useScreenForm( screen: SettingsScreen ) {
	const { kind, name } = screen.entity;
	const { form } = useViewConfig( { kind, name } );
	const { hasFinished, error } = useSelect(
		( select ) => {
			// Every store has these metadata selectors, but core-data's types leave them out.
			const core = select( coreStore ) as unknown as ResolutionSelectors;
			return {
				hasFinished: core.hasFinishedResolution( 'getViewConfig', [
					kind,
					name,
				] ),
				error: core.getResolutionError( 'getViewConfig', [
					kind,
					name,
				] ),
			};
		},
		[ kind, name ]
	);

	return {
		form: hasFinished ? ( form ?? { fields: [] } ) : undefined,
		error: error ? toError( error ) : undefined,
	};
}
