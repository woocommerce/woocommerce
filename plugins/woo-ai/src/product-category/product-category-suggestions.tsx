/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useCallback, useEffect, useState } from '@wordpress/element';
import { UseCompletionError } from '@woocommerce/ai';

/**
 * Internal dependencies
 */
import { MagicButton, RandomLoadingMessage } from '../components';
import { getCategories, selectCategory } from '../utils';
import AlertIcon from '../../assets/images/icons/alert.svg';
import { getAvailableCategoryPaths, recordCategoryTracks } from './utils';
import { useNewCategorySuggestions } from './useNewCategorySuggestions';
import { useExistingCategorySuggestions } from './useExistingCategorySuggestions';
import { createCategoriesFromPath } from '../utils/categoryCreator';
import { CategorySuggestionFeedback } from './category-suggestion-feedback';

enum SuggestionsState {
	Initial,
	Fetching,
	Failed,
	Complete,
	None,
}

export const ProductCategorySuggestions = () => {
	const [ existingSuggestionsState, setExistingSuggestionsState ] =
		useState< SuggestionsState >( SuggestionsState.Initial );
	const [ newSuggestionsState, setNewSuggestionsState ] =
		useState< SuggestionsState >( SuggestionsState.Initial );
	const [ existingSuggestions, setExistingSuggestions ] = useState<
		string[]
	>( [] );
	const [ newSuggestions, setNewSuggestions ] = useState< string[] >( [] );
	const [ showFeedback, setShowFeedback ] = useState( false );
	let feedbackTimeout: number | null = null;

	useEffect( () => {
		recordCategoryTracks( 'view_ui' );
	}, [] );

	/**
	 * Show the feedback box after a delay.
	 */
	const showFeedbackAfterDelay = () => {
		if ( feedbackTimeout ) {
			clearTimeout( feedbackTimeout );
			feedbackTimeout = null;
		}

		feedbackTimeout = setTimeout( () => {
			setShowFeedback( true );
		}, 5000 );
	};

	/**
	 * Reset the feedback box.
	 */
	const resetFeedbackBox = () => {
		if ( feedbackTimeout ) {
			clearTimeout( feedbackTimeout );
			feedbackTimeout = null;
		}
		setShowFeedback( false );
	};

	/**
	 * Check if a suggestion is valid.
	 *
	 * @param suggestion         The suggestion to check.
	 * @param selectedCategories The currently selected categories.
	 */
	const isSuggestionValid = (
		suggestion: string,
		selectedCategories: string[]
	) => {
		return (
			suggestion !== __( 'Uncategorized', 'woocommerce' ) &&
			! selectedCategories.includes( suggestion )
		);
	};

	/**
	 * Callback for when the existing category suggestions have been generated.
	 *
	 * @param {string[]} existingCategorySuggestions The existing category suggestions.
	 */
	const onExistingCategorySuggestionsGenerated = async (
		existingCategorySuggestions: string[]
	) => {
		let filtered: string[] = [];
		try {
			const availableCategories = await getAvailableCategoryPaths();

			// Only show suggestions that are valid, available, and not already in the list of new suggestions.
			filtered = existingCategorySuggestions.filter(
				( suggestion ) =>
					isSuggestionValid( suggestion, getCategories() ) &&
					availableCategories.includes( suggestion ) &&
					! newSuggestions.includes( suggestion )
			);
		} catch ( e ) {
			// eslint-disable-next-line no-console
			console.error( 'Unable to fetch available categories.', e );
		}

		if ( filtered.length === 0 ) {
			setExistingSuggestionsState( SuggestionsState.None );
		} else {
			setExistingSuggestionsState( SuggestionsState.Complete );
		}
		setExistingSuggestions( filtered );

		showFeedbackAfterDelay();
		recordCategoryTracks( 'stop', {
			reason: 'finished',
			suggestions_type: 'existing',
			suggestions: existingCategorySuggestions,
			valid_suggestions: filtered,
		} );
	};

	/**
	 * Callback for when the new category suggestions have been generated.
	 *
	 * @param {string[]} newCategorySuggestions
	 */
	const onNewCategorySuggestionsGenerated = async (
		newCategorySuggestions: string[]
	) => {
		let filtered: string[] = [];
		try {
			const availableCategories = await getAvailableCategoryPaths();

			// Only show suggestions that are valid, NOT already available, and not already in the list of existing suggestions.
			filtered = newCategorySuggestions.filter(
				( suggestion ) =>
					isSuggestionValid( suggestion, getCategories() ) &&
					! availableCategories.includes( suggestion ) &&
					! existingSuggestions.includes( suggestion )
			);
		} catch ( e ) {
			// eslint-disable-next-line no-console
			console.error( 'Unable to fetch available categories.', e );
		}

		if ( filtered.length === 0 ) {
			setNewSuggestionsState( SuggestionsState.None );
		} else {
			setNewSuggestionsState( SuggestionsState.Complete );
		}
		setNewSuggestions( filtered );

		showFeedbackAfterDelay();
		recordCategoryTracks( 'stop', {
			reason: 'finished',
			suggestions_type: 'new',
			suggestions: newCategorySuggestions,
			valid_suggestions: filtered,
		} );
	};

	/**
	 * Callback for when an error occurs while generating the existing category suggestions.
	 *
	 * @param {UseCompletionError} error
	 */
	const onExistingCatSuggestionError = ( error: UseCompletionError ) => {
		// eslint-disable-next-line no-console
		console.debug( 'Streaming error encountered', error );
		recordCategoryTracks( 'stop', {
			reason: 'error',
			suggestions_type: 'existing',
			error: error.code ?? error.message,
		} );
		setExistingSuggestionsState( SuggestionsState.Failed );
	};

	/**
	 * Callback for when an error occurs while generating the new category suggestions.
	 *
	 * @param {UseCompletionError} error
	 */
	const onNewCatSuggestionError = ( error: UseCompletionError ) => {
		// eslint-disable-next-line no-console
		console.debug( 'Streaming error encountered', error );
		recordCategoryTracks( 'stop', {
			reason: 'error',
			suggestions_type: 'new',
			error: error.code ?? error.message,
		} );
		setNewSuggestionsState( SuggestionsState.Failed );
	};

	const { fetchSuggestions: fetchExistingCategorySuggestions } =
		useExistingCategorySuggestions(
			onExistingCategorySuggestionsGenerated,
			onExistingCatSuggestionError
		);

	const { fetchSuggestions: fetchNewCategorySuggestions } =
		useNewCategorySuggestions(
			onNewCategorySuggestionsGenerated,
			onNewCatSuggestionError
		);

	/**
	 * Callback for when an existing category suggestion is clicked.
	 *
	 * @param {string} suggestion The suggestion that was clicked.
	 */
	const handleExistingSuggestionClick = useCallback(
		( suggestion: string ) => {
			// remove the selected item from the list of suggestions
			setExistingSuggestions(
				existingSuggestions.filter( ( s ) => s !== suggestion )
			);
			selectCategory( suggestion );

			recordCategoryTracks( 'select', {
				selected_category: suggestion,
				suggestions_type: 'existing',
			} );
		},
		[ existingSuggestions ]
	);

	/**
	 * Callback for when a new category suggestion is clicked.
	 *
	 * @param {string} suggestion The suggestion that was clicked.
	 */
	const handleNewSuggestionClick = useCallback(
		async ( suggestion: string ) => {
			// remove the selected item from the list of suggestions
			setNewSuggestions(
				newSuggestions.filter( ( s ) => s !== suggestion )
			);

			try {
				await createCategoriesFromPath( suggestion );

				recordCategoryTracks( 'select', {
					selected_category: suggestion,
					suggestions_type: 'new',
				} );
			} catch ( e ) {
				// eslint-disable-next-line no-console
				console.error( 'Unable to create category', e );
			}
		},
		[ newSuggestions ]
	);

	const fetchProductSuggestions = async () => {
		resetFeedbackBox();
		setExistingSuggestions( [] );
		setNewSuggestions( [] );
		setExistingSuggestionsState( SuggestionsState.Fetching );
		setNewSuggestionsState( SuggestionsState.Fetching );

		recordCategoryTracks( 'start', {
			current_categories: getCategories(),
		} );

		await Promise.all( [
			fetchExistingCategorySuggestions(),
			fetchNewCategorySuggestions(),
		] );
	};

	return (
		<div className="wc-product-category-suggestions">
			<MagicButton
				onClick={ fetchProductSuggestions }
				disabled={
					existingSuggestionsState === SuggestionsState.Fetching ||
					newSuggestionsState === SuggestionsState.Fetching
				}
				label={ __( 'Suggest a category using AI', 'woocommerce' ) }
			/>
			{ ( existingSuggestionsState === SuggestionsState.Fetching ||
				newSuggestionsState === SuggestionsState.Fetching ) && (
				<div className="wc-product-category-suggestions__loading notice notice-info">
					<p className="wc-product-category-suggestions__loading-message">
						<RandomLoadingMessage
							isLoading={
								existingSuggestionsState ===
									SuggestionsState.Fetching ||
								newSuggestionsState ===
									SuggestionsState.Fetching
							}
						/>
					</p>
				</div>
			) }
			{ existingSuggestionsState === SuggestionsState.None &&
				newSuggestionsState === SuggestionsState.None && (
					<div className="wc-product-category-suggestions__no-match notice notice-warning">
						<p>
							{ __(
								'Unable to generate a matching category for the product. Please try including more information about the product in the title and description.',
								'woocommerce'
							) }
						</p>
					</div>
				) }
			{ existingSuggestionsState === SuggestionsState.Failed &&
				newSuggestionsState === SuggestionsState.Failed && (
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
			{ ( existingSuggestionsState === SuggestionsState.Complete ||
				newSuggestionsState === SuggestionsState.Complete ) && (
				<div>
					<ul className="wc-product-category-suggestions__suggestions">
						{ existingSuggestions.map( ( suggestion ) => (
							<li key={ suggestion }>
								<button
									title={ __(
										'Select category',
										'woocommerce'
									) }
									className="button-link"
									onClick={ () =>
										handleExistingSuggestionClick(
											suggestion
										)
									}
								>
									{ suggestion }
								</button>
							</li>
						) ) }
						{ newSuggestions.map( ( suggestion ) => (
							<li key={ suggestion }>
								<button
									title={ __(
										'Add and select category',
										'woocommerce'
									) }
									className="button-link"
									onClick={ () =>
										handleNewSuggestionClick( suggestion )
									}
								>
									{ suggestion }
								</button>
							</li>
						) ) }
					</ul>
					{ showFeedback && (
						<div className="wc-product-category-suggestions__feedback">
							<CategorySuggestionFeedback />
						</div>
					) }
				</div>
			) }
		</div>
	);
};
