/**
 * Internal dependencies
 */
import { WPDataSelector, WPDataSelectors } from '../types';
import { ProductFormState } from './types';

export const getFields = ( state: ProductFormState ) => {
	return state.fields;
};

export const getField = ( state: ProductFormState, id: string ) => {
	return state.fields.find( ( field ) => field.id === id );
};

export const getProductForm = ( state: ProductFormState ) => {
	const { errors, ...form } = state;
	return form;
};

export type ProductFormSelectors = {
	getFields: WPDataSelector< typeof getFields >;
	getField: WPDataSelector< typeof getField >;
	getProductForm: WPDataSelector< typeof getProductForm >;
} & WPDataSelectors;
