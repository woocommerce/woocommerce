/**
 * External dependencies
 */
import { BlockAttributes } from '@wordpress/blocks';
import { createElement, useEffect } from '@wordpress/element';
import { useWooBlockProps } from '@woocommerce/block-templates';
import { ProductProductAttribute } from '@woocommerce/data';
import { __ } from '@wordpress/i18n';
import { recordEvent } from '@woocommerce/tracks';
// eslint-disable-next-line @typescript-eslint/ban-ts-comment
// @ts-ignore No types for this exist yet.
// eslint-disable-next-line @woocommerce/dependency-group
import { useEntityProp, useEntityId } from '@wordpress/core-data';

/**
 * Internal dependencies
 */
import { AttributeControl } from '../../../components/attribute-control';
import { ProductEditorBlockEditProps } from '../../../types';
import { useProductAttributes } from '../../../hooks/use-product-attributes';

export function AttributesBlockEdit( {
	attributes,
	context: { isInSelectedTab },
}: ProductEditorBlockEditProps< BlockAttributes > ) {
	const [ entityAttributes, setEntityAttributes ] = useEntityProp<
		ProductProductAttribute[]
	>( 'postType', 'product', 'attributes' );

	const productId = useEntityId( 'postType', 'product' );

	const blockProps = useWooBlockProps( attributes );

	const {
		attributes: attributeList,
		fetchAttributes,
		handleChange,
	} = useProductAttributes( {
		allAttributes: entityAttributes,
		onChange: setEntityAttributes,
		productId,
	} );

	useEffect( () => {
		if ( isInSelectedTab ) {
			fetchAttributes();
		}
	}, [ entityAttributes, isInSelectedTab ] );

	return (
		<div { ...blockProps }>
			<AttributeControl
				value={ attributeList }
				disabledAttributeIds={ entityAttributes
					.filter( ( attr ) => !! attr.variation )
					.map( ( attr ) => attr.id ) }
				uiStrings={ {
					disabledAttributeMessage: __(
						'Already used in Variations',
						'woocommerce'
					),
				} }
				onAdd={ () => {
					recordEvent(
						'product_add_attributes_modal_add_button_click'
					);
				} }
				onChange={ handleChange }
				onNewModalCancel={ () => {
					recordEvent(
						'product_add_attributes_modal_cancel_button_click'
					);
				} }
				onNewModalOpen={ () => {
					if ( ! attributeList.length ) {
						recordEvent(
							'product_add_first_attribute_button_click'
						);
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
		</div>
	);
}
