/**
 * External dependencies
 */
import { Product, ProductAttribute } from '@woocommerce/data';
import { reject } from 'lodash';
import { useFormContext } from '@woocommerce/components';

/**
 * Internal dependencies
 */
import { AttributeField } from '../attribute-field';
import {
	EnhancedProductAttribute,
	useProductAttributes,
} from '~/products/hooks/use-product-attributes';
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

	const optionsFilter = ( attribute: EnhancedProductAttribute ) =>
		!! attribute.variation;

	const { attributes, setAttributes } = useProductAttributes( {
		initialAttributes: ( value || [] ).filter( optionsFilter ),
		productId,
	} );

	const handleChange = async ( newAttributes: ProductAttribute[] ) => {
		const otherAttributes = reject(
			value as ProductAttribute[],
			optionsFilter
		) as ProductAttribute[];
		onChange( [ ...otherAttributes, ...newAttributes ] );
		setAttributes( newAttributes );
		generateProductVariations( {
			...values,
			attributes: [ ...otherAttributes, ...newAttributes ],
		} );
	};

	return (
		<AttributeField
			attributeType="for-variations"
			attributes={ attributes }
			onChange={ handleChange }
		/>
	);
};
