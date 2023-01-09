/**
 * External dependencies
 */
import { ProductAttribute } from '@woocommerce/data';

/**
 * Internal dependencies
 */
import { AttributeField } from '../attribute-field';
import { useProductAttributes } from '~/products/hooks/use-product-attributes';

type AttributesProps = {
	value: ProductAttribute[];
	onChange: ( value: ProductAttribute[] ) => void;
	productId?: number;
};

export const Attributes: React.FC< AttributesProps > = ( {
	value,
	onChange,
	productId,
} ) => {
	const { attributes, handleChange } = useProductAttributes( {
		allAttributes: value,
		onChange,
		productId,
	} );

	return (
		<AttributeField
			attributeType="regular"
			attributes={ attributes }
			onChange={ handleChange }
		/>
	);
};
