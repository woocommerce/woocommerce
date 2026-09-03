/**
 * Internal dependencies
 */
import mustContain from './must-contain';

export const productPriceValidation = ( value: string ) =>
	mustContain( value, '<price/>' );

/**
 * Ensure that the screen reader price text contains required placeholders.
 *
 * Ensure the filter value contains the three required placeholders:
 * - <quantity/>
 * - <productName/>
 * - <price/>
 */
export const productPriceScreenReaderValidation = (
	value: string
): true | never => {
	return (
		mustContain( value, '<quantity/>' ) &&
		mustContain( value, '<productName/>' ) &&
		mustContain( value, '<price/>' )
	);
};
