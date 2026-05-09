/**
 * External dependencies
 */
import apiFetch from '@wordpress/api-fetch';
import { useEffect, useRef, useState } from '@wordpress/element';
import type { Field } from '@wordpress/dataviews';

/**
 * Internal dependencies
 */
import type { ProductEntityRecord } from '../types';
import type {
	LegacyFieldsResponse,
	LegacyHookMapping,
} from './types';
import { createLegacyField } from './adapter';

type UseLegacyFieldsResult = {
	fieldsByHook: Record< string, Field< ProductEntityRecord >[] >;
	isLoading: boolean;
};

/**
 * Fetch legacy field definitions and convert them to DataForm fields.
 *
 * Results are cached for the lifetime of the component — field definitions
 * don't change within a session.
 */
export function useLegacyFields(
	hookMapping: LegacyHookMapping
): UseLegacyFieldsResult {
	const [ fieldsByHook, setFieldsByHook ] = useState<
		Record< string, Field< ProductEntityRecord >[] >
	>( {} );
	const [ isLoading, setIsLoading ] = useState( true );
	const cacheRef = useRef< Record<
		string,
		Field< ProductEntityRecord >[]
	> | null >( null );

	const hookNames = Object.keys( hookMapping );
	const hookNamesKey = hookNames.join( ',' );

	useEffect( () => {
		if ( cacheRef.current ) {
			setFieldsByHook( cacheRef.current );
			setIsLoading( false );
			return;
		}

		if ( hookNames.length === 0 ) {
			setFieldsByHook( {} );
			setIsLoading( false );
			return;
		}

		let cancelled = false;

		const hooksParam = hookNames
			.map( ( name ) => `hooks[]=${ encodeURIComponent( name ) }` )
			.join( '&' );

		apiFetch< LegacyFieldsResponse >( {
			path: `/wc/v4/products/legacy-fields?${ hooksParam }`,
		} )
			.then( ( response ) => {
				if ( cancelled ) {
					return;
				}

				const grouped: Record<
					string,
					Field< ProductEntityRecord >[]
				> = {};

				const fieldsData = response.fields ?? response;

				for ( const [ hookName, definitions ] of Object.entries(
					fieldsData as Record< string, unknown[] >
				) ) {
					grouped[ hookName ] = [];
					for ( const def of definitions ) {
						const field = createLegacyField(
							def as Parameters< typeof createLegacyField >[ 0 ]
						);
						if ( field ) {
							grouped[ hookName ].push( field );
						}
					}
				}

				cacheRef.current = grouped;
				setFieldsByHook( grouped );
				setIsLoading( false );
			} )
			.catch( ( error ) => {
				if ( ! cancelled ) {
					// eslint-disable-next-line no-console
					console.error( 'Failed to load legacy field definitions:', error );
					setFieldsByHook( {} );
					setIsLoading( false );
				}
			} );

		return () => {
			cancelled = true;
		};
		// eslint-disable-next-line react-hooks/exhaustive-deps
	}, [ hookNamesKey ] );

	return { fieldsByHook, isLoading };
}
