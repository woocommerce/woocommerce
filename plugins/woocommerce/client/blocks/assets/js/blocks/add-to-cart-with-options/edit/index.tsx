/**
 * External dependencies
 */
import { useEffect } from '@wordpress/element';
import { BlockEditProps } from '@wordpress/blocks';
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
import { UpgradeProductImageGallery } from '../components/upgrade-product-image-gallery';
import { useProductTypeSelector } from '../../../shared/stores/product-type-template-state';
import { AddToCartWithOptionsEditTemplatePart } from './edit-template-part';
import { Skeleton } from './skeleton';
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
				<UpgradeProductImageGallery />
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
					<Skeleton
						buttonText={ product?.add_to_cart?.single_text }
						productType={ productType }
						isLoading={ false }
					/>
				</div>
			) }
		</>
	);
};

export default AddToCartOptionsEdit;
