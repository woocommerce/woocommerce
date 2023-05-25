/**
 * External dependencies
 */
import apiFetch from '@wordpress/api-fetch';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import {
	ProductDataSuggestion,
	ProductDataSuggestionRequest,
} from '../utils/types';

type WooApiResponse = {
	suggestions: ProductDataSuggestion[];
};

export const useProductDataSuggestions = () => {
	const fetchSuggestions = async (
		request: ProductDataSuggestionRequest
	) => {
		if ( request.name.length < 10 && request.description.length < 50 ) {
			throw new Error(
				__(
					'Enter a few descriptive words or add product description, tags, or attributes to generate name ideas.',
					'woocommerce'
				)
			);
		}

		const response = await apiFetch< WooApiResponse >( {
			path: '/wooai/product-data-suggestions',
			method: 'POST',
			data: request,
		} );

		return response.suggestions;
	};

	return {
		fetchSuggestions,
	} as const;
};
