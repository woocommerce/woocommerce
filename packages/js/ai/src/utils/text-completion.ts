/**
 * Internal dependencies
 */
import { requestJetpackToken } from './requestJetpackToken';

/**
 * Leaving this here to make it easier to debug the streaming API calls for now
 *
 * @param {string} prompt         - The query to send to the API
 * @param {string} feature        - The feature to use for the completion
 * @param {string} responseFormat - The format of the response. Can be 'text' or 'json_object'.
 */
export async function getCompletion(
	prompt: string,
	feature: string,
	responseFormat?: string
) {
	const { token } = await requestJetpackToken();

	const url = new URL(
		'https://public-api.wordpress.com/wpcom/v2/text-completion/stream'
	);

	url.searchParams.append( 'prompt', prompt );
	url.searchParams.append( 'token', token );
	url.searchParams.append( 'feature', feature );

	if ( responseFormat ) {
		url.searchParams.append( 'response_format', responseFormat );
	}

	return new EventSource( url.toString() );
}
