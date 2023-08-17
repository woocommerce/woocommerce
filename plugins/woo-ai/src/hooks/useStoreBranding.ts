/**
 * External dependencies
 */
import { useEffect } from 'react';
import { useQuery, UseQueryResult } from 'react-query';

/**
 * Internal dependencies
 */
import { getToneOfVoice, getBusinessDescription } from '../utils/branding';

// Define your data type
interface BrandingData {
	toneOfVoice: string;
	businessDescription: string;
}

// Define your error type
interface BrandingError {
	message: string;
}

type UseStoreBrandingOptions = {
	onError?: ( error: BrandingError ) => void;
};

// Async function to fetch branding data
async function fetchBrandingData(): Promise< BrandingData > {
	const [ toneOfVoice, businessDescription ] = await Promise.all( [
		getToneOfVoice(),
		getBusinessDescription(),
	] );

	return { toneOfVoice, businessDescription };
}

export function useStoreBranding( {
	onError,
}: UseStoreBrandingOptions = {} ): UseQueryResult<
	BrandingData,
	BrandingError
> {
	const result = useQuery< BrandingData, BrandingError >(
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
