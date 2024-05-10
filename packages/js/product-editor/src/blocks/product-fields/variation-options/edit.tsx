/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { BlockAttributes } from '@wordpress/blocks';
import { Button } from '@wordpress/components';
import {
	createElement,
	createInterpolateElement,
	useEffect,
	useMemo,
} from '@wordpress/element';
import { useWooBlockProps } from '@woocommerce/block-templates';
import {
	Product,
	ProductProductAttribute,
	useUserPreferences,
} from '@woocommerce/data';
import { recordEvent } from '@woocommerce/tracks';
import { Link } from '@woocommerce/components';
import { getAdminLink } from '@woocommerce/settings';
// eslint-disable-next-line @typescript-eslint/ban-ts-comment
// @ts-ignore No types for this exist yet.
// eslint-disable-next-line @woocommerce/dependency-group
import { useEntityProp, useEntityId } from '@wordpress/core-data';

/**
 * Internal dependencies
 */
import { useProductAttributes } from '../../../hooks/use-product-attributes';
import {
	AttributeControl,
	AttributeControlEmptyStateProps,
} from '../../../components/attribute-control';
import { useProductVariationsHelper } from '../../../hooks/use-product-variations-helper';
import { ProductEditorBlockEditProps } from '../../../types';
import { ProductTShirt } from './images';

export function Edit( {
	attributes: blockAttributes,
	context: { postType, isInSelectedTab },
}: ProductEditorBlockEditProps< BlockAttributes > ) {
	const blockProps = useWooBlockProps( blockAttributes );
	const { generateProductVariations } = useProductVariationsHelper();
	const {
		updateUserPreferences,
		local_attributes_notice_dismissed_ids: dismissedNoticesIds = [],
	} = useUserPreferences();

	const [ entityAttributes, setEntityAttributes ] = useEntityProp<
		ProductProductAttribute[]
	>( 'postType', 'product', 'attributes' );

	const [ entityDefaultAttributes, setEntityDefaultAttributes ] =
		useEntityProp< Product[ 'default_attributes' ] >(
			'postType',
			'product',
			'default_attributes'
		);

	const productId = useEntityId( 'postType', postType );

	const { attributes, fetchAttributes, handleChange } = useProductAttributes(
		{
			allAttributes: entityAttributes,
			isVariationAttributes: true,
			productId: useEntityId( 'postType', 'product' ),
			onChange( values, defaultAttributes ) {
				setEntityAttributes( values );
				setEntityDefaultAttributes( defaultAttributes );
				generateProductVariations( values, defaultAttributes );
			},
		}
	);

	useEffect( () => {
		if ( isInSelectedTab ) {
			fetchAttributes();
		}
	}, [ entityAttributes, isInSelectedTab ] );

	const localAttributeNames = attributes
		.filter( ( attr ) => attr.id === 0 )
		.map( ( attr ) => attr.name );
	let notice: string | React.ReactElement = '';
	if (
		localAttributeNames.length > 0 &&
		! dismissedNoticesIds?.includes( productId )
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
						href={ getAdminLink(
							'edit.php?post_type=product&page=product_attributes'
						) }
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

	function renderCustomEmptyState( {
		addAttribute,
	}: AttributeControlEmptyStateProps ) {
		return (
			<div className="wp-block-woocommerce-product-variations-options-field__empty-state">
				<div className="wp-block-woocommerce-product-variations-options-field__empty-state-image">
					<ProductTShirt className="wp-block-woocommerce-product-variations-options-field__empty-state-image-product" />
					<ProductTShirt className="wp-block-woocommerce-product-variations-options-field__empty-state-image-product" />
					<ProductTShirt className="wp-block-woocommerce-product-variations-options-field__empty-state-image-product" />
				</div>

				<p className="wp-block-woocommerce-product-variations-options-field__empty-state-description">
					{ __(
						'Sell your product in multiple variations like size or color.',
						'woocommerce'
					) }
				</p>

				<div className="wp-block-woocommerce-product-variations-options-field__empty-state-actions">
					<Button variant="primary" onClick={ () => addAttribute() }>
						{ __( 'Add options', 'woocommerce' ) }
					</Button>
					<Button
						variant="secondary"
						onClick={ () =>
							addAttribute( __( 'Size', 'woocommerce' ) )
						}
					>
						{ __( 'Add sizes', 'woocommerce' ) }
					</Button>
					<Button
						variant="secondary"
						onClick={ () =>
							addAttribute( __( 'Color', 'woocommerce' ) )
						}
					>
						{ __( 'Add colors', 'woocommerce' ) }
					</Button>
				</div>
			</div>
		);
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
						local_attributes_notice_dismissed_ids: [
							...dismissedNoticesIds,
							productId,
						],
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
				renderCustomEmptyState={ renderCustomEmptyState }
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
