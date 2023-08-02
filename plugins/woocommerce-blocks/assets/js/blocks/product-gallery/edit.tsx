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
import { BlockSettings } from './inner-blocks/product-gallery-thumbnails/block-settings';
import type { BlockAttributes } from './types';

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
];

export const Edit = ( {
	clientId,
	attributes,
	setAttributes,
}: BlockEditProps< BlockAttributes > ) => {
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
				<BlockSettings
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
			<InnerBlocks
				allowedBlocks={ [
					'woocommerce/product-gallery-large-image',
					'woocommerce/product-gallery-thumbnails',
				] }
				template={ TEMPLATE }
			/>
		</div>
	);
};
