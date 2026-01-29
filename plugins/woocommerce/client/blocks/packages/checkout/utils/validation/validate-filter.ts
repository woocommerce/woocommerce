/**
 * Internal dependencies
 */
import mustContain from './must-contain';

export const productPriceValidation = ( value: string ) =>
	mustContain( value, '<price/>' );

/**
 * Ensure that the screen reader price text contains a string.
 *
 * Extensions such as WooCommerce Deposits manipulate the `<price/>` element
 * to contain additional text (e.g, $x.xx due today) so the text here needs
 * to be open so the extensions can replace the text with their own in its
 * entirety.
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
