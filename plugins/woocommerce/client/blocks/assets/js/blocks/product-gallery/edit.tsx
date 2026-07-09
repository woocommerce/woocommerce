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
import { useProductDataContext } from '@woocommerce/shared-context';
import clsx from 'clsx';

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
		context,
	}: BlockEditProps< ProductGalleryBlockAttributes > & {
		context?: {
			postId?: number | string;
		};
	} ) => {
		const { product, isLoading } = useProductDataContext();
		const productImages = product?.images || [];
		const hasProductContext = Boolean( context?.postId && product?.id );
		const hasOneOrNoImages =
			hasProductContext && ! isLoading && productImages.length <= 1;
		const blockProps = useBlockProps( {
			className: clsx( 'wc-block-product-gallery', {
				'wc-block-product-gallery--has-one-or-no-images':
					hasOneOrNoImages,
			} ),
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
