/**
 * External dependencies
 */
import { useState, useEffect } from '@wordpress/element';

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
	refetch: () => void;
}

/**
 * Custom hook to read General Settings from preloaded data.
 *
 * @return Object containing settings data, loading state, error state, and utility functions.
 */
export const useGeneralSettings = (): UseGeneralSettingsReturn => {
	const getPreloadedSettings = (): GeneralSettingsResponse | null => {
		const windowWithSettings = window as Window & {
			wcSettings?: {
				admin?: {
					settings?: {
						general?: GeneralSettingsResponse;
					};
				};
			};
		};
		return windowWithSettings.wcSettings?.admin?.settings?.general || null;
	};

	const [ data, setData ] = useState< GeneralSettingsResponse | null >(
		getPreloadedSettings()
	);
	const [ isLoading, setIsLoading ] = useState( ! data );
	const [ error, setError ] = useState< Error | null >( null );

	const refetch = () => {
		const preloaded = getPreloadedSettings();
		if ( preloaded ) {
			setData( preloaded );
			setError( null );
			setIsLoading( false );
		} else {
			setError( new Error( 'General settings data is missing.' ) );
			setIsLoading( false );
		}
	};

	useEffect( () => {
		if ( ! data ) {
			refetch();
		}
	}, [] );

	return {
		data,
		isLoading,
		error,
		refetch,
	};
};
