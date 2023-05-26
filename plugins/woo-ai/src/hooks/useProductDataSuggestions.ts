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
	ApiErrorResponse,
} from '../utils/types';

type ProductDataSuggestionSuccessResponse = {
	suggestions: ProductDataSuggestion[];
};

type ProductDataSuggestionErrorResponse = ApiErrorResponse;

export const useProductDataSuggestions = () => {
	const fetchSuggestions = async (
		request: ProductDataSuggestionRequest
	) => {
		try {
			const response =
				await apiFetch< ProductDataSuggestionSuccessResponse >( {
					path: '/wooai/product-data-suggestions',
					method: 'POST',
					data: request,
				} );

			return response.suggestions;
		} catch ( e ) {
			/* eslint-disable no-console */
			console.error( e );

			if ( ! ( e as ProductDataSuggestionErrorResponse )?.data?.status ) {
				throw new Error(
					__(
						`Apologies, this is an experimental feature and there was an error with this service. Please try again.`,
						'woocommerce'
					)
				);
			}

			throw new Error(
				( e as ProductDataSuggestionErrorResponse ).message
			);
		}
	};

	return {
		fetchSuggestions,
	} as const;
};
