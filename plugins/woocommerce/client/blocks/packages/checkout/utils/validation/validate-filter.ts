/**
 * Internal dependencies
 */
import mustContain from './must-contain';

export const productPriceValidation = ( value: string ) =>
	mustContain( value, '<price/>' );
