/**
 * External dependencies
 */
import { apiFetch } from '@wordpress/data-controls';

/**
 * Internal dependencies
 */
import {
	getFieldsSuccess,
	getFieldsError,
	getProductFormSuccess,
	getProductFormError,
} from './actions';
import { WC_ADMIN_NAMESPACE } from '../constants';
import { ProductFormField, ProductForm } from './types';

export function* getFields() {
	try {
		const url = WC_ADMIN_NAMESPACE + '/product-form/fields';
		const results: ProductFormField[] = yield apiFetch( {
			path: url,
			method: 'GET',
		} );

		return getFieldsSuccess( results );
	} catch ( error ) {
		return getFieldsError( error );
	}
}

export function* getProductForm() {
	try {
		const url = WC_ADMIN_NAMESPACE + '/product-form';
		const results: ProductForm = yield apiFetch( {
			path: url,
			method: 'GET',
		} );

		return getProductFormSuccess( results );
	} catch ( error ) {
		return getProductFormError( error );
	}
}
