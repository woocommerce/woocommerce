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
import type { BatchUpdateRequest, BatchUpdateResponse } from './types';

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

export function batchUpdateProductVariationsError(
	key: IdType,
	error: unknown
) {
	return {
		type: TYPES.BATCH_UPDATE_VARIATIONS_ERROR as const,
		key,
		error,
		errorType: 'BATCH_UPDATE_VARIATIONS',
	};
}

export function* batchUpdateProductVariations(
	idQuery: IdQuery,
	data: BatchUpdateRequest
) {
	const urlParameters = getUrlParameters(
		WC_PRODUCT_VARIATIONS_NAMESPACE,
		idQuery
	);

	try {
		const result: BatchUpdateResponse = yield apiFetch( {
			path: getRestPath(
				`${ WC_PRODUCT_VARIATIONS_NAMESPACE }/batch`,
				{},
				urlParameters
			),
			method: 'POST',
			data,
		} );

		return result;
	} catch ( error ) {
		const { key } = parseId( idQuery, urlParameters );

		yield batchUpdateProductVariationsError( key, error );
		throw error;
	}
}
