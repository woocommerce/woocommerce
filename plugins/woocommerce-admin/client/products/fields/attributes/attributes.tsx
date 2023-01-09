/**
 * External dependencies
 */
import { ProductAttribute } from '@woocommerce/data';
import { reject } from 'lodash';

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
	const attributesFilter = ( attribute: EnhancedProductAttribute ) =>
		! attribute.variation;

	const { attributes, setAttributes } = useProductAttributes( {
		initialAttributes: ( value || [] ).filter( attributesFilter ),
		productId,
	} );

	const handleChange = ( newAttributes: ProductAttribute[] ) => {
		const otherAttributes = reject(
			value,
			attributesFilter
		) as ProductAttribute[];
		onChange( [ ...otherAttributes, ...newAttributes ] );
		setAttributes( newAttributes );
	};

	return (
		<AttributeField
			attributeType="regular"
			attributes={ attributes }
			onChange={ handleChange }
		/>
	);
};
