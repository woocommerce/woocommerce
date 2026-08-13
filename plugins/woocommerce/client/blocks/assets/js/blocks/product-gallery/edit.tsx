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
	InnerBlockTemplate,
	type BlockInstance,
} from '@wordpress/blocks';
import { useSelect } from '@wordpress/data';
import { withProductDataContext } from '@woocommerce/shared-hocs';
import { useProductDataContext } from '@woocommerce/shared-context';
import { getSetting } from '@woocommerce/settings';
import clsx from 'clsx';

/**
 * Internal dependencies
 */
import { ProductGalleryBlockSettings } from './block-settings/index';
import type { ProductGalleryBlockAttributes } from './types';
import { resolveAspectRatio } from '../../atomic/blocks/product-elements/image/utils';
import type { BlockAttributes as ProductImageBlockAttributes } from '../../atomic/blocks/product-elements/image/types';

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

const PRODUCT_IMAGE_BLOCK_NAME = 'woocommerce/product-image';

type ParsedAspectRatio = {
	width: string;
	height: string;
};

const DEFAULT_ASPECT_RATIO: ParsedAspectRatio = { width: '1', height: '1' };

type ProductImageBlock = BlockInstance<
	Partial< ProductImageBlockAttributes >
>;

const isProductImageBlock = (
	block: BlockInstance
): block is ProductImageBlock => block.name === PRODUCT_IMAGE_BLOCK_NAME;

const findProductImageBlock = (
	blocks: BlockInstance[]
): ProductImageBlock | undefined => {
	for ( const block of blocks ) {
		if ( isProductImageBlock( block ) ) {
			return block;
		}

		const innerProductImageBlock = findProductImageBlock(
			block.innerBlocks
		);
		if ( innerProductImageBlock ) {
			return innerProductImageBlock;
		}
	}

	return undefined;
};

const resolveProductImageAspectRatio = (
	attributes: Partial< ProductImageBlockAttributes > | undefined,
	storeAspectRatio: string | null
): string | undefined => {
	const { style, aspectRatio, imageSizing } = attributes ?? {};

	return resolveAspectRatio(
		style,
		aspectRatio,
		storeAspectRatio,
		imageSizing
	);
};

const parseAspectRatio = (
	aspectRatio: string | undefined
): ParsedAspectRatio => {
	if ( ! aspectRatio ) {
		return DEFAULT_ASPECT_RATIO;
	}

	const ratioParts = aspectRatio
		.split( '/' )
		.map( ( part ) => part.trim() )
		.filter( Boolean );

	if ( ratioParts.length === 0 || ratioParts.length > 2 ) {
		return DEFAULT_ASPECT_RATIO;
	}

	const width = Number( ratioParts[ 0 ] );
	const height = Number( ratioParts[ 1 ] ?? ratioParts[ 0 ] );

	if ( ! Number.isFinite( width ) || ! Number.isFinite( height ) ) {
		return DEFAULT_ASPECT_RATIO;
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
				const productImageBlock = findProductImageBlock(
					select( blockEditorStore ).getBlocks( clientId )
				);

				return resolveProductImageAspectRatio(
					productImageBlock?.attributes,
					storeAspectRatio
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
				'--wc-block-product-gallery-large-image-ratio-width':
					productGalleryAspectRatio.width,
				'--wc-block-product-gallery-large-image-ratio-height':
					productGalleryAspectRatio.height,
			},
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
