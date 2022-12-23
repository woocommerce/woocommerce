/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { ProductAttribute } from '@woocommerce/data';

/**
 * Internal dependencies
 */
import { AttributeField } from '../attribute-field';

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
	return (
		<AttributeField
			attributeType="for-variations"
			value={ value }
			onChange={ onChange }
			productId={ productId }
		/>
	);
};
