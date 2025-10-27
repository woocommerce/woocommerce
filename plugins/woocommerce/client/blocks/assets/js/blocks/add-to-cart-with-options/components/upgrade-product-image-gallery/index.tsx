/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useState, useEffect } from '@wordpress/element';
import { BlockInstance } from '@wordpress/blocks';
import { select } from '@wordpress/data';
import { UpgradeDowngradeNotice } from '@woocommerce/editor-components/upgrade-downgrade-notice';
import { findBlock } from '@woocommerce/utils';

/**
 * Internal dependencies
 */
import { replaceBlockWithProductGallery } from '../../../product-gallery/edit-utils';

export const UpgradeProductImageGallery = () => {
	const [ productImageGalleryBlock, setProductImageGalleryBlock ] =
		useState< BlockInstance | null >( null );

	useEffect( () => {
		const foundBlock = findBlock( {
			blocks: select( 'core/block-editor' ).getBlocks(),
			findCondition: ( block ) =>
				block.name === 'woocommerce/product-image-gallery',
		} );
		if ( foundBlock ) {
			setProductImageGalleryBlock( foundBlock );
		} else {
			setProductImageGalleryBlock( null );
		}
	}, [ setProductImageGalleryBlock ] );

	if ( ! productImageGalleryBlock ) {
		return null;
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
				replaceBlockWithProductGallery(
					productImageGalleryBlock.clientId
				);
				setProductImageGalleryBlock( null );
			} }
		>
			{ notice }
		</UpgradeDowngradeNotice>
	);
};
