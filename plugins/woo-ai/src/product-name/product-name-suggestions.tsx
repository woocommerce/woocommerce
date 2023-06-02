/**
 * External dependencies
 */
import React from 'react';
import { __ } from '@wordpress/i18n';
import { useCallback, useEffect, useRef, useState } from '@wordpress/element';

/**
 * Internal dependencies
 */
import MagicIcon from '../../assets/images/icons/magic.svg';
import AlertIcon from '../../assets/images/icons/alert.svg';
import { productData } from '../utils';
import { useProductDataSuggestions } from '../hooks/useProductDataSuggestions';
import {
	ProductDataSuggestion,
	ProductDataSuggestionRequest,
} from '../utils/types';
import { SuggestionItem, PoweredByLink, recordNameTracks } from './index';
import { RandomLoadingMessage } from '../components';

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
	const [ suggestions, setSuggestions ] = useState< ProductDataSuggestion[] >(
		[]
	);
	const { fetchSuggestions } = useProductDataSuggestions();
	const nameInputRef = useRef< HTMLInputElement >(
		document.querySelector( '#title' )
	);
	const [ productName, setProductName ] = useState< string >(
		nameInputRef.current?.value || ''
	);

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
						'#woocommerce-ai-app-product-name-suggestions *, #title'
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
			selectedTitle: suggestion.content,
		} );

		updateProductName( suggestion.content );
		setSuggestions( [] );
	};

	const fetchProductSuggestions = async (
		event: React.MouseEvent< HTMLElement >
	) => {
		if ( ( event.target as Element )?.closest( 'a' ) ) {
			return;
		}
		setSuggestions( [] );
		setSuggestionsState( SuggestionsState.Fetching );
		try {
			const currentProductData = productData();

			recordNameTracks( 'start', {
				currentTitle: currentProductData.name,
			} );

			const request: ProductDataSuggestionRequest = {
				requested_data: 'name',
				...currentProductData,
			};

			const suggestionResults = await fetchSuggestions( request );

			recordNameTracks( 'stop', {
				reason: 'finished',
				suggestions: suggestionResults,
			} );
			setSuggestions( suggestionResults );
			setSuggestionsState( SuggestionsState.None );
			setIsFirstLoad( false );
		} catch ( e ) {
			recordNameTracks( 'stop', {
				reason: 'error',
				error: ( e as { message?: string } )?.message || '',
			} );
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
					<>
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
					</>
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
