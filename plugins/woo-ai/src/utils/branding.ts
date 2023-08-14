/**
 * External dependencies
 */
import apiFetch from '@wordpress/api-fetch';

// Define the expected shape of the API response
type ApiResponse = {
	value: string;
};

/**
 * Fetch the tone of voice setting.
 *
 * @return {Promise<string>} A promise that resolves with the tone of voice or 'neutral' if the API call fails.
 */
export async function getToneOfVoice(): Promise< string > {
	try {
		const { value } = await apiFetch< ApiResponse >( {
			path: '/wc/v3/settings/woo-ai/tone-of-voice',
		} );
		return value;
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
		const { value } = await apiFetch< ApiResponse >( {
			path: '/wc/v3/settings/woo-ai/store-description',
		} );
		return value;
	} catch ( error ) {
		throw error;
	}
}
