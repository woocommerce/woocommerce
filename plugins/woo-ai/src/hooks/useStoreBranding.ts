/**
 * External dependencies
 */
import { useEffect } from 'react';
import { useQuery, UseQueryResult } from 'react-query';

/**
 * Internal dependencies
 */
import { getAllBrandingSettings } from '../utils/branding';
import type { BrandingSettings } from '../utils/branding';

// Define your error type
interface BrandingError {
	message: string;
}

type UseStoreBrandingOptions = {
	onError?: ( error: BrandingError ) => void;
};

// Async function to fetch branding data
async function fetchBrandingData(): Promise< BrandingSettings > {
	return await getAllBrandingSettings();
}

export function useStoreBranding( {
	onError,
}: UseStoreBrandingOptions = {} ): UseQueryResult<
	BrandingSettings,
	BrandingError
> {
	const result = useQuery< BrandingSettings, BrandingError >(
		'storeBranding',
		fetchBrandingData,
		{
			refetchOnWindowFocus: false, // Do not refetch when window gains focus
		}
	);

	useEffect( () => {
		if ( result.isError && onError ) {
			onError( result.error as BrandingError );
		}
	}, [ result.isError, result.error, onError ] );

	return result;
}
