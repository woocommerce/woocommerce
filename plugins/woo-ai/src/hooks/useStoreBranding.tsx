/**
 * External dependencies
 */
import { useQuery } from 'react-query';

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

// Async function to fetch branding data
async function fetchBrandingData(): Promise< BrandingData > {
	const toneOfVoice = await getToneOfVoice();
	const businessDescription = await getBusinessDescription();

	return { toneOfVoice, businessDescription };
}

export function useStoreBranding() {
	return useQuery< BrandingData, BrandingError >(
		'storeBranding',
		fetchBrandingData,
		{
			refetchOnWindowFocus: false, // Do not refetch when window gains focus
		}
	);
}
