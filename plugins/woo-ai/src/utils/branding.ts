/**
 * External dependencies
 */
import apiFetch from '@wordpress/api-fetch';

// Define the expected shape of the API response
type ApiResponseSingleValue = {
	value: string;
};

type ApiResponseItem = {
	id: string;
	value: string;
};

export type BrandingSettings = {
	toneOfVoice: string;
	businessDescription: string;
};

/**
 * Fetch the tone of voice setting.
 *
 * @return {Promise<string>} A promise that resolves with the tone of voice or 'neutral' if the API call fails.
 */
export async function getToneOfVoice(): Promise< string > {
	try {
		const { value } = await apiFetch< ApiResponseSingleValue >( {
			path: '/wc/v3/settings/woo-ai/tone-of-voice',
		} );
		return value;
	} catch ( error ) {
		throw error;
	}
}

/**
 * Fetch all Woo AI branding settings.
 *
 * @return {Promise<BrandingSettings>} A promise that resolves with all branding settings.
 */
export async function getAllBrandingSettings(): Promise< BrandingSettings > {
	try {
		const response = await apiFetch< ApiResponseItem[] >( {
			path: '/wc/v3/settings/woo-ai',
		} );

		const toneOfVoice = response.find(
			( setting ) => setting.id === 'tone-of-voice'
		)?.value;
		const businessDescription = response.find(
			( setting ) => setting.id === 'store-description'
		)?.value;
		return {
			toneOfVoice: toneOfVoice || '',
			businessDescription: businessDescription || '',
		};
	} catch ( error ) {
		throw error;
	}
}

/**
 * Fetch the business description setting.
 *
 * @return {Promise<string>} A promise that resolves with the business description.
 */
export async function getBusinessDescription(): Promise< string > {
	try {
		const { value } = await apiFetch< ApiResponseSingleValue >( {
			path: '/wc/v3/settings/woo-ai/store-description',
		} );
		return value;
	} catch ( error ) {
		throw error;
	}
}
