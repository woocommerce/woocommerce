/**
 * Internal dependencies
 */
import './style.scss';
import AttributePicker from './attribute-picker';
import { getAttributes, getVariationAttributes } from './utils';

/**
 * VariationAttributes component.
 *
 * @param {*} props Component props.
 */
const VariationAttributes = ( { product } ) => {
	const {
		attributes: productAttributes = {},
		variations: productVariations = [],
	} = product;

	const attributes = getAttributes( productAttributes );
	const variationAttributes = getVariationAttributes( productVariations );

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
		/>
	);
};

export default VariationAttributes;
