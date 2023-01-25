/**
 * External dependencies
 */
import { Product, ProductAttribute } from '@woocommerce/data';
import { useFormContext } from '@woocommerce/components';

/**
 * Internal dependencies
 */
import { AttributeControl } from '../attribute-control';
import { useProductAttributes } from '~/products/hooks/use-product-attributes';
import { useProductVariationsHelper } from '../../hooks/use-product-variations-helper';

type OptionsProps = {
	value: ProductAttribute[];
	onChange: ( value: ProductAttribute[] ) => void;
	productId?: number;
};

export const Options: React.FC< OptionsProps > = ( {
	value,
	onChange,
	productId,
} ) => {
	const { values } = useFormContext< Product >();
	const { generateProductVariations } = useProductVariationsHelper();

	const { attributes, handleChange } = useProductAttributes( {
		allAttributes: value,
		isVariationAttributes: true,
		onChange: ( newAttributes ) => {
			onChange( newAttributes );
			generateProductVariations( {
				...values,
				attributes: newAttributes,
			} );
		},
		productId,
	} );

	return (
		<AttributeControl
			attributeType="for-variations"
			value={ attributes }
			onChange={ handleChange }
		/>
	);
};
