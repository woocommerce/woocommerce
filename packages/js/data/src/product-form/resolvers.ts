/**
 * External dependencies
 */
import { apiFetch, select } from '@wordpress/data-controls';
import { controls } from '@wordpress/data';

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
import { STORE_NAME } from './constants';

const resolveSelect =
	controls && controls.resolveSelect ? controls.resolveSelect : select;

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

export function* getCountry() {
	yield resolveSelect( STORE_NAME, 'getProductForm' );
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
