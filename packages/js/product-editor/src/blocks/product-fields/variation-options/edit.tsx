/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { BlockAttributes } from '@wordpress/blocks';
import {
	createElement,
	createInterpolateElement,
	useMemo,
} from '@wordpress/element';
import { useWooBlockProps } from '@woocommerce/block-templates';
import {
	Product,
	ProductAttribute,
	useUserPreferences,
} from '@woocommerce/data';
import { recordEvent } from '@woocommerce/tracks';
import { Link } from '@woocommerce/components';
// eslint-disable-next-line @typescript-eslint/ban-ts-comment
// @ts-ignore No types for this exist yet.
// eslint-disable-next-line @woocommerce/dependency-group
import { useEntityProp, useEntityId } from '@wordpress/core-data';

/**
 * Internal dependencies
 */
import { useProductAttributes } from '../../../hooks/use-product-attributes';
import { AttributeControl } from '../../../components/attribute-control';
import { useProductVariationsHelper } from '../../../hooks/use-product-variations-helper';
import { ProductEditorBlockEditProps } from '../../../types';

export function Edit( {
	attributes: blockAttributes,
}: ProductEditorBlockEditProps< BlockAttributes > ) {
	const blockProps = useWooBlockProps( blockAttributes );
	const { generateProductVariations } = useProductVariationsHelper();
	const {
		updateUserPreferences,
		product_block_variable_options_notice_dismissed:
			hasDismissedVariableOptionsNotice,
	} = useUserPreferences();

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
		onChange( values, defaultAttributes ) {
			setEntityAttributes( values );
			setEntityDefaultAttributes( defaultAttributes );
			generateProductVariations( values, defaultAttributes );
		},
	} );

	const localAttributeNames = attributes
		.filter( ( attr ) => attr.id === 0 )
		.map( ( attr ) => attr.name );
	let notice: string | React.ReactElement = '';
	if (
		localAttributeNames.length > 0 &&
		hasDismissedVariableOptionsNotice !== 'yes'
	) {
		notice = createInterpolateElement(
			__(
				'Buyers can’t search or filter by <attributeNames /> to find the variations. Consider adding them again as <globalAttributeLink>global attributes</globalAttributeLink> to make them easier to discover.',
				'woocommerce'
			),
			{
				attributeNames: (
					<span>
						{ localAttributeNames.length === 2
							? localAttributeNames.join(
									__( ' and ', 'woocommerce' )
							  )
							: localAttributeNames.join( ', ' ) }
					</span>
				),
				globalAttributeLink: (
					<Link
						href="https://woo.com/document/variable-product/#add-attributes-to-use-for-variations"
						type="external"
						target="_blank"
					/>
				),
			}
		);
	}

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
				onAdd={ () => {
					recordEvent( 'product_options_modal_add_button_click' );
				} }
				onChange={ handleChange }
				createNewAttributesAsGlobal={ true }
				useRemoveConfirmationModal={ true }
				onNoticeDismiss={ () =>
					updateUserPreferences( {
						product_block_variable_options_notice_dismissed: 'yes',
					} )
				}
				onAddAnother={ () => {
					recordEvent(
						'product_add_options_modal_add_another_option_button_click'
					);
				} }
				onNewModalCancel={ () => {
					recordEvent( 'product_options_modal_cancel_button_click' );
				} }
				onNewModalOpen={ () => {
					recordEvent( 'product_options_add_option' );
				} }
				onRemoveItem={ () => {
					recordEvent(
						'product_add_options_modal_remove_option_button_click'
					);
				} }
				onRemove={ () =>
					recordEvent(
						'product_remove_option_confirmation_confirm_click'
					)
				}
				onRemoveCancel={ () =>
					recordEvent(
						'product_remove_option_confirmation_cancel_click'
					)
				}
				disabledAttributeIds={ entityAttributes
					.filter( ( attr ) => ! attr.variation )
					.map( ( attr ) => attr.id ) }
				termsAutoSelection="all"
				uiStrings={ {
					notice,
					globalAttributeHelperMessage: '',
					customAttributeHelperMessage: '',
					newAttributeModalNotice: '',
					newAttributeModalTitle: __(
						'Add variation options',
						'woocommerce'
					),
					newAttributeModalDescription: __(
						'Select from existing attributes or create new ones to add new variations for your product. You can change the order later.',
						'woocommerce'
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
