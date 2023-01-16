/**
 * Internal dependencies
 */
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
