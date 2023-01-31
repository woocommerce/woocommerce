/**
 * External dependencies
 */
import { useFormContext } from '@woocommerce/components';

import { Product } from '@woocommerce/data';

/**
 * Internal dependencies
 */
import { Attributes } from '../../fields/attributes';

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
