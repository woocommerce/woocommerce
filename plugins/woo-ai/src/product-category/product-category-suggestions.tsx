/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useState } from '@wordpress/element';
import {
	__experimentalUseCompletion as useCompletion,
	UseCompletionError,
} from '@woocommerce/ai';

/**
 * Internal dependencies
 */
import { MagicButton, RandomLoadingMessage } from '../components';
import {
	getCategories,
	getAllAvailableCategories,
	selectCategory,
	generateProductDataInstructions,
} from '../utils';
import AlertIcon from '../../assets/images/icons/alert.svg';
import { SuggestionPills } from '../components/suggestion-pills';
import { WOO_AI_PLUGIN_FEATURE_NAME } from '../constants';

enum SuggestionsState {
	Initial,
	Fetching,
	Failed,
	NoMatch, // No matching categories found for the suggestions
	Complete,
	None,
}

export const ProductCategorySuggestions = () => {
	const [ suggestionsState, setSuggestionsState ] =
		useState< SuggestionsState >( SuggestionsState.Initial );
	const [ suggestions, setSuggestions ] = useState< string[] >( [] );

	const parseCategorySuggestions = ( categorySuggestions: string ) => {
		return categorySuggestions.split( ',' ).map( ( suggestion ) => {
			return suggestion.trim();
		} );
	};

	const filterValidCategorySuggestions = (
		categorySuggestions: string[]
	) => {
		const availableCategories = getAllAvailableCategories();
		const selectedCategories = getCategories();

		return categorySuggestions.filter( ( suggestion ) => {
			// return only the suggestions that exist in the list of all available categories and are not already selected
			return (
				availableCategories.includes( suggestion ) &&
				! selectedCategories.includes( suggestion )
			);
		} );
	};

	const { requestCompletion } = useCompletion( {
		feature: WOO_AI_PLUGIN_FEATURE_NAME,
		onStreamError: ( error: UseCompletionError ) => {
			// eslint-disable-next-line no-console
			console.debug( 'Streaming error encountered', error );
			setSuggestionsState( SuggestionsState.Failed );
		},
		onCompletionFinished: ( reason, content ) => {
			try {
				const parsed = parseCategorySuggestions( content );
				const filtered = filterValidCategorySuggestions( parsed );

				if ( parsed.length > 0 && filtered.length === 0 ) {
					setSuggestionsState( SuggestionsState.NoMatch );
				} else if ( parsed.length === 0 ) {
					setSuggestionsState( SuggestionsState.None );
				} else {
					setSuggestionsState( SuggestionsState.Complete );
				}

				setSuggestions( filtered );
			} catch ( e ) {
				setSuggestionsState( SuggestionsState.Failed );
				throw new Error( 'Unable to parse suggestions' );
			}
		},
	} );

	const handleSuggestionClick = ( suggestion: string ) => {
		// remove the selected item from the list of suggestions
		setSuggestions( suggestions.filter( ( s ) => s !== suggestion ) );
		selectCategory( suggestion );
	};

	const buildPrompt = () => {
		const allAvailableCategories = getAllAvailableCategories().join( ', ' );

		const productPropsInstructions = generateProductDataInstructions();
		const instructions = [
			'You are a WooCommerce SEO and marketing expert.',
			`Using the product's ${ productPropsInstructions.includedProps.join(
				', '
			) }, detect the main product category and its subcategories based on the existing available categories.`,
			"Your goal is to enhance the store's SEO performance and sales.",
			'Categories can have parents and multi-level children structures like Parent Category > Child Category.',
			'You will be given a list of available categories and you can only return the best matching categories from this list.',
			`Available categories are: ${ allAvailableCategories }`,
			"The product's properties are:",
			...productPropsInstructions.instructions,
			'Return only a comma-separated list of the product categories, children categories must be separated by >.',
			'Here is an example of a valid response:',
			'Parent Category 1 > Subcategory 1, Parent Category 2 > Subcategory 2 > Another Subcategory 2',
			'Do not output the example response. Respond only with the suggested categories. Do not say anything else.',
		];

		return instructions.join( '\n' );
	};

	const fetchProductSuggestions = async () => {
		setSuggestions( [] );
		setSuggestionsState( SuggestionsState.Fetching );

		await requestCompletion( buildPrompt() );
	};

	const onGetSuggestionsClick = () => {
		fetchProductSuggestions();
	};

	return (
		<div className="wc-product-category-suggestions">
			{ suggestions.length > 0 &&
				suggestionsState !== SuggestionsState.Fetching && (
					<SuggestionPills
						suggestions={ suggestions }
						onSuggestionClick={ handleSuggestionClick }
					/>
				) }
			{ suggestionsState === SuggestionsState.Fetching && (
				<div className="wc-product-category-suggestions__loading notice notice-info">
					<p className="wc-product-category-suggestions__loading-message">
						<RandomLoadingMessage
							isLoading={
								suggestionsState === SuggestionsState.Fetching
							}
						/>
					</p>
				</div>
			) }
			{ suggestionsState === SuggestionsState.NoMatch && (
				<div className="wc-product-category-suggestions__no-match notice notice-success">
					<p>
						{ __(
							'Your product is already well-categorized according to the properties you provided.',
							'woocommerce'
						) }
					</p>
				</div>
			) }
			{ suggestionsState === SuggestionsState.None && (
				<div className="wc-product-category-suggestions__no-results notice notice-warning">
					<p>
						{ __(
							'Unable to generate categories: please ensure sufficient product data, such as title, description, tags, and attributes, is provided for optimum results.',
							'woocommerce'
						) }
					</p>
				</div>
			) }
			{ suggestionsState === SuggestionsState.Failed && (
				<div
					className={ `wc-product-category-suggestions__error notice notice-error` }
				>
					<p className="wc-product-category-suggestions__error-message">
						<img src={ AlertIcon } alt="" />
						{ __(
							`We're currently experiencing high demand for our experimental feature. Please check back in shortly!`,
							'woocommerce'
						) }
					</p>
				</div>
			) }
			{ suggestionsState === SuggestionsState.Initial && (
				<MagicButton
					onClick={ onGetSuggestionsClick }
					label={ __( 'Suggest categories using AI', 'woocommerce' ) }
				/>
			) }
		</div>
	);
};
