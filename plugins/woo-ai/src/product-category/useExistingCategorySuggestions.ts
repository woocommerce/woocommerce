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
import { getAvailableCategoryPaths } from './utils';

type UseExistingCategorySuggestionsHook = {
	fetchSuggestions: () => Promise< void >;
};

export const useExistingCategorySuggestions = (
	onSuggestionsGenerated: ( suggestions: string[] ) => void,
	onError: ( error: UseCompletionError ) => void
): UseExistingCategorySuggestionsHook => {
	const { requestCompletion } = useCompletion( {
		feature: WOO_AI_PLUGIN_FEATURE_NAME,
		onStreamError: ( error: UseCompletionError ) => {
			// eslint-disable-next-line no-console
			console.debug( 'Streaming error encountered', error );

			onError( error );
		},
		onCompletionFinished: async ( reason, content ) => {
			if ( reason === 'error' ) {
				throw Error( 'Invalid response' );
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
		let availableCategories: string[] = [];
		try {
			availableCategories = await getAvailableCategoryPaths();
		} catch ( e ) {
			// eslint-disable-next-line no-console
			console.error( 'Unable to fetch available categories', e );
		}

		const productPropsInstructions = generateProductDataInstructions( {
			excludeProps: [ ProductProps.Categories ],
		} );
		const instructions = [
			'You are a WooCommerce SEO and marketing expert.',
			`Using the product's ${ productPropsInstructions.includedProps.join(
				', '
			) } suggest only one category that best matches the product.`,
			'Categories can have parents and multi-level children structures like Parent Category > Child Category.',
			availableCategories
				? `You will be given a list of available categories. Find the best matching category from this list. Available categories are: ${ availableCategories.join(
						', '
				  ) }`
				: '',
			"The product's properties are:",
			...productPropsInstructions.instructions,
			'Return only one product category, children categories must be separated by >.',
			'Here is an example of a valid response:',
			'Parent Category > Subcategory > Another Subcategory',
			'Do not output the example response. Respond only with the suggested categories. Do not say anything else.',
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
