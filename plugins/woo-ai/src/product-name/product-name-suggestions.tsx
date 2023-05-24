/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useEffect, useRef, useState } from '@wordpress/element';
import React from 'react';

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
	const [ error, setError ] = useState( '' );
	const [ suggestions, setSuggestions ] = useState< ProductDataSuggestion[] >(
		[]
	);
	const { fetchSuggestions } = useProductDataSuggestions();

	const clearError = () => {
		setError( '' );
		setSuggestionsState( SuggestionsState.None );
	};

	const nameEl = useRef< HTMLInputElement >(
		document.querySelector( '#title' )
	);

	const titleChangeHandler = () => {
		clearError();
	};
	useEffect( () => {
		const name = nameEl.current;

		name?.addEventListener( 'keyup', titleChangeHandler );
		name?.addEventListener( 'change', titleChangeHandler );

		return () => {
			name?.removeEventListener( 'keyup', titleChangeHandler );
			name?.removeEventListener( 'change', titleChangeHandler );
		};
	}, [ nameEl ] );

	const updateProductName = ( newName: string ) => {
		if ( ! nameEl.current || ! newName.length ) return;
		nameEl.current.value = newName;
		nameEl.current.setAttribute( 'value', newName );
	};

	const handleSuggestionClick = ( suggestion: ProductDataSuggestion ) => {
		updateProductName( suggestion.content );
		setSuggestions( [] );
	};

	const getSuggestions = async () => {
		clearError();
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
		<div className="wc-product-name-suggestions-container">
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
					onClick={ getSuggestions }
				>
					<img src={ MagicIcon } alt="magic button icon" />
					{ suggestions.length > 0 &&
						__( 'Suggest Alternatives', 'woocommerce' ) }
					{ ! suggestions.length &&
						__( 'Get Suggestions (beta)', 'woocommerce' ) }
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
						onClick={ clearError }
					>
						<span className="screen-reader-text">
							{ __( 'Dismiss this notice.', 'woocommerce' ) }
						</span>
					</button>
				</p>
			) }
		</div>
	);
}
