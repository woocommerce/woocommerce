/**
 * External dependencies
 */
import clsx from 'clsx';
import { InspectorControls, useBlockProps } from '@wordpress/block-editor';
import { PanelBody } from '@wordpress/components';
import { WC_BLOCKS_IMAGE_URL } from '@woocommerce/block-settings';
import { useProductDataContext } from '@woocommerce/shared-context';
import { useRef, useState, useEffect } from '@wordpress/element';
import type { ProductResponseImageItem } from '@woocommerce/types';
import type { BlockEditProps } from '@wordpress/blocks';

/**
 * Internal dependencies
 */
import { ProductGalleryThumbnailsBlockSettings } from './block-settings';
import { checkOverflow } from '../../utils';
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
}: BlockEditProps< ProductGalleryThumbnailsBlockAttributes > ) => {
	const { thumbnailSize } = attributes;

	const placeholderSrc = `${ WC_BLOCKS_IMAGE_URL }block-placeholders/product-image-gallery.svg`;
	const productContext = useProductDataContext();
	const product = productContext?.product;

	// If the product is not loaded, the default product object is returned.
	// That's why we're checking if product id is truthy as by default it's 0.
	const isProductContext = Boolean( product?.id );
	const productThumbnails = isProductContext
		? prepareProductImages( product?.images )
		: Array( MAX_THUMBNAILS ).fill( {
				src: placeholderSrc,
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
	const className = clsx(
		'wc-block-product-gallery-thumbnails',
		`wc-block-product-gallery-thumbnails--thumbnails-size-${ thumbnailSizeValue }`,
		{
			'wc-block-product-gallery-thumbnails--overflow-right':
				overflowState.right,
			'wc-block-product-gallery-thumbnails--overflow-bottom':
				overflowState.bottom,
		}
	);
	const blockProps = useBlockProps( { className } );

	return (
		<>
			<InspectorControls>
				<PanelBody>
					<ProductGalleryThumbnailsBlockSettings
						attributes={ attributes }
						setAttributes={ setAttributes }
					/>
				</PanelBody>
			</InspectorControls>
			{ renderThumbnails && (
				<div { ...blockProps }>
					<div
						ref={ scrollableRef }
						className="wc-block-product-gallery-thumbnails__scrollable"
					>
						{ productThumbnails.map( ( { src, alt }, index ) => {
							const thumbnailClassName = clsx(
								'wc-block-product-gallery-thumbnails__thumbnail',
								{
									'wc-block-product-gallery-thumbnails__thumbnail--active':
										index === 0,
								}
							);
							return (
								<div
									className={ thumbnailClassName }
									key={ index }
								>
									<img
										className="wc-block-product-gallery-thumbnails__thumbnail__image"
										src={ src }
										alt={ alt }
										loading="lazy"
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
