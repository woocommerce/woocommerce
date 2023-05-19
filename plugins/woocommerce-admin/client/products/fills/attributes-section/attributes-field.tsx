/**
 * External dependencies
 */
import { useFormContext } from '@woocommerce/components';
import { __experimentalAttributes as Attributes } from '@woocommerce/product-editor';
import { Product } from '@woocommerce/data';

/**
 * Internal dependencies
 */

export const AttributesField = () => {
	const {
		getInputProps,
		values: { id: productId },
	} = useFormContext< Product >();
	return (
		<Attributes
			{ ...getInputProps( 'attributes', {
				productId,
			} ) }
		/>
	);
};
