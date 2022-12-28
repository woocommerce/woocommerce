/**
 * External dependencies
 */
import { ProductAttribute } from '@woocommerce/data';

/**
 * Internal dependencies
 */
import { AttributeField } from '../attribute-field';

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
	return (
		<AttributeField
			attributeType="regular"
			value={ value }
			onChange={ onChange }
			productId={ productId }
		/>
	);
};
