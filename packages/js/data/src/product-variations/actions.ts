/**
 * External dependencies
 */
import { apiFetch } from '@wordpress/data-controls';
import { controls } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { getUrlParameters, getRestPath, parseId } from '../crud/utils';
import TYPES from './action-types';
import { IdQuery, IdType, Item } from '../crud/types';
import { WC_PRODUCT_VARIATIONS_NAMESPACE } from './constants';
import type {
	BatchUpdateRequest,
	BatchUpdateResponse,
	GenerateRequest,
} from './types';
import CRUD_ACTIONS from './crud-actions';
import {
	Product,
	ProductProductAttribute,
	ProductDefaultAttribute,
} from '../products/types';

export function generateProductVariationsError( key: IdType, error: unknown ) {
	return {
		type: TYPES.GENERATE_VARIATIONS_ERROR as const,
		key,
		error,
		errorType: CRUD_ACTIONS.GENERATE_VARIATIONS,
	};
}

export function generateProductVariationsRequest( key: IdType ) {
	return {
		type: TYPES.GENERATE_VARIATIONS_REQUEST as const,
		key,
	};
}

export function generateProductVariationsSuccess( key: IdType ) {
	return {
		type: TYPES.GENERATE_VARIATIONS_SUCCESS as const,
		key,
	};
}

export const generateProductVariations = function* (
	idQuery: IdQuery,
	productData: {
		type?: string;
		attributes: ProductProductAttribute[];
		default_attributes?: ProductDefaultAttribute[];
		meta_data?: Product[ 'meta_data' ];
	},
	data: GenerateRequest,
	saveAttributes = true
) {
	const urlParameters = getUrlParameters(
		WC_PRODUCT_VARIATIONS_NAMESPACE,
		idQuery
	);
	const { key } = parseId( idQuery, urlParameters );
	yield generateProductVariationsRequest( key );

	if ( saveAttributes ) {
		try {
			yield controls.dispatch(
				'core',
				'saveEntityRecord',
				'postType',
				'product',
				{
					id: urlParameters[ 0 ],
					...productData,
				}
			);
		} catch ( error ) {
			yield generateProductVariationsError( key, error );
			throw error;
		}
	}

	try {
		const result: Item = yield apiFetch( {
			path: getRestPath(
				`${ WC_PRODUCT_VARIATIONS_NAMESPACE }/generate`,
				{},
				urlParameters
			),
			method: 'POST',
			data,
		} );
		yield generateProductVariationsSuccess( key );
		return result;
	} catch ( error ) {
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

export type Actions = ReturnType<
	| typeof generateProductVariationsRequest
	| typeof generateProductVariationsError
	| typeof generateProductVariationsSuccess
>;
