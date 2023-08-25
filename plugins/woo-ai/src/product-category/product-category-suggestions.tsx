/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useEffect, useState } from '@wordpress/element';
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
	selectCategory,
	generateProductDataInstructions,
} from '../utils';
import AlertIcon from '../../assets/images/icons/alert.svg';
import { SuggestionPills } from '../components/suggestion-pills';
import { WOO_AI_PLUGIN_FEATURE_NAME } from '../constants';
import { getAvailableCategories, recordCategoryTracks } from './utils';

enum SuggestionsState {
	Initial,
	Fetching,
	Failed,
	Complete,
	None,
}
type FilteredSuggestions = {
	newCategories: string[];
	existingCategories: string[];
};

export const ProductCategorySuggestions = () => {
	const [ suggestionsState, setSuggestionsState ] =
		useState< SuggestionsState >( SuggestionsState.Initial );
	const [ suggestions, setSuggestions ] = useState< string[] >( [] );
	const [ newCategorySuggestions, setNewCategorySuggestions ] = useState<
		string[]
	>( [] );

	useEffect( () => {
		recordCategoryTracks( 'view_ui' );
	}, [] );

	const parseCategorySuggestions = ( categorySuggestions: string ) => {
		return categorySuggestions.split( ',' ).map( ( suggestion ) => {
			return suggestion.trim();
		} );
	};

	const filterValidCategorySuggestions = async (
		categorySuggestions: string[]
	): Promise< FilteredSuggestions > => {
		const selectedCategories = getCategories();

		const newCategories: string[] = [];
		const existingCategories: string[] = [];

		try {
			const availableCategories = await getAvailableCategories();

			categorySuggestions.forEach( ( suggestion ) => {
				if ( selectedCategories.includes( suggestion ) ) {
					return;
				}
				if ( availableCategories.includes( suggestion ) ) {
					existingCategories.push( suggestion );
				} else {
					newCategories.push( suggestion );
				}
			} );
		} catch ( e ) {
			// eslint-disable-next-line no-console
			console.error( 'Unable to fetch available categories', e );
		}

		return {
			newCategories,
			existingCategories,
		};
	};

	const { requestCompletion } = useCompletion( {
		feature: WOO_AI_PLUGIN_FEATURE_NAME,
		onStreamError: ( error: UseCompletionError ) => {
			// eslint-disable-next-line no-console
			console.debug( 'Streaming error encountered', error );
			recordCategoryTracks( 'stop', {
				reason: 'error',
				error: error.code ?? error.message,
			} );
			setSuggestionsState( SuggestionsState.Failed );
		},
		onCompletionFinished: async ( reason, content ) => {
			try {
				const parsed = parseCategorySuggestions( content );
				const { newCategories, existingCategories } =
					await filterValidCategorySuggestions( parsed );

				if (
					newCategories.length === 0 &&
					existingCategories.length === 0
				) {
					setSuggestionsState( SuggestionsState.None );
				} else {
					setSuggestionsState( SuggestionsState.Complete );
				}

				recordCategoryTracks( 'stop', {
					reason: 'finished',
					suggestions: parsed,
					existing_suggestions: existingCategories,
					new_suggestions: newCategories,
				} );

				setSuggestions( existingCategories );
				setNewCategorySuggestions( newCategories );
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

		recordCategoryTracks( 'select', {
			selected_category: suggestion,
		} );
	};

	const buildPrompt = async () => {
		let availableCategories: string[] = [];
		try {
			availableCategories = await getAvailableCategories();
		} catch ( e ) {
			// eslint-disable-next-line no-console
			console.error( 'Unable to fetch available categories', e );
		}

		const productPropsInstructions = generateProductDataInstructions();
		const instructions = [
			'You are a WooCommerce SEO and marketing expert.',
			`Using the product's ${ productPropsInstructions.includedProps.join(
				', '
			) } suggest suitable product categories.`,
			'Categories can have parents and multi-level children structures like Parent Category > Child Category.',
			availableCategories
				? `You will be given a list of available categories. Find the best matching categories from this list. Available categories are: ${ availableCategories.join(
						', '
				  ) }`
				: '',
			"If no match is found, use the Google standard taxonomy hierarchy to suggest better product categories to store's SEO performance and sales.",
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
		setNewCategorySuggestions( [] );
		setSuggestionsState( SuggestionsState.Fetching );

		recordCategoryTracks( 'start', {
			current_categories: getCategories(),
		} );

		await requestCompletion( await buildPrompt() );
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
			{ newCategorySuggestions.length > 0 &&
				suggestionsState !== SuggestionsState.Fetching && (
					<div className={ `wc-product-category-suggestions__new` }>
						<p>
							{ __(
								'Consider adding these categories to your store:',
								'woocommerce'
							) }
						</p>
						<ul className="wc-product-category-suggestions__new-list">
							{ newCategorySuggestions.map( ( suggestion ) => (
								<li
									className="woocommerce-pill"
									key={ suggestion }
								>
									{ suggestion }
								</li>
							) ) }
						</ul>
					</div>
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
			{ suggestionsState === SuggestionsState.None && (
				<div className="wc-product-category-suggestions__no-match notice notice-warning">
					<p>
						{ __(
							'Unable to generate a matching category for the product.',
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
			{ suggestionsState !== SuggestionsState.Fetching && (
				<MagicButton
					onClick={ fetchProductSuggestions }
					label={ __( 'Suggest categories using AI', 'woocommerce' ) }
				/>
			) }
		</div>
	);
};
