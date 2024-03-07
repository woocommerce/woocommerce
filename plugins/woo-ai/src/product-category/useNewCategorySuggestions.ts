/**
 * External dependencies
 */
import {
	__experimentalUseCompletion as useCompletion,
	UseCompletionError,
} from '@woocommerce/ai';

/**
 * Internal dependencies
 */
import { WOO_AI_PLUGIN_FEATURE_NAME } from '../constants';
import { generateProductDataInstructions, ProductProps } from '../utils';

type UseNewCategorySuggestionsHook = {
	fetchSuggestions: () => Promise< void >;
};

export const useNewCategorySuggestions = (
	onSuggestionsGenerated: ( suggestions: string[] ) => void,
	onError: ( error: UseCompletionError ) => void
): UseNewCategorySuggestionsHook => {
	const { requestCompletion } = useCompletion( {
		feature: WOO_AI_PLUGIN_FEATURE_NAME,
		onStreamError: ( error: UseCompletionError ) => {
			// eslint-disable-next-line no-console
			console.debug( 'Streaming error encountered', error );

			onError( error );
		},
		onCompletionFinished: async ( reason, content ) => {
			if ( reason === 'error' ) {
				throw Error( 'Unable to parse suggestions' );
			}
			if ( ! content ) {
				throw Error( 'No suggestions were generated' );
			}

			try {
				const parsed = content
					.split( ',' )
					.map( ( suggestion ) => {
						return suggestion.trim();
					} )
					.filter( Boolean );

				onSuggestionsGenerated( parsed );
			} catch ( e ) {
				throw Error( 'Unable to parse suggestions' );
			}
		},
	} );

	const buildPrompt = async () => {
		const productPropsInstructions = generateProductDataInstructions( {
			excludeProps: [ ProductProps.Categories ],
		} );
		const instructions = [
			'You are a WooCommerce SEO and marketing expert.',
			`Using the product's ${ productPropsInstructions.includedProps.join(
				', '
			) } suggest the best matching category from the Google standard product category taxonomy hierarchy.`,
			'The category can optionally have multi-level children structures like Parent Category > Child Category.',
			"The product's properties are:",
			...productPropsInstructions.instructions,
			'Return only one best matching product category, children categories must be separated by >.',
			'Here is an example of a valid response:',
			'Parent Category > Child Category > Child of Child Category',
			'Do not output the example response. Respond only with the one suggested category. Do not say anything else.',
		];

		return instructions.join( '\n' );
	};

	const fetchSuggestions = async () => {
		await requestCompletion( await buildPrompt() );
	};

	return {
		fetchSuggestions,
	};
};
