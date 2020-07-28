/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { decodeEntities } from '@wordpress/html-entities';
import { SelectControl } from '@wordpress/components';

// Default option for select boxes.
const selectAnOption = {
	value: '',
	label: __( 'Select an option', 'woocommerce' ),
};

/**
 * VariationAttributeSelect component.
 *
 * @param {*} props Component props.
 */
const AttributeSelectControl = ( {
	attributeName,
	options = [],
	selected = '',
	onChange = () => {},
} ) => {
	return (
		<SelectControl
			className="wc-block-components-product-add-to-cart-attribute-picker__select"
			label={ decodeEntities( attributeName ) }
			value={ selected || '' }
			options={ [ selectAnOption, ...options ] }
			onChange={ onChange }
		/>
	);
};

export default AttributeSelectControl;
