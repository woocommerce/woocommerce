/**
 * External dependencies
 */
import { PLACEHOLDER_IMG_SRC } from '@woocommerce/settings';
import type { ProductResponseImageItem } from '@woocommerce/types';
import { InspectorControls, useBlockProps } from '@wordpress/block-editor';
import type { BlockEditProps } from '@wordpress/blocks';
import { useEffect, useRef, useState } from '@wordpress/element';
import { useProduct } from '@woocommerce/entities';
import clsx from 'clsx';

/**
 * Internal dependencies
 */
import { checkOverflow } from '../../utils';
import { ProductGalleryThumbnailsBlockSettings } from './block-settings';
import type { ProductGalleryThumbnailsBlockAttributes } from './types';

const MAX_THUMBNAILS = 10;

/**
 * Prepares product images for display in the gallery thumbnails.
 * Limits the number of images to MAX_THUMBNAILS - no need to load more in editor.
 * Also, extracts src and alt properties from the image object.
 *
 * @param {ProductResponseImageItem[]} productImages - Array of product images from the API response.
 * @return {{ src: string | undefined; alt: string | undefined }[]} Array of prepared image objects containing src and alt properties.
 */
const prepareProductImages = (
	productImages: ProductResponseImageItem[]
): { src: string | undefined; alt: string | undefined }[] => {
	return productImages.slice( 0, MAX_THUMBNAILS ).map( ( image ) => {
		return {
			src: image?.src,
			alt: image?.alt,
		};
	} );
};
export const Edit = ( {
	attributes,
	setAttributes,
	context,
}: BlockEditProps< ProductGalleryThumbnailsBlockAttributes > & {
	context: {
		postId?: string;
	};
} ) => {
	const { thumbnailSize, aspectRatio } = attributes;

	const { product } = useProduct( context.postId );

	const productThumbnails =
		product && product.images && product.images.length > 0
			? prepareProductImages( product.images )
			: Array( MAX_THUMBNAILS ).fill( {
					src: PLACEHOLDER_IMG_SRC,
					alt: '',
			  } );

	const renderThumbnails = productThumbnails.length > 1;

	const scrollableRef = useRef< HTMLDivElement >( null );
	const [ overflowState, setOverflowState ] = useState( {
		bottom: false,
		right: false,
	} );

	useEffect( () => {
		const scrollableElement = scrollableRef.current;
		if ( ! scrollableElement ) {
			return;
		}

		// Create a ResizeObserver to watch for layout changes
		const resizeObserver = new ResizeObserver( () => {
			const overflow = checkOverflow( scrollableElement );
			setOverflowState( overflow );
		} );

		// Observe both the scrollable element and its parent for size changes
		resizeObserver.observe( scrollableElement );
		if ( scrollableElement.parentElement ) {
			resizeObserver.observe( scrollableElement.parentElement );
		}

		return () => {
			resizeObserver.disconnect();
		};
	}, [ thumbnailSize ] );

	const thumbnailSizeValue = Number( thumbnailSize.replace( '%', '' ) );
	const className = clsx( 'wc-block-product-gallery-thumbnails', {
		'wc-block-product-gallery-thumbnails--overflow-right':
			overflowState.right,
		'wc-block-product-gallery-thumbnails--overflow-bottom':
			overflowState.bottom,
	} );
	const blockProps = useBlockProps( {
		className,
		style: {
			'--wc-block-product-gallery-thumbnails-size': thumbnailSizeValue,
		},
	} );
	const imageStyles: Record< string, string | undefined > = {
		aspectRatio,
	};

	return (
		<>
			<InspectorControls>
				<ProductGalleryThumbnailsBlockSettings
					attributes={ attributes }
					setAttributes={ setAttributes }
				/>
			</InspectorControls>
			{ renderThumbnails && (
				<div { ...blockProps }>
					<div
						ref={ scrollableRef }
						className="wc-block-product-gallery-thumbnails__scrollable"
					>
						{ productThumbnails.map( ( { src, alt }, index ) => {
							const imageClassName = clsx(
								'wc-block-product-gallery-thumbnails__thumbnail__image',
								{
									'wc-block-product-gallery-thumbnails__thumbnail__image--is-active':
										index === 0,
								}
							);
							return (
								<div
									className="wc-block-product-gallery-thumbnails__thumbnail"
									key={ index }
								>
									<img
										className={ imageClassName }
										src={ src }
										alt={ alt }
										loading="lazy"
										style={ imageStyles }
									/>
								</div>
							);
						} ) }
					</div>
				</div>
			) }
		</>
	);
};
