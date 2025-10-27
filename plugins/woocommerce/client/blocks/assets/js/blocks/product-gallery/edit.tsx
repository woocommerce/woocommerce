/**
 * External dependencies
 */
import {
	InnerBlocks,
	InspectorControls,
	useBlockProps,
} from '@wordpress/block-editor';
import { BlockEditProps, InnerBlockTemplate } from '@wordpress/blocks';
import { withProductDataContext } from '@woocommerce/shared-hocs';

/**
 * Internal dependencies
 */
import { ProductGalleryBlockSettings } from './block-settings/index';
import type { ProductGalleryBlockAttributes } from './types';

const TEMPLATE: InnerBlockTemplate[] = [
	[ 'woocommerce/product-gallery-thumbnails' ],
	[
		'woocommerce/product-gallery-large-image',
		{},
		[
			[
				'woocommerce/product-image',
				{
					showProductLink: false,
					showSaleBadge: false,
				},
			],
			[
				'woocommerce/product-sale-badge',
				{
					align: 'right',
				},
			],
			[ 'woocommerce/product-gallery-large-image-next-previous' ],
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
					template={ TEMPLATE }
				/>
			</div>
		);
	}
);
