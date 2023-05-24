/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useRef, useState } from '@wordpress/element';
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

	const handleSuggestionClick = ( suggestion: ProductDataSuggestion ) => {
		if ( ! nameEl.current || ! suggestion.content.length ) return;
		// Set the product name to the suggestion.
		nameEl.current.value = suggestion.content;
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
		<div className="title-suggestions-container">
			{ suggestions.length > 0 && ! fetching && (
				<ul className="suggested-titles">
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
					className="button woo-ai-write-it-for-me-btn"
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
				<p className="loading-message">
					<RandomLoadingMessage isLoading={ fetching } />
				</p>
			) }
			{ fetching && (
				<p className="tip-message">
					<RandomTipMessage />
				</p>
			) }
			{ failed && (
				<p className="error-message">
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
