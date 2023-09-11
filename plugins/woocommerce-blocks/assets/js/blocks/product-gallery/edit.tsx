/**
 * External dependencies
 */
import {
	InnerBlocks,
	InspectorControls,
	useBlockProps,
	useInnerBlocksProps,
} from '@wordpress/block-editor';
import { BlockEditProps, InnerBlockTemplate } from '@wordpress/blocks';
import { useEffect } from '@wordpress/element';

/**
 * Internal dependencies
 */
import {
	moveInnerBlocksToPosition,
	getInnerBlocksLockAttributes,
} from './utils';
import { ProductGalleryThumbnailsBlockSettings } from './inner-blocks/product-gallery-thumbnails/block-settings';
import { ProductGalleryPagerBlockSettings } from './inner-blocks/product-gallery-pager/settings';
import { ProductGalleryBlockSettings } from './block-settings/index';
import type { ProductGalleryAttributes } from './types';
import { ProductGalleryNextPreviousBlockSettings } from './inner-blocks/product-gallery-large-image-next-previous/settings';

const TEMPLATE: InnerBlockTemplate[] = [
	[
		'core/group',
		{ layout: { type: 'flex', flexWrap: 'nowrap' } },
		[
			[
				'woocommerce/product-gallery-thumbnails',
				getInnerBlocksLockAttributes( 'lock' ),
			],
			[
				'core/group',
				{
					layout: {
						type: 'flex',
						orientation: 'vertical',
						justifyContent: 'center',
					},
					...getInnerBlocksLockAttributes( 'lock' ),
				},
				[
					[
						'woocommerce/product-gallery-large-image',
						getInnerBlocksLockAttributes( 'lock' ),
						[
							[
								'woocommerce/product-sale-badge',
								{
									align: 'right',
									style: {
										spacing: {
											margin: {
												top: '4px',
												right: '4px',
												bottom: '4px',
												left: '4px',
											},
										},
									},
									lock: { move: true },
								},
							],
							[
								'woocommerce/product-gallery-large-image-next-previous',
								{
									layout: {
										type: 'flex',
										verticalAlignment: 'bottom',
									},
									lock: { move: true, remove: true },
								},
							],
						],
					],
					[
						'woocommerce/product-gallery-pager',
						{ lock: { move: true, remove: true } },
					],
				],
			],
		],
	],
];

export const Edit = ( {
	clientId,
	attributes,
	setAttributes,
}: BlockEditProps< ProductGalleryAttributes > ) => {
	const blockProps = useBlockProps();

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
			<InspectorControls>
				<ProductGalleryNextPreviousBlockSettings
					context={ {
						...attributes,
						productGalleryClientId: clientId,
					} }
				/>
			</InspectorControls>
			<InnerBlocks
				allowedBlocks={ [
					'woocommerce/product-gallery-large-image',
					'woocommerce/product-gallery-thumbnails',
				] }
				templateLock={ false }
				template={ TEMPLATE }
			/>
		</div>
	);
};

export const Save = () => {
	const blockProps = useBlockProps.save();
	const innerBlocksProps = useInnerBlocksProps.save( blockProps );
	return <div { ...innerBlocksProps } />;
};
