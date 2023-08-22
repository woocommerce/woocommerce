/**
 * External dependencies
 */
import {
	InnerBlocks,
	InspectorControls,
	useBlockProps,
} from '@wordpress/block-editor';
import { BlockEditProps, InnerBlockTemplate } from '@wordpress/blocks';
import { useEffect } from '@wordpress/element';

/**
 * Internal dependencies
 */
import {
	moveInnerBlocksToPosition,
	updateGroupBlockType,
	getInnerBlocksLockAttributes,
} from './utils';
import { ProductGalleryThumbnailsBlockSettings } from './inner-blocks/product-gallery-thumbnails/block-settings';
import { ProductGalleryPagerBlockSettings } from './inner-blocks/product-gallery-pager/settings';
import { ProductGalleryBlockSettings } from './block-settings/index';
import type { ProductGalleryAttributes } from './types';

const TEMPLATE: InnerBlockTemplate[] = [
	[
		'core/group',
		{ layout: { type: 'flex' } },
		[
			[
				'woocommerce/product-gallery-thumbnails',
				getInnerBlocksLockAttributes( 'lock' ),
			],
			[
				'woocommerce/product-gallery-large-image',
				getInnerBlocksLockAttributes( 'lock' ),
			],
		],
	],
	[
		'woocommerce/product-gallery-pager',
		getInnerBlocksLockAttributes( 'lock' ),
	],
];

export const Edit = ( {
	clientId,
	attributes,
	setAttributes,
}: BlockEditProps< ProductGalleryAttributes > ) => {
	const blockProps = useBlockProps();

	// Update the Group block type when the thumbnailsPosition attribute changes.
	updateGroupBlockType( attributes, clientId );

	useEffect( () => {
		setAttributes( {
			...attributes,
			productGalleryClientId: clientId,
		} );
		// Move the Thumbnails block to the correct above or below the Large Image based on the thumbnailsPosition attribute.
		moveInnerBlocksToPosition( attributes, clientId );
	}, [ setAttributes, attributes, clientId ] );

	return (
		<div { ...blockProps }>
			<InspectorControls>
				<ProductGalleryPagerBlockSettings
					context={ {
						productGalleryClientId: clientId,
						pagerDisplayMode: attributes.pagerDisplayMode,
					} }
				/>
				<ProductGalleryThumbnailsBlockSettings
					attributes={ attributes }
					setAttributes={ setAttributes }
					context={ {
						productGalleryClientId: clientId,
						thumbnailsPosition: attributes.thumbnailsPosition,
						thumbnailsNumberOfThumbnails:
							attributes.thumbnailsNumberOfThumbnails,
					} }
				/>
			</InspectorControls>
			<InspectorControls>
				<ProductGalleryBlockSettings
					attributes={ attributes }
					setAttributes={ setAttributes }
				/>
			</InspectorControls>
			<InnerBlocks
				allowedBlocks={ [
					'woocommerce/product-gallery-large-image',
					'woocommerce/product-gallery-pager',
					'woocommerce/product-gallery-thumbnails',
				] }
				templateLock={ false }
				template={ TEMPLATE }
			/>
		</div>
	);
};
