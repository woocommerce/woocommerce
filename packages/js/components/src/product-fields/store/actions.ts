/**
 * Internal dependencies
 */
import TYPES from './action-types';
import { ProductFieldDefinition } from './types';

export function registerProductField( field: ProductFieldDefinition ) {
	return {
		type: TYPES.REGISTER_FIELD as const,
		field,
	};
}

export type Actions = ReturnType< typeof registerProductField >;
