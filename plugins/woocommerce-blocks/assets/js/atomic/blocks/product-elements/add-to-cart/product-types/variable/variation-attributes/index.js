/**
 * Internal dependencies
 */
import './style.scss';
import AttributePicker from './attribute-picker';
import { getAttributes, getVariationAttributes } from './utils';

/**
 * VariationAttributes component.
 */
const VariationAttributes = ( { product, dispatchers } ) => {
	const attributes = getAttributes( product.attributes );
	const variationAttributes = getVariationAttributes( product.variations );

	if (
		Object.keys( attributes ).length === 0 ||
		variationAttributes.length === 0
	) {
		return null;
	}

	return (
		<AttributePicker
			attributes={ attributes }
			variationAttributes={ variationAttributes }
			setRequestParams={ dispatchers.setRequestParams }
		/>
	);
};

export default VariationAttributes;
