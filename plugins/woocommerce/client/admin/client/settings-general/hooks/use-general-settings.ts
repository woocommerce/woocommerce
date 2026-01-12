/**
 * External dependencies
 */
import { useState, useEffect } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';

/**
 * Type definitions for General Settings API response.
 */
export interface SettingsField {
	id: string;
	label: string;
	type: string;
	options?: Record< string, string >;
	desc?: string;
}

export interface SettingsGroup {
	title: string;
	description: string;
	order: number;
	fields: SettingsField[];
}

export interface GeneralSettingsResponse {
	id: string;
	title: string;
	description: string;
	values: Record< string, unknown >;
	groups: Record< string, SettingsGroup >;
}

export interface UseGeneralSettingsReturn {
	data: GeneralSettingsResponse | null;
	isLoading: boolean;
	error: Error | null;
	refetch: () => Promise< void >;
	updateSettings: ( values: Record< string, unknown > ) => Promise< void >;
	isSaving: boolean;
	saveError: Error | null;
}

/**
 * Custom hook to fetch and manage General Settings from the WooCommerce V4 API.
 *
 * @return Object containing settings data, loading state, error state, and utility functions.
 */
export const useGeneralSettings = (): UseGeneralSettingsReturn => {
	const [ data, setData ] = useState< GeneralSettingsResponse | null >(
		null
	);
	const [ isLoading, setIsLoading ] = useState( true );
	const [ error, setError ] = useState< Error | null >( null );
	const [ isSaving, setIsSaving ] = useState( false );
	const [ saveError, setSaveError ] = useState< Error | null >( null );

	const fetchSettings = async () => {
		try {
			setIsLoading( true );
			setError( null );

			const response = await apiFetch< GeneralSettingsResponse >( {
				path: '/wc/v4/settings/general',
				method: 'GET',
			} );

			setData( response );
		} catch ( err ) {
			setError(
				err instanceof Error ? err : new Error( 'Unknown error' )
			);
		} finally {
			setIsLoading( false );
		}
	};

	const updateSettings = async ( values: Record< string, unknown > ) => {
		try {
			setIsSaving( true );
			setSaveError( null );

			const response = await apiFetch< GeneralSettingsResponse >( {
				path: '/wc/v4/settings/general',
				method: 'POST',
				data: { values },
			} );

			setData( response );
		} catch ( err ) {
			setSaveError(
				err instanceof Error ? err : new Error( 'Unknown error' )
			);
			throw err;
		} finally {
			setIsSaving( false );
		}
	};

	useEffect( () => {
		fetchSettings();
	}, [] );

	return {
		data,
		isLoading,
		error,
		refetch: fetchSettings,
		updateSettings,
		isSaving,
		saveError,
	};
};
