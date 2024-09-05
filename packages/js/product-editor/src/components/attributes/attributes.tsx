/**
 * External dependencies
 */
import { createElement } from '@wordpress/element';
import { ProductProductAttribute } from '@woocommerce/data';
import { __ } from '@wordpress/i18n';
import { recordEvent } from '@woocommerce/tracks';

/**
 * Internal dependencies
 */
import { AttributeControl } from '../attribute-control';

type AttributesProps = {
	attributeList?: ProductProductAttribute[];
	value: ProductProductAttribute[];
	onChange: ( value: ProductProductAttribute[] ) => void;
};

/**
 * This component is no longer in active use.
 * It is kept here for backward compatibility because is being used in the `AttributesField` component, under
 * `plugins/woocommerce-admin/client/products/fills/attributes-section/attributes-field.tsx`.
 */
export const Attributes: React.FC< AttributesProps > = ( {
	value,
	onChange,
	attributeList = [],
} ) => {
	return (
		<AttributeControl
			value={ attributeList }
			disabledAttributeIds={ value
				.filter( ( attr ) => !! attr.variation )
				.map( ( attr ) => attr.id ) }
			uiStrings={ {
				disabledAttributeMessage: __(
					'Already used in Variations',
					'woocommerce'
				),
			} }
			onAdd={ () => {
				recordEvent( 'product_add_attributes_modal_add_button_click' );
			} }
			onChange={ onChange }
			onNewModalCancel={ () => {
				recordEvent(
					'product_add_attributes_modal_cancel_button_click'
				);
			} }
			onNewModalOpen={ () => {
				if ( ! attributeList.length ) {
					recordEvent( 'product_add_first_attribute_button_click' );
					return;
				}
				recordEvent( 'product_add_attribute_button' );
			} }
			onAddAnother={ () => {
				recordEvent(
					'product_add_attributes_modal_add_another_attribute_button_click'
				);
			} }
			onRemoveItem={ () => {
				recordEvent(
					'product_add_attributes_modal_remove_attribute_button_click'
				);
			} }
			onRemove={ () =>
				recordEvent(
					'product_remove_attribute_confirmation_confirm_click'
				)
			}
			onRemoveCancel={ () =>
				recordEvent(
					'product_remove_attribute_confirmation_cancel_click'
				)
			}
			termsAutoSelection="first"
			defaultVisibility={ true }
		/>
	);
};
