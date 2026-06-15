/**
 * External dependencies
 */
import { useEffect } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { BlockEditProps } from '@wordpress/blocks';
import { PanelBody, ToggleControl } from '@wordpress/components';
import {
	BlockControls,
	InspectorControls,
	useBlockProps,
} from '@wordpress/block-editor';
import { getSetting } from '@woocommerce/settings';
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
	const { attributes, setAttributes } = props;
	const { showAddToWishlist } = attributes;
	const isWishlistFeatureEnabled = getSetting< boolean >(
		'wishlistFeatureEnabled',
		false
	);
	const { product } = useProduct( props.context?.postId );
	const blockProps = useBlockProps( {
		className: 'wc-block-add-to-cart-with-options',
	} );
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
				{ isWishlistFeatureEnabled && (
					<PanelBody title={ __( 'Wishlist', 'woocommerce' ) }>
						<ToggleControl
							__nextHasNoMarginBottom
							label={ __( 'Add to wishlist', 'woocommerce' ) }
							help={ __(
								'Show an “Add to wishlist” button as the last item in the add to cart area.',
								'woocommerce'
							) }
							checked={ !! showAddToWishlist }
							onChange={ ( value ) =>
								setAttributes( { showAddToWishlist: value } )
							}
						/>
					</PanelBody>
				) }
			</InspectorControls>
			<BlockControls>
				<ToolbarProductTypeGroup />
			</BlockControls>
			{ isCoreProductType ? (
				<AddToCartWithOptionsEditTemplatePart
					productType={ productType }
					showAddToWishlist={
						isWishlistFeatureEnabled && !! showAddToWishlist
					}
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
