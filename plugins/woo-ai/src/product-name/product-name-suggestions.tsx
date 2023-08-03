/**
 * External dependencies
 */
import React from 'react';
import { __ } from '@wordpress/i18n';
import { useCallback, useEffect, useRef, useState } from '@wordpress/element';
import { __experimentalUseCompletion as useCompletion } from '@woocommerce/ai';

/**
 * Internal dependencies
 */
import MagicIcon from '../../assets/images/icons/magic.svg';
import AlertIcon from '../../assets/images/icons/alert.svg';
import {
	getProductName,
	getPostId,
	getPublishingStatus,
	isProductDownloadable,
	isProductVirtual,
	getProductType,
	getTags,
	getAttributes,
} from '../utils';
import { useProductSlug } from '../hooks';
import { ProductDataSuggestion } from '../utils/types';
import { SuggestionItem, PoweredByLink, recordNameTracks } from './index';
import { RandomLoadingMessage } from '../components';
import { WOO_AI_PLUGIN_FEATURE_NAME } from '../constants';

const MIN_TITLE_LENGTH = 10;

enum SuggestionsState {
	Fetching = 'fetching',
	Failed = 'failed',
	None = 'none',
}

type TinyEditor = {
	on: ( eventName: string, handler: () => void ) => void;
};

declare const tinymce: {
	on: (
		eventName: 'addeditor',
		handler: ( event: Event & { editor: TinyEditor } ) => void,
		thing?: boolean
	) => void;
};

const MagicImage = () => (
	<img
		className="wc-product-name-suggestions__magic-image"
		src={ MagicIcon }
		alt=""
	/>
);

export const ProductNameSuggestions = () => {
	const [ suggestionsState, setSuggestionsState ] =
		useState< SuggestionsState >( SuggestionsState.None );
	const [ isFirstLoad, setIsFirstLoad ] = useState< boolean >( true );
	const [ visible, setVisible ] = useState< boolean >( false );
	const [ viewed, setViewed ] = useState< boolean >( false );
	const [ suggestions, setSuggestions ] = useState< ProductDataSuggestion[] >(
		[]
	);
	const { updateProductSlug } = useProductSlug();
	const { requestCompletion } = useCompletion( {
		feature: WOO_AI_PLUGIN_FEATURE_NAME,
		onStreamError: ( error ) => {
			// eslint-disable-next-line no-console
			console.debug( 'Streaming error encountered', error );
			recordNameTracks( 'stop', {
				reason: 'error',
				error: error.code ?? error.message,
			} );
			setSuggestionsState( SuggestionsState.Failed );
		},
		onCompletionFinished: ( reason, content ) => {
			try {
				const parsed = JSON.parse( content );
				setSuggestions( parsed.suggestions );
				setSuggestionsState( SuggestionsState.None );

				recordNameTracks( 'stop', {
					reason: 'finished',
					suggestions: parsed.suggestions,
				} );

				setSuggestions( parsed.suggestions );
				setIsFirstLoad( false );
			} catch ( e ) {
				setSuggestionsState( SuggestionsState.Failed );
				throw new Error( 'Unable to parse suggestions' );
			}
		},
	} );
	const nameInputRef = useRef< HTMLInputElement >(
		document.querySelector( '#title' )
	);
	const [ productName, setProductName ] = useState< string >(
		nameInputRef.current?.value || ''
	);

	useEffect( () => {
		if ( visible && ! viewed ) {
			setViewed( true );
			recordNameTracks( 'view_ui' );
		}
	}, [ visible, viewed ] );

	useEffect( () => {
		const nameInput = nameInputRef.current;

		const onFocus = () => {
			setVisible( true );
		};
		const onKeyUp = ( e: KeyboardEvent ) => {
			if ( e.key === 'Escape' ) {
				setVisible( false );
			}

			setSuggestions( [] );
			setProductName( ( e.target as HTMLInputElement ).value || '' );
		};

		const onChange = ( e: Event ) => {
			setProductName( ( e.target as HTMLInputElement ).value || '' );
		};

		const onBodyClick = ( e: MouseEvent ) => {
			const target = e.target as HTMLElement;
			if (
				! (
					nameInput?.ownerDocument.activeElement === nameInput ||
					// Need to capture errant handlediv click that happens on load as well
					Boolean( target.querySelector( ':scope > .handlediv' ) ) ||
					target?.matches(
						'#woocommerce-ai-app-product-name-suggestions *, #title, .woo-ai-get-suggestions-btn__content'
					)
				)
			) {
				setVisible( false );
			}
		};

		// Necessary since tinymce does not bubble click events.
		const onDOMLoad = () => {
			tinymce.on(
				'addeditor',
				( event ) =>
					event.editor.on( 'click', () => setVisible( false ) ),
				true
			);
		};

		if ( nameInput ) {
			nameInput.addEventListener( 'focus', onFocus );
			nameInput.addEventListener( 'keyup', onKeyUp );
			nameInput.addEventListener( 'change', onChange );
		}
		document.body.addEventListener( 'click', onBodyClick );
		document.addEventListener( 'DOMContentLoaded', onDOMLoad );

		return () => {
			if ( nameInput ) {
				nameInput.removeEventListener( 'focus', onFocus );
				nameInput.removeEventListener( 'keyup', onKeyUp );
				nameInput.removeEventListener( 'change', onChange );
			}
			document.body.removeEventListener( 'click', onBodyClick );
			document.removeEventListener( 'DOMContentLoaded', onDOMLoad );
		};
	}, [] );

	const updateProductName = ( newName: string ) => {
		if ( ! nameInputRef.current || ! newName.length ) {
			return;
		}
		nameInputRef.current.value = newName;
		nameInputRef.current.setAttribute( 'value', newName );

		// Ensure change event is fired for other interactions.
		nameInputRef.current.dispatchEvent( new Event( 'change' ) );

		setProductName( newName );
	};

	const handleSuggestionClick = ( suggestion: ProductDataSuggestion ) => {
		recordNameTracks( 'select', {
			selected_title: suggestion.content,
		} );

		updateProductName( suggestion.content );
		setSuggestions( [] );

		const productId = getPostId();
		const publishingStatus = getPublishingStatus();
		// Update product slug if product is a draft.
		if ( productId !== null && publishingStatus === 'draft' ) {
			try {
				updateProductSlug( suggestion.content, productId );
			} catch ( e ) {
				// Log silently if slug update fails.
				/* eslint-disable-next-line no-console */
				console.error( e );
			}
		}
	};

	const buildPrompt = () => {
		const validProductData = Object.entries( {
			name: getProductName(),
			tags: getTags(),
			attributes: getAttributes(),
			product_type: getProductType(),
			is_downloadable: isProductDownloadable(),
			is_virtual: isProductVirtual(),
		} ).reduce( ( acc, [ key, value ] ) => {
			if (
				typeof value === 'boolean' ||
				( value instanceof Array
					? Boolean( value.length )
					: Boolean( value ) )
			) {
				// eslint-disable-next-line @typescript-eslint/ban-ts-comment
				// @ts-ignore
				acc[ key ] = value;
			}
			return acc;
		}, {} );

		const instructions = [
			'You are a WooCommerce SEO and marketing expert.',
			"Using the product's name, description, tags, categories, and other attributes, provide three optimized alternatives to the product's title to enhance the store's SEO performance and sales.",
			"Provide the best option for the product's title based on the product properties.",
			'Identify the language used in the given title and use the same language in your response.',
			'Return only the alternative value for product\'s title in the "content" part of your response.',
			'Product titles should contain at least 20 characters.',
			'Return a short and concise reason for each suggestion in seven words in the "reason" part of your response.',
			"The product's properties are:",
			`${ JSON.stringify( validProductData ) }`,
			'Here is an example of a valid response:',
			'{"suggestions": [{"content": "An improved alternative to the product\'s title", "reason": "Reason for the suggestion"}, {"content": "Another improved alternative to the product title", "reason": "Reason for this suggestion"}]}',
		];

		return instructions.join( '\n' );
	};

	const fetchProductSuggestions = async (
		event: React.MouseEvent< HTMLElement >
	) => {
		if ( ( event.target as Element )?.closest( 'a' ) ) {
			return;
		}

		setSuggestions( [] );
		setSuggestionsState( SuggestionsState.Fetching );

		recordNameTracks( 'start', {
			current_title: getProductName(),
		} );

		try {
			await requestCompletion( buildPrompt() );
		} catch ( e ) {
			setSuggestionsState( SuggestionsState.Failed );
		}
	};

	const shouldRenderSuggestionsButton = useCallback( () => {
		return (
			productName.length >= MIN_TITLE_LENGTH &&
			suggestionsState !== SuggestionsState.Fetching
		);
	}, [ productName, suggestionsState ] );

	const getSuggestionsButtonLabel = useCallback( () => {
		return isFirstLoad
			? __( 'Generate name ideas with AI', 'woocommerce' )
			: __( 'Get more ideas', 'woocommerce' );
	}, [ isFirstLoad ] );

	return (
		<div
			className="wc-product-name-suggestions-container"
			hidden={ ! visible }
		>
			{ suggestions.length > 0 &&
				suggestionsState !== SuggestionsState.Fetching && (
					<ul className="wc-product-name-suggestions__suggested-names">
						{ suggestions.map( ( suggestion, index ) => (
							<SuggestionItem
								key={ index }
								suggestion={ suggestion }
								onSuggestionClick={ handleSuggestionClick }
							/>
						) ) }
					</ul>
				) }
			{ productName.length < MIN_TITLE_LENGTH &&
				suggestionsState === SuggestionsState.None && (
					<div className="wc-product-name-suggestions__tip-message">
						<div>
							<MagicImage />
							{ __(
								'Enter a few descriptive words to generate product name.',
								'woocommerce'
							) }
						</div>
						<PoweredByLink />
					</div>
				) }
			{ suggestionsState !== SuggestionsState.Failed && (
				<button
					className="button woo-ai-get-suggestions-btn"
					type="button"
					onClick={ fetchProductSuggestions }
					style={ {
						display: shouldRenderSuggestionsButton()
							? 'flex'
							: 'none',
					} }
				>
					<div className="woo-ai-get-suggestions-btn__content">
						<MagicImage />
						{ getSuggestionsButtonLabel() }
					</div>
					<PoweredByLink />
				</button>
			) }
			{ suggestionsState === SuggestionsState.Fetching && (
				<p className="wc-product-name-suggestions__loading-message">
					<RandomLoadingMessage
						isLoading={
							suggestionsState === SuggestionsState.Fetching
						}
					/>
				</p>
			) }
			{ suggestionsState === SuggestionsState.Failed && (
				<p className="wc-product-name-suggestions__error-message">
					<img src={ AlertIcon } alt="" />
					{ __(
						`We're currently experiencing high demand for our experimental feature. Please check back in shortly!`,
						'woocommerce'
					) }
				</p>
			) }
		</div>
	);
};
