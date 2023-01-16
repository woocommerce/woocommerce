/**
 * External dependencies
 */
import { Product, ProductAttribute } from '@woocommerce/data';
import { useFormContext } from '@woocommerce/components';

/**
 * Internal dependencies
 */
import { AttributeField } from '../attribute-field';
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

	const handleChange = async ( attributes: ProductAttribute[] ) => {
		onChange( attributes );
		generateProductVariations( { ...values, attributes } );
	};

	return (
		<AttributeField
			attributeType="for-variations"
			value={ value }
			onChange={ handleChange }
			productId={ productId }
		/>
	);
};
