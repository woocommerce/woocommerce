/**
 * External dependencies
 */
import { apiFetch } from '@wordpress/data-controls';

/**
 * Internal dependencies
 */
import { getUrlParameters, getRestPath, parseId } from '../crud/utils';
import TYPES from './action-types';
import { IdQuery, IdType, Item } from '../crud/types';
import { WC_PRODUCT_VARIATIONS_NAMESPACE } from './constants';

export function generateProductVariationsError( key: IdType, error: unknown ) {
	return {
		type: TYPES.GENERATE_VARIATIONS_ERROR as const,
		key,
		error,
		errorType: 'GENERATE_VARIATIONS',
	};
}

export const generateProductVariations = function* ( idQuery: IdQuery ) {
	const urlParameters = getUrlParameters(
		WC_PRODUCT_VARIATIONS_NAMESPACE,
		idQuery
	);

	try {
		const result: Item = yield apiFetch( {
			path: getRestPath(
				`${ WC_PRODUCT_VARIATIONS_NAMESPACE }/generate`,
				{},
				urlParameters
			),
			method: 'POST',
		} );

		return result;
	} catch ( error ) {
		const { key } = parseId( idQuery, urlParameters );

		yield generateProductVariationsError( key, error );
		throw error;
	}
};
