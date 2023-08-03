/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useBlockProps } from '@wordpress/block-editor';
import { createElement, createInterpolateElement } from '@wordpress/element';
import { ProductAttribute } from '@woocommerce/data';
import { Link } from '@woocommerce/components';
// eslint-disable-next-line @typescript-eslint/ban-ts-comment
// @ts-ignore No types for this exist yet.
// eslint-disable-next-line @woocommerce/dependency-group
import { useEntityProp, useEntityId } from '@wordpress/core-data';

/**
 * Internal dependencies
 */
import { useProductAttributes } from '../../hooks/use-product-attributes';
import { AttributeControl } from '../../components/attribute-control';

export function Edit() {
	const blockProps = useBlockProps();

	const [ entityAttributes, setEntityAttributes ] = useEntityProp<
		ProductAttribute[]
	>( 'postType', 'product', 'attributes' );

	const { attributes, handleChange } = useProductAttributes( {
		allAttributes: entityAttributes,
		onChange: setEntityAttributes,
		isVariationAttributes: true,
		productId: useEntityId( 'postType', 'product' ),
	} );

	return (
		<div { ...blockProps }>
			<AttributeControl
				value={ attributes }
				onChange={ handleChange }
				uiStrings={ {
					globalAttributeHelperMessage: '',
					customAttributeHelperMessage: '',
					newAttributeModalNotice: '',
					newAttributeModalTitle: __(
						'Add variation options',
						'woocommerce'
					),
					newAttributeModalDescription: createInterpolateElement(
						__(
							'Select from existing <globalAttributeLink>global attributes</globalAttributeLink> or create options for buyers to choose on the product page. You can change the order later.',
							'woocommerce'
						),
						{
							globalAttributeLink: (
								<Link
									href="https://woocommerce.com/document/variable-product/#add-attributes-to-use-for-variations"
									type="external"
									target="_blank"
								/>
							),
						}
					),
					attributeRemoveLabel: __(
						'Remove variation option',
						'woocommerce'
					),
					attributeRemoveConfirmationMessage: __(
						'Remove this variation option?',
						'woocommerce'
					),
				} }
			/>
		</div>
	);
}
