/**
 * Internal dependencies
 */
import { requestJetpackToken } from './requestJetpackToken';

/**
 * Leaving this here to make it easier to debug the streaming API calls for now
 *
 * @param {string} prompt - The query to send to the API
 */
export async function getCompletion( prompt: string, feature: string ) {
	const { token } = await requestJetpackToken();

	const url = new URL(
		'https://public-api.wordpress.com/wpcom/v2/text-completion/stream'
	);

	url.searchParams.append( 'prompt', prompt );
	url.searchParams.append( 'token', token );
	url.searchParams.append( 'feature', feature );

	return new EventSource( url.toString() );
}
