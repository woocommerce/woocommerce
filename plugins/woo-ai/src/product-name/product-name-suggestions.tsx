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
import { productData } from '../shared/productData';
import RandomLoadingMessage from '../shared/random-loading-message';
import ErrorMessage from '../shared/error-message';
import { useProductDataSuggestions } from '../hooks/useProductDataSuggestions';
import {
	ProductDataSuggestion,
	ProductDataSuggestionRequest,
} from '../shared/types';
import SuggestionItem from './suggestion-item';
import RandomTipMessage from '../shared/random-tip-message';

export function ProductNameSuggestions() {
	const [ fetching, setFetching ] = useState( false );
	const [ failed, setFailed ] = useState( false );
	const [ error, setError ] = useState( '' );
	const [ suggestions, setSuggestions ] = useState< ProductDataSuggestion[] >(
		[]
	);
	const { fetchSuggestions } = useProductDataSuggestions();

	const updateFailedStateWithError = ( errorMessage: string ) => {
		setError( errorMessage );
		setFailed( true );
	};

	const clearError = () => {
		setError( '' );
		setFailed( false );
	};

	const nameEl = useRef< HTMLInputElement >(
		document.querySelector( '#title' )
	);
	useEffect( () => {
		const name = nameEl.current;
		const titleChangeHandler = () => {
			setFailed( false );
		};

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
		// Set the product name to the suggestion.
		updateProductName( suggestion.content );
		setSuggestions( [] );
	};

	const getSuggestions = async () => {
		clearError();
		setSuggestions( [] );
		setFetching( true );
		try {
			const currentProductData = productData();
			const request: ProductDataSuggestionRequest = {
				requested_data: 'name',
				name: currentProductData.name,
				description: currentProductData.description,
				categories: currentProductData.categories,
				tags: currentProductData.tags,
				attributes: currentProductData.attributes,
			};
			setSuggestions( await fetchSuggestions( request ) );
		} catch ( e ) {
			/* eslint-disable no-console */
			console.error( e );

			updateFailedStateWithError( e instanceof Error ? e.message : '' );
		}
		setFetching( false );
	};

	return (
		<div className="wc-product-name-suggestions-container">
			{ suggestions.length > 0 && ! fetching && (
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
			{ ! fetching && (
				<button
					className="button woo-ai-get-suggestions-btn"
					type="button"
					disabled={ fetching }
					onClick={ getSuggestions }
				>
					<img src={ MagicIcon } alt="magic button icon" />
					{ suggestions.length > 0 &&
						__( 'Suggest Alternatives', 'woocommerce' ) }
					{ ! suggestions.length &&
						__( 'Get Suggestions (beta)', 'woocommerce' ) }
				</button>
			) }
			{ fetching && (
				<p className="wc-product-name-suggestions__loading-message">
					<RandomLoadingMessage isLoading={ fetching } />
				</p>
			) }
			{ fetching && (
				<p className="wc-product-name-suggestions__tip-message">
					<RandomTipMessage />
				</p>
			) }
			{ failed && (
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
