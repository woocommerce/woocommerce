/**
 * External dependencies
 */
import { createElement, useEffect } from '@wordpress/element';
import { ProductProductAttribute } from '@woocommerce/data';
import { __ } from '@wordpress/i18n';
import { recordEvent } from '@woocommerce/tracks';

/**
 * Internal dependencies
 */
import { AttributeControl } from '../attribute-control';
import { useProductAttributes } from '../../hooks/use-product-attributes';

type AttributesProps = {
	value: ProductProductAttribute[];
	onChange: ( value: ProductProductAttribute[] ) => void;
	productId?: number;
};

export const Attributes: React.FC< AttributesProps > = ( {
	value,
	onChange,
	productId,
} ) => {
	const { attributes, fetchAttributes, handleChange } = useProductAttributes(
		{
			allAttributes: value,
			onChange,
			productId,
		}
	);

	useEffect( () => {
		fetchAttributes();
	}, [ value ] );

	return (
		<AttributeControl
			value={ attributes }
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
			onChange={ handleChange }
			onNewModalCancel={ () => {
				recordEvent(
					'product_add_attributes_modal_cancel_button_click'
				);
			} }
			onNewModalOpen={ () => {
				if ( ! attributes.length ) {
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
