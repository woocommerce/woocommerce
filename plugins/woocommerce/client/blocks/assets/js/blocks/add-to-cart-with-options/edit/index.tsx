/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useEffect } from '@wordpress/element';
import { BlockEditProps } from '@wordpress/blocks';
import { Disabled } from '@wordpress/components';
import { ProductShortDescriptionSkeleton } from '@woocommerce/base-components/skeleton/patterns/product-short-description';
import {
	BlockControls,
	InspectorControls,
	useBlockProps,
} from '@wordpress/block-editor';
import { useProduct } from '@woocommerce/entities';

/**
 * Internal dependencies
 */
import ToolbarProductTypeGroup from '../components/toolbar-type-product-selector-group';
import { DowngradeNotice } from '../components/downgrade-notice';
import { useProductTypeSelector } from '../../../shared/stores/product-type-template-state';
import { AddToCartWithOptionsEditTemplatePart } from './edit-template-part';
import type { Attributes } from '../types';

const AddToCartOptionsEdit = (
	props: BlockEditProps< Attributes > & { context?: { postId?: number } }
) => {
	const { product } = useProduct( props.context?.postId );
	const blockProps = useBlockProps();
	const blockClientId = blockProps?.id;

	const {
		current: currentProductType,
		registerListener,
		unregisterListener,
	} = useProductTypeSelector();

	useEffect( () => {
		registerListener( blockClientId );
		return () => {
			unregisterListener( blockClientId );
		};
	}, [ blockClientId, registerListener, unregisterListener ] );

	const productType =
		product?.id === undefined ? currentProductType?.slug : product?.type;
	const isCoreProductType =
		productType &&
		[ 'simple', 'variable', 'external', 'grouped' ].includes( productType );

	return (
		<>
			<InspectorControls>
				<DowngradeNotice blockClientId={ props?.clientId } />
			</InspectorControls>
			<BlockControls>
				<ToolbarProductTypeGroup />
			</BlockControls>
			{ isCoreProductType ? (
				<AddToCartWithOptionsEditTemplatePart
					productType={ productType }
				/>
			) : (
				<div { ...blockProps }>
					<div className="wp-block-woocommerce-add-to-cart-with-options__skeleton-wrapper">
						<ProductShortDescriptionSkeleton />
					</div>
					<Disabled>
						<button
							className={ `alt wp-element-button ${ productType }_add_to_cart_button` }
						>
							{ ( product && product.add_to_cart?.single_text ) ||
								__( 'Add to cart', 'woocommerce' ) }
						</button>
					</Disabled>
				</div>
			) }
		</>
	);
};

export default AddToCartOptionsEdit;
