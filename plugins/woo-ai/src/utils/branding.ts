// Import apiFetch module from WordPress
import apiFetch from '@wordpress/api-fetch';

// Define the expected shape of the API response
interface ApiResponse {
    value: string;
  }

  /**
 * Fetch the tone of voice setting.
 *
 * @returns {Promise<string>} A promise that resolves with the tone of voice or 'neutral' if the API call fails.
 */
export async function getToneOfVoice(): Promise<string> {
  try {
    const { value } = await apiFetch<ApiResponse>( { path: '/wc/v3/settings/woo-ai/tone-of-voice' } );
    return value;
  } catch (error) {
    // @todo: can we log this anywhere?
    // should we try to log these using WC logging?
    console.error("Failed to fetch tone of voice", error);
    // Return a default value
    return 'neutral';
  }
}

/**
 * Fetch the business description setting.
 *
 * @returns {Promise<string>} A promise that resolves with the business description.
 * @throws {Error} If the API call fails.
 */
export async function getBusinessDescription(): Promise<string> {
  try {
    const { value } = await apiFetch<ApiResponse>( { path: '/wc/v3/settings/woo-ai/store-description' } );
    return value;
  } catch (error) {
    console.error("Failed to fetch business description", error);
    // Throw the error for the calling function to handle
    throw error;
  }
}