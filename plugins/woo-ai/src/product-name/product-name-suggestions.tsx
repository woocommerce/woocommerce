/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useEffect, useRef, useState } from '@wordpress/element';
import React from 'react';
import OutsideClickHandler from 'react-outside-click-handler';

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
import {
	RandomTipMessage,
	RandomLoadingMessage,
	ErrorMessage,
} from '../components';

enum SuggestionsState {
	Fetching = 'fetching',
	Failed = 'failed',
	None = 'none',
}

export function ProductNameSuggestions() {
	const [ suggestionsState, setSuggestionsState ] =
		useState< SuggestionsState >( SuggestionsState.None );
	const [ error, setError ] = useState< string >( '' );
	const [ visible, setVisible ] = useState< boolean >( false );
	const [ suggestions, setSuggestions ] = useState< ProductDataSuggestion[] >(
		[]
	);
	const { fetchSuggestions } = useProductDataSuggestions();
	const nameInputRef = useRef< HTMLInputElement >(
		document.querySelector( '#title' )
	);

	const resetError = () => {
		setError( '' );
		setSuggestionsState( SuggestionsState.None );
	};

	const onOutsideClick = ( e: MouseEvent ) => {
		if ( e.target instanceof Node && e.target !== nameInputRef.current ) {
			setVisible( false );
		}
	};

	useEffect( () => {
		const nameInput = nameInputRef.current;

		const onFocus = () => {
			setVisible( true );
		};

		if ( nameInput ) {
			nameInput.addEventListener( 'focus', onFocus );
			// Initially hide the container unless the input is already in focus
			const inputOwnerDocument = nameInput.ownerDocument;
			setVisible( inputOwnerDocument?.activeElement === nameInput );
		}

		return () => {
			if ( nameInput ) {
				nameInput.removeEventListener( 'focus', onFocus );
			}
		};
	}, [ nameInputRef ] );

	const updateProductName = ( newName: string ) => {
		if ( ! nameInputRef.current || ! newName.length ) return;
		nameInputRef.current.value = newName;
		nameInputRef.current.setAttribute( 'value', newName );
	};

	const handleSuggestionClick = ( suggestion: ProductDataSuggestion ) => {
		updateProductName( suggestion.content );
		setSuggestions( [] );
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
		} catch ( e ) {
			/* eslint-disable no-console */
			console.error( e );

			setError( e instanceof Error ? e.message : '' );
			setSuggestionsState( SuggestionsState.Failed );
		}
	};

	return (
		<OutsideClickHandler
			onOutsideClick={ onOutsideClick }
			display="contents"
		>
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
				{ suggestionsState !== SuggestionsState.Fetching && (
					<button
						className="button woo-ai-get-suggestions-btn"
						type="button"
						onClick={ fetchProductSuggestions }
					>
						<img src={ MagicIcon } alt="magic button icon" />
						{ suggestions.length > 0
							? __( 'Get more ideas', 'woocommerce' )
							: __(
									'Generate name ideas with AI',
									'woocommerce'
							  ) }
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
				{ suggestionsState === SuggestionsState.Fetching && (
					<p className="wc-product-name-suggestions__tip-message">
						<RandomTipMessage />
					</p>
				) }
				{ suggestionsState === SuggestionsState.Failed && (
					<p className="wc-product-name-suggestions__error-message">
						<ErrorMessage error={ error } />
						<button
							className="notice-dismiss"
							type="button"
							onClick={ resetError }
						>
							<span className="screen-reader-text">
								{ __( 'Dismiss this notice.', 'woocommerce' ) }
							</span>
						</button>
					</p>
				) }
			</div>
		</OutsideClickHandler>
	);
}
