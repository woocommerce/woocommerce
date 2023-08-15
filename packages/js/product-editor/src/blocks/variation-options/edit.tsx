/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useBlockProps } from '@wordpress/block-editor';
import {
	createElement,
	createInterpolateElement,
	useMemo,
} from '@wordpress/element';
import { Product, ProductAttribute } from '@woocommerce/data';
import { Link } from '@woocommerce/components';
// eslint-disable-next-line @typescript-eslint/ban-ts-comment
// @ts-ignore No types for this exist yet.
// eslint-disable-next-line @woocommerce/dependency-group
import { useEntityProp, useEntityId } from '@wordpress/core-data';

/**
 * Internal dependencies
 */
import {
	EnhancedProductAttribute,
	useProductAttributes,
} from '../../hooks/use-product-attributes';
import { AttributeControl } from '../../components/attribute-control';
import { useProductVariationsHelper } from '../../hooks/use-product-variations-helper';

function manageDefaultAttributes( values: EnhancedProductAttribute[] ) {
	return values.reduce< Product[ 'default_attributes' ] >(
		( prevDefaultAttributes, currentAttribute ) => {
			if ( currentAttribute.isDefault ) {
				return [
					...prevDefaultAttributes,
					{
						id: currentAttribute.id,
						name: currentAttribute.name,
						option: currentAttribute.options[ 0 ],
					},
				];
			}
			return prevDefaultAttributes;
		},
		[]
	);
}

export function Edit() {
	const blockProps = useBlockProps();
	const { generateProductVariations } = useProductVariationsHelper();

	const [ entityAttributes, setEntityAttributes ] = useEntityProp<
		ProductAttribute[]
	>( 'postType', 'product', 'attributes' );

	const [ entityDefaultAttributes, setEntityDefaultAttributes ] =
		useEntityProp< Product[ 'default_attributes' ] >(
			'postType',
			'product',
			'default_attributes'
		);

	const { attributes, handleChange } = useProductAttributes( {
		allAttributes: entityAttributes,
		isVariationAttributes: true,
		productId: useEntityId( 'postType', 'product' ),
		onChange( values ) {
			setEntityAttributes( values );
			setEntityDefaultAttributes( manageDefaultAttributes( values ) );
			generateProductVariations( values );
		},
	} );

	function mapDefaultAttributes() {
		return attributes.map( ( attribute ) => ( {
			...attribute,
			isDefault: entityDefaultAttributes.some(
				( defaultAttribute ) =>
					defaultAttribute.id === attribute.id ||
					defaultAttribute.name === attribute.name
			),
		} ) );
	}

	return (
		<div { ...blockProps }>
			<AttributeControl
				value={ useMemo( mapDefaultAttributes, [
					attributes,
					entityDefaultAttributes,
				] ) }
				onChange={ handleChange }
				createNewAttributesAsGlobal={ true }
				useRemoveConfirmationModal={ true }
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
					attributeRemoveConfirmationModalMessage: __(
						'If you continue, some variations of this product will be deleted and customers will no longer be able to purchase them.',
						'woocommerce'
					),
				} }
			/>
		</div>
	);
}
