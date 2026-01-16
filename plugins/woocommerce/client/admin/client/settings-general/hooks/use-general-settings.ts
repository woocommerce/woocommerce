/**
 * External dependencies
 */
/**
 * Internal dependencies
 */
import type { ReactSettingsResponse } from '../../settings/types';
import { useReactSettings } from '../../settings/hooks/use-react-settings';

export interface UseGeneralSettingsReturn {
	data: ReactSettingsResponse | null;
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
	return useReactSettings( {
		dataPath: [ 'settings', 'general' ],
		missingDataMessage: 'General settings data is missing.',
	} );
};
