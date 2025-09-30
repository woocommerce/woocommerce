/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useState, useEffect } from '@wordpress/element';
import { createBlock, BlockInstance } from '@wordpress/blocks';
import { dispatch, useSelect } from '@wordpress/data';
import { UpgradeDowngradeNotice } from '@woocommerce/editor-components/upgrade-downgrade-notice';
import { findBlock } from '@woocommerce/utils';

const upgradeToBlockifiedProductGallery = (
	productImageGalleryBlockClientId: string
) => {
	const newBlock = createBlock( 'woocommerce/product-gallery' );

	dispatch( 'core/block-editor' ).replaceBlock(
		productImageGalleryBlockClientId,
		newBlock
	);

	return true;
};

export const UpgradeProductImageGallery = () => {
	const [ productImageGalleryBlock, setProductImageGalleryBlock ] =
		useState< BlockInstance | null >( null );

	const getBlocks = useSelect( ( select ) => {
		return select( 'core/block-editor' ).getBlocks;
	}, [] );

	useEffect( () => {
		const foundBlock = findBlock( {
			blocks: getBlocks(),
			findCondition: ( block ) =>
				block.name === 'woocommerce/product-image-gallery',
		} );
		if ( foundBlock ) {
			setProductImageGalleryBlock( foundBlock );
		} else {
			setProductImageGalleryBlock( null );
		}
	}, [ getBlocks, setProductImageGalleryBlock ] );

	if ( ! productImageGalleryBlock ) {
		return false;
	}

	const notice = __(
		'This template contains the classic Product Image Gallery block which is not compatible with the Add to Cart + Options block. Switch to the new Product Gallery block for a better experience.',
		'woocommerce'
	);

	const buttonLabel = __(
		'Upgrade to the blockified Product Gallery',
		'woocommerce'
	);

	return (
		<UpgradeDowngradeNotice
			isDismissible={ false }
			actionLabel={ buttonLabel }
			onActionClick={ () => {
				upgradeToBlockifiedProductGallery(
					productImageGalleryBlock.clientId
				);
				setProductImageGalleryBlock( null );
			} }
		>
			{ notice }
		</UpgradeDowngradeNotice>
	);
};
