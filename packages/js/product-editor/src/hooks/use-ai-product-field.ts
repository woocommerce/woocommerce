/**
 * External dependencies
 */
import { useState } from '@wordpress/element';
import { __experimentalUseCompletion as useCompletion } from '@woocommerce/ai';

/**
 * Internal dependencies
 */
import { WOO_AI_PLUGIN_FEATURE_NAME } from '../constants';

enum FetchingState {
	Fetching = 'fetching',
	Failed = 'failed',
	None = 'none',
}

export function useAIProductField() {
	const [ fetchingState, setFetchingState ] = useState< FetchingState >(
		FetchingState.None
	);

	const { requestCompletion } = useCompletion( {
		feature: WOO_AI_PLUGIN_FEATURE_NAME,
		onStreamError: ( error ) => {
			// eslint-disable-next-line no-console
			console.debug( 'Streaming error encountered', error );
			setFetchingState( FetchingState.Failed );
			return error;
		},
		onCompletionFinished: ( reason, content ) => {
			try {
				const parsed = JSON.parse( content );
				setFetchingState( FetchingState.None );
				return parsed.suggestions;
			} catch ( e ) {
				setFetchingState( FetchingState.Failed );
				throw new Error( 'Unable to parse suggestions' );
			}
		},
	} );

	const buildPrompt = (
		prompt: string,
		context: string,
		responseExample: string
	) => {
		const instructions = [
			'You are a WooCommerce SEO and marketing expert.',
			"Using the product's name, description, tags, categories, and other attributes, provide three optimized alternatives to the product's title to enhance the store's SEO performance and sales.",
			"Provide the best option for the product's title based on the product properties.",
			'Identify the language used in the given title and use the same language in your response.',
			'Return only the alternative value for product\'s title in the "content" part of your response.',
			'Product titles should contain at least 20 characters.',
			'Return a short and concise reason for each suggestion in seven words in the "reason" part of your response.',
			`${ JSON.stringify( context ) }`,
			"The product's properties are:",
			`${ JSON.stringify( prompt ) }`,
			'Here is an example of a valid response:',
			'{"suggestions": [{"content": "' +
				responseExample +
				'", "reason": "Reason for the suggestion"}]}',
		];

		return instructions.join( '\n' );
	};

	const fetchProductSuggestions = async (
		context: string,
		prompt: string,
		responseExample: string
	) => {
		setFetchingState( FetchingState.Fetching );
		try {
			return await requestCompletion(
				buildPrompt( prompt, context, responseExample )
			);
		} catch ( e ) {
			setFetchingState( FetchingState.Failed );
		}
	};

	return {
		fetchProductSuggestions,
		fetchingState,
	};
}
