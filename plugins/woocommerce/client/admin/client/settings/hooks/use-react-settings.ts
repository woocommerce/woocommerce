/**
 * External dependencies
 */
import { useCallback, useEffect, useState } from '@wordpress/element';

/**
 * Internal dependencies
 */
import type { ReactSettingsResponse } from '../types';

export interface UseReactSettingsReturn {
	data: ReactSettingsResponse | null;
	isLoading: boolean;
	error: Error | null;
	refetch: () => void;
}

type UseReactSettingsOptions = {
	dataPath: string[];
	missingDataMessage?: string;
};

const getNestedValue = (
	root: unknown,
	path: string[]
): ReactSettingsResponse | null => {
	if ( ! root || typeof root !== 'object' ) {
		return null;
	}

	let current: unknown = root;
	for ( const key of path ) {
		if ( ! current || typeof current !== 'object' ) {
			return null;
		}

		const record = current as Record< string, unknown >;
		if ( ! Object.prototype.hasOwnProperty.call( record, key ) ) {
			return null;
		}

		current = record[ key ];
	}

	return current as ReactSettingsResponse;
};

const getPreloadedSettings = (
	dataPath: string[]
): ReactSettingsResponse | null => {
	const windowWithSettings =
		typeof window === 'undefined'
			? ( {} as Window )
			: ( window as Window & {
					wcSettings?: {
						admin?: Record< string, unknown >;
					};
			  } );

	return getNestedValue( windowWithSettings.wcSettings?.admin, dataPath );
};

/**
 * Custom hook to read React settings from preloaded data.
 *
 * @param options Hook options.
 * @return Object containing settings data, loading state, and error state.
 */
export const useReactSettings = (
	options: UseReactSettingsOptions
): UseReactSettingsReturn => {
	const { dataPath, missingDataMessage } = options;
	const [ data, setData ] = useState< ReactSettingsResponse | null >(
		getPreloadedSettings( dataPath )
	);
	const [ isLoading, setIsLoading ] = useState( ! data );
	const [ error, setError ] = useState< Error | null >( null );

	const refetch = useCallback( () => {
		const preloaded = getPreloadedSettings( dataPath );
		if ( preloaded ) {
			setData( preloaded );
			setError( null );
			setIsLoading( false );
			return;
		}

		setError(
			new Error( missingDataMessage || 'Settings data is missing.' )
		);
		setIsLoading( false );
	}, [ dataPath, missingDataMessage ] );

	useEffect( () => {
		if ( ! data ) {
			refetch();
		}
	}, [ data, refetch ] );

	return {
		data,
		isLoading,
		error,
		refetch,
	};
};
