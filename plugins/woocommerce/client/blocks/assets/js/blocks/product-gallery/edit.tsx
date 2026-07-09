/**
 * External dependencies
 */
import {
	InnerBlocks,
	InspectorControls,
	store as blockEditorStore,
	useBlockProps,
} from '@wordpress/block-editor';
import {
	BlockEditProps,
	BlockInstance,
	InnerBlockTemplate,
} from '@wordpress/blocks';
import { useSelect } from '@wordpress/data';
import { withProductDataContext } from '@woocommerce/shared-hocs';
import { useProductDataContext } from '@woocommerce/shared-context';
import { getSetting } from '@woocommerce/settings';
import { findBlock } from '@woocommerce/utils';
import clsx from 'clsx';
import type { CSSProperties } from 'react';

/**
 * Internal dependencies
 */
import { ProductGalleryBlockSettings } from './block-settings/index';
import type { ProductGalleryBlockAttributes } from './types';
import { resolveAspectRatio } from '../../atomic/blocks/product-elements/image/utils';
import type {
	AspectRatioStyle,
	BlockAttributes as ProductImageBlockAttributes,
	ImageSizing,
} from '../../atomic/blocks/product-elements/image/types';

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

const parseAspectRatio = ( aspectRatio: string | undefined ) => {
	if ( ! aspectRatio ) {
		return { width: '1', height: '1' };
	}

	const ratioParts = aspectRatio
		.split( '/' )
		.map( ( part ) => part.trim() )
		.filter( Boolean );

	if ( ratioParts.length > 2 ) {
		return { width: '1', height: '1' };
	}

	const width = Number( ratioParts[ 0 ] );
	const height = Number( ratioParts[ 1 ] ?? ratioParts[ 0 ] );

	if (
		! Number.isFinite( width ) ||
		! Number.isFinite( height ) ||
		width <= 0 ||
		height <= 0
	) {
		return { width: '1', height: '1' };
	}

	return {
		width: String( width ),
		height: String( height ),
	};
};

export const Edit = withProductDataContext(
	( {
		clientId,
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
		const storeAspectRatio = getSetting< string | null >(
			'thumbnailAspectRatio',
			null
		);
		const productImageAspectRatio = useSelect(
			( select ) => {
				const galleryBlock = select( blockEditorStore ).getBlock(
					clientId
				) as BlockInstance | null;
				const productImageBlock = findBlock( {
					blocks: galleryBlock?.innerBlocks ?? [],
					findCondition: ( block: BlockInstance ) =>
						block.name === 'woocommerce/product-image',
				} );
				const productImageAttributes = productImageBlock?.attributes as
					| Partial< ProductImageBlockAttributes >
					| undefined;

				return resolveAspectRatio(
					productImageAttributes?.style as
						| AspectRatioStyle
						| undefined,
					productImageAttributes?.aspectRatio,
					storeAspectRatio,
					productImageAttributes?.imageSizing as
						| ImageSizing
						| undefined
				);
			},
			[ clientId, storeAspectRatio ]
		);
		const productGalleryAspectRatio = parseAspectRatio(
			productImageAspectRatio
		);
		const blockProps = useBlockProps( {
			className: clsx( 'wc-block-product-gallery', {
				'wc-block-product-gallery--has-one-or-no-images':
					hasOneOrNoImages,
			} ),
			style: {
				'--wc-block-product-gallery-image-ratio-width':
					productGalleryAspectRatio.width,
				'--wc-block-product-gallery-image-ratio-height':
					productGalleryAspectRatio.height,
			} as CSSProperties,
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
