/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useCallback, useEffect, useRef, useState } from '@wordpress/element';
import React from 'react';
import { Pill } from '@woocommerce/components';

/**
 * Internal dependencies
 */
import MagicIcon from '../../assets/images/icons/magic.svg';
import { productData } from '../utils';
import { useProductDataSuggestions } from '../hooks/useProductDataSuggestions';
import {
	ProductDataSuggestion,
	ProductDataSuggestionRequest,
} from '../utils/types';
import SuggestionItem from './suggestion-item';
import RandomLoadingMessage from '../components/random-loading-message';

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

export const ProductNameSuggestions = () => {
	const [ suggestionsState, setSuggestionsState ] =
		useState< SuggestionsState >( SuggestionsState.None );
	const [ error, setError ] = useState< string >( '' );
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

	const resetError = () => {
		setError( '' );
		setSuggestionsState( SuggestionsState.None );
	};

	useEffect( () => {
		const nameInput = nameInputRef.current;

		const onFocus = () => {
			setVisible( true );
		};
		const onKeyUp = ( e: KeyboardEvent ) => {
			if ( e.key === 'Escape' ) {
				setVisible( false );
			}

			setProductName( ( e.target as HTMLInputElement ).value || '' );
		};

		const onChange = ( e: Event ) => {
			setProductName( ( e.target as HTMLInputElement ).value || '' );
		};

		const onBodyClick = ( e: MouseEvent ) => {
			const target = e.target as HTMLElement;

			// Need to capture errant handlediv click that happens on load as well
			if (
				target?.matches( '.handlediv' ) ||
				! target?.matches(
					'#woocommerce-ai-app-product-name-suggestions *, #title'
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
		if ( ! nameInputRef.current || ! newName.length ) return;
		nameInputRef.current.value = newName;
		nameInputRef.current.setAttribute( 'value', newName );
		setProductName( newName );
	};

	const handleSuggestionClick = ( suggestion: ProductDataSuggestion ) => {
		updateProductName( suggestion.content );
		setSuggestions( [] );
		resetError();
	};

	const fetchProductSuggestions = async () => {
		resetError();
		setSuggestions( [] );
		setSuggestionsState( SuggestionsState.Fetching );
		try {
			const currentProductData = productData();
			const request: ProductDataSuggestionRequest = {
				requested_data: 'name',
				...currentProductData,
			};
			setSuggestions( await fetchSuggestions( request ) );
			setSuggestionsState( SuggestionsState.None );
			setIsFirstLoad( false );
		} catch ( e ) {
			setSuggestionsState( SuggestionsState.Failed );
			setError( e instanceof Error ? e.message : '' );
		}
	};

	const shouldRenderSuggestionsButton = useCallback( () => {
		return (
			productName.length >= 10 &&
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
			{ productName.length < 10 &&
				suggestionsState !== SuggestionsState.Fetching && (
					<p className="wc-product-name-suggestions__tip-message">
						<img src={ MagicIcon } alt="magic button icon" />
						{ __(
							'Enter a few descriptive words to generate product name using AI (beta).',
							'woocommerce'
						) }
					</p>
				) }
			<button
				className="button woo-ai-get-suggestions-btn"
				type="button"
				onClick={ fetchProductSuggestions }
				style={ {
					display: shouldRenderSuggestionsButton() ? 'flex' : 'none',
				} }
			>
				<div className="woo-ai-get-suggestions-btn__content">
					<img src={ MagicIcon } alt="magic button icon" />
					{ getSuggestionsButtonLabel() }
				</div>
				<Pill>{ __( 'Experimental', 'woocommerce' ) }</Pill>
			</button>
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
					{ error }
				</p>
			) }
		</div>
	);
};
