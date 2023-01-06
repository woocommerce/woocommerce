/**
 * External dependencies
 */
import { ProductAttribute } from '@woocommerce/data';

/**
 * Internal dependencies
 */
import { AttributeField } from '../attribute-field';
import {
	EnhancedProductAttribute,
	useProductAttributes,
} from '~/products/hooks/use-product-attributes';

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
	const { attributes, setAttributes } = useProductAttributes( {
		filter: ( attribute: EnhancedProductAttribute ) =>
			! attribute.variation,
		inputValue: value,
		productId,
	} );

	return (
		<AttributeField
			attributeType="regular"
			attributes={ attributes }
			onChange={ ( newAttributes ) => {
				setAttributes( newAttributes );
				onChange( newAttributes );
			} }
		/>
	);
};
