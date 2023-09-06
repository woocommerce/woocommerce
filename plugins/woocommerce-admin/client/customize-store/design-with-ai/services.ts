/**
 * External dependencies
 */
import { __experimentalRequestJetpackToken as requestJetpackToken } from '@woocommerce/ai';
import apiFetch from '@wordpress/api-fetch';

/**
 * Internal dependencies
 */
import { designWithAiStateMachineContext } from './types';

export const getLookAndTone = async (
	context: designWithAiStateMachineContext
) => {
	const prompt = [
		'You are a WordPress theme expert.',
		'Analyze the following store description and determine the look and tone of the theme.',
		'For look, you can choose between Contemporary, Classic, and Bold.',
		'For tone of the description, you can choose between Informal, Neutral, and Formal.',
		'Your response should be in json with look and tone values.',
		'\n',
		context.businessInfoDescription.descriptionText,
	];

	const { token } = await requestJetpackToken();

	const url = new URL(
		'https://public-api.wordpress.com/wpcom/v2/text-completion'
	);

	url.searchParams.append( 'prompt', prompt.join( '\n' ) );
	url.searchParams.append( 'token', token );
	url.searchParams.append( 'feature', 'woo_cys' );
	url.searchParams.append( '_fields', 'completion' );

	const data: {
		completion: string;
	} = await apiFetch( {
		url: url.toString(),
		method: 'POST',
	} );

	return JSON.parse( data.completion );
};

export const services = {
	getLookAndTone,
};
