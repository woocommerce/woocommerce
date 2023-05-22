/**
 * External dependencies
 */
import apiFetch from '@wordpress/api-fetch';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import {
	AttributeSuggestion,
	AttributeSuggestionRequest,
} from '../shared/types';

type WooApiResponse = {
	suggestions: AttributeSuggestion[];
};

export const useAttributeSuggestions = () => {
	const fetchSuggestions = async ( request: AttributeSuggestionRequest ) => {
		if ( request.name.length < 10 && request.description.length < 50 ) {
			throw new Error(
				__(
					"🧐 We need more details about your product! Please add a descriptive title or description. Categories, tags, and attributes are a plus for better results!'",
					'woocommerce'
				)
			);
		}

		const response = await apiFetch< WooApiResponse >( {
			path: '/wooai/attribute-suggestions',
			method: 'POST',
			data: request,
		} );

		return response.suggestions;
	};

	return {
		fetchSuggestions,
	} as const;
};
