/**
 * External dependencies
 */
import { useCallback, useEffect, useRef, useState } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import type { FinanceError } from '../types';

export type ApiRequestState< T > = {
	data: T | null;
	isLoading: boolean;
	error: FinanceError | null;
};

export type ApiRequestResult< T > = ApiRequestState< T > & {
	refetch: () => void;
};

const toFinanceError = ( error: unknown ): FinanceError => {
	const { code, message } = ( error ?? {} ) as Partial< FinanceError >;

	return {
		code: typeof code === 'string' ? code : 'unknown_error',
		message:
			typeof message === 'string' && message
				? message
				: __(
						'Something went wrong. Please try again.',
						'woocommerce'
				  ),
	};
};

/**
 * Fetch a GET endpoint and expose its state. A new path supersedes the request in flight,
 * so a late response for an old path is ignored. The previous data is kept while the next
 * request loads. Pass null to skip fetching.
 *
 * @param path REST path, or null to skip.
 */
export function useApiRequest< T >(
	path: string | null
): ApiRequestResult< T > {
	const [ state, setState ] = useState< ApiRequestState< T > >( {
		data: null,
		isLoading: path !== null,
		error: null,
	} );
	const [ reloadToken, setReloadToken ] = useState( 0 );
	const requestId = useRef( 0 );

	useEffect( () => {
		requestId.current += 1;
		const currentRequestId = requestId.current;

		if ( path === null ) {
			setState( { data: null, isLoading: false, error: null } );
			return;
		}

		setState( ( prev ) => ( { ...prev, isLoading: true, error: null } ) );

		apiFetch< T >( { path } )
			.then( ( data ) => {
				if ( currentRequestId === requestId.current ) {
					setState( { data, isLoading: false, error: null } );
				}
			} )
			.catch( ( error: unknown ) => {
				if ( currentRequestId === requestId.current ) {
					setState( {
						data: null,
						isLoading: false,
						error: toFinanceError( error ),
					} );
				}
			} );
	}, [ path, reloadToken ] );

	const refetch = useCallback( () => setReloadToken( ( t ) => t + 1 ), [] );

	return { ...state, refetch };
}
