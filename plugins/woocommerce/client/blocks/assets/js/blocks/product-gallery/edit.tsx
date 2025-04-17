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
import { withProductDataContext } from '@woocommerce/shared-hocs';

/**
 * Internal dependencies
 */
import { ProductGalleryBlockSettings } from './block-settings/index';
import type { ProductGalleryBlockAttributes } from './types';

const TEMPLATE: InnerBlockTemplate[] = [
	[
		'core/group',
		{
			layout: {
				type: 'flex',
				flexWrap: 'nowrap',
				verticalAlignment: 'top',
			},
			metadata: {
				name: 'Gallery Area',
			},
			className: 'wc-block-product-gallery__gallery-area',
		},
		[
			[ 'woocommerce/product-gallery-thumbnails' ],
			[
				'woocommerce/product-gallery-large-image',
				{},
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
						},
					],
					[
						'woocommerce/product-gallery-large-image-next-previous',
						{
							style: {
								border: { radius: '100%' },
							},
						},
					],
				],
			],
		],
	],
];

export const Edit = withProductDataContext(
	( {
		attributes,
		setAttributes,
	}: BlockEditProps< ProductGalleryBlockAttributes > ) => {
		const blockProps = useBlockProps( {
			className: 'wc-block-product-gallery',
		} );

		return (
			<div { ...blockProps }>
				<InspectorControls>
					<ProductGalleryBlockSettings
						attributes={ attributes }
						setAttributes={ setAttributes }
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
	}
);

export const Save = () => {
	const blockProps = useBlockProps.save();
	const innerBlocksProps = useInnerBlocksProps.save( blockProps );
	return <div { ...innerBlocksProps } />;
};
