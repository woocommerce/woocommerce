/**
 * External dependencies
 */
import classNames from 'classnames';
import { Button } from '@wordpress/components';
import { useWooBlockProps } from '@woocommerce/block-templates';
import { Product, ProductAttribute } from '@woocommerce/data';
import { recordEvent } from '@woocommerce/tracks';
import { createElement, useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import {
	// @ts-expect-error no exported member.
	useInnerBlocksProps,
} from '@wordpress/block-editor';
// eslint-disable-next-line @typescript-eslint/ban-ts-comment
// @ts-ignore No types for this exist yet.
// eslint-disable-next-line @woocommerce/dependency-group
import { useEntityProp, useEntityId } from '@wordpress/core-data';

/**
 * Internal dependencies
 */
import { sanitizeHTML } from '../../../utils/sanitize-html';
import { VariationsBlockAttributes } from './types';
import { EmptyVariationsImage } from './empty-variations-image';
import { NewAttributeModal } from '../../../components/attribute-control/new-attribute-modal';
import {
	EnhancedProductAttribute,
	useProductAttributes,
} from '../../../hooks/use-product-attributes';
import { getAttributeId } from '../../../components/attribute-control/utils';
import { useProductVariationsHelper } from '../../../hooks/use-product-variations-helper';
import { hasAttributesUsedForVariations } from '../../../utils';
import { TRACKS_SOURCE } from '../../../constants';
import { ProductEditorBlockEditProps } from '../../../types';

export function Edit( {
	attributes,
}: ProductEditorBlockEditProps< VariationsBlockAttributes > ) {
	const { description } = attributes;

	const { generateProductVariations } = useProductVariationsHelper();
	const [ isNewModalVisible, setIsNewModalVisible ] = useState( false );
	const [ productAttributes, setProductAttributes ] = useEntityProp<
		Product[ 'attributes' ]
	>( 'postType', 'product', 'attributes' );
	const [ , setDefaultProductAttributes ] = useEntityProp<
		Product[ 'default_attributes' ]
	>( 'postType', 'product', 'default_attributes' );

	const { attributes: variationOptions, handleChange } = useProductAttributes(
		{
			allAttributes: productAttributes,
			isVariationAttributes: true,
			productId: useEntityId( 'postType', 'product' ),
			onChange( values, defaultAttributes ) {
				setProductAttributes( values );
				setDefaultProductAttributes( defaultAttributes );
				generateProductVariations( values, defaultAttributes );
			},
		}
	);

	const hasAttributes = hasAttributesUsedForVariations( productAttributes );

	const blockProps = useWooBlockProps( attributes, {
		className: classNames( {
			'wp-block-woocommerce-product-variations-fields--has-attributes':
				hasAttributes,
		} ),
	} );
	const innerBlockProps = useInnerBlocksProps(
		{
			className:
				'wp-block-woocommerce-product-variations-fields__content',
		},
		{ templateLock: 'all' }
	);

	const openNewModal = () => {
		setIsNewModalVisible( true );
		recordEvent( 'product_options_add_first_option' );
	};

	const closeNewModal = () => {
		setIsNewModalVisible( false );
	};

	const handleAdd = ( newOptions: EnhancedProductAttribute[] ) => {
		const addedAttributesOnly = newOptions.filter(
			( newAttr ) =>
				! variationOptions.some(
					( attr: ProductAttribute ) =>
						getAttributeId( newAttr ) === getAttributeId( attr )
				)
		);
		recordEvent( 'product_options_add', {
			source: TRACKS_SOURCE,
			options: addedAttributesOnly.map( ( attribute ) => ( {
				attribute: attribute.name,
				values: attribute.options,
			} ) ),
		} );

		handleChange( addedAttributesOnly );
		closeNewModal();
	};

	return (
		<div { ...blockProps }>
			<div className="wp-block-woocommerce-product-variations-fields__heading">
				<div className="wp-block-woocommerce-product-variations-fields__heading-image-container">
					<EmptyVariationsImage />
				</div>
				<p
					className="wp-block-woocommerce-product-variations-fields__heading-description"
					dangerouslySetInnerHTML={ sanitizeHTML( description ) }
				/>
				<div className="wp-block-woocommerce-product-variations-fields__heading-actions">
					<Button variant="primary" onClick={ openNewModal }>
						{ __( 'Add variation options', 'woocommerce' ) }
					</Button>
				</div>
			</div>

			<div { ...innerBlockProps } />
			{ isNewModalVisible && (
				<NewAttributeModal
					title={ __( 'Add variation options', 'woocommerce' ) }
					description={ __(
						'Select from existing attributes or create new ones to add new variations for your product. You can change the order later.',
						'woocommerce'
					) }
					createNewAttributesAsGlobal={ true }
					notice={ '' }
					onCancel={ () => {
						recordEvent(
							'product_options_modal_cancel_button_click'
						);
						closeNewModal();
					} }
					onAdd={ handleAdd }
					onAddAnother={ () => {
						recordEvent(
							'product_add_options_modal_add_another_option_button_click'
						);
					} }
					onRemoveItem={ () => {
						recordEvent(
							'product_add_options_modal_remove_option_button_click'
						);
					} }
					selectedAttributeIds={ variationOptions.map(
						( attr ) => attr.id
					) }
					disabledAttributeIds={ productAttributes
						.filter( ( attr ) => ! attr.variation )
						.map( ( attr ) => attr.id ) }
					termsAutoSelection="all"
				/>
			) }
		</div>
	);
}
