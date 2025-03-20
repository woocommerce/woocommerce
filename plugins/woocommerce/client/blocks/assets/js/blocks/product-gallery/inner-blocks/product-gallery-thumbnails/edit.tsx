/**
 * External dependencies
 */
import clsx from 'clsx';
import { InspectorControls, useBlockProps } from '@wordpress/block-editor';
import { PanelBody } from '@wordpress/components';
import { WC_BLOCKS_IMAGE_URL } from '@woocommerce/block-settings';
import { withProductDataContext } from '@woocommerce/shared-hocs';
import { useProductDataContext } from '@woocommerce/shared-context';
import type { BlockEditProps } from '@wordpress/blocks';
import { useRef, useState, useEffect } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { ProductGalleryThumbnailsBlockSettings } from './block-settings';
import { checkOverflow } from '../../utils';
import type { ProductGalleryThumbnailsBlockAttributes } from './types';

export const Edit = withProductDataContext(
	( {
		attributes,
		setAttributes,
	}: BlockEditProps< ProductGalleryThumbnailsBlockAttributes > ) => {
		const { thumbnailSize } = attributes;

		const maxThumbnails = 10;
		const placeholderSrc = `${ WC_BLOCKS_IMAGE_URL }block-placeholders/product-image-gallery.svg`;
		const productContext = useProductDataContext();
		const productImages =
			productContext?.product?.images ||
			Array( maxThumbnails ).fill( null );
		const productThumbnails = productImages
			?.slice( 0, maxThumbnails )
			.map( ( image ) => {
				return {
					src: image?.src || placeholderSrc,
					alt: image?.alt || '',
				};
			} );
		const renderThumbnails = productThumbnails.length > 1;

		const scrollableRef = useRef< HTMLDivElement >( null );
		const [ overflowState, setOverflowState ] = useState( {
			overflowBottom: false,
			overflowRight: false,
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

			// Initial check
			const overflow = checkOverflow( scrollableElement );
			setOverflowState( overflow );

			return () => {
				resizeObserver.disconnect();
			};
		}, [ thumbnailSize ] ); // Re-run when thumbnailSize changes as it affects layout

		const thumbnailSizeValue = Number( thumbnailSize.replace( '%', '' ) );

		const className = clsx(
			'wc-block-product-gallery-thumbnails',
			`wc-block-product-gallery-thumbnails--thumbnails-size-${ thumbnailSizeValue }`,
			{
				'wc-block-product-gallery-thumbnails--overflow-right':
					overflowState.overflowRight,
				'wc-block-product-gallery-thumbnails--overflow-bottom':
					overflowState.overflowBottom,
			}
		);
		const blockProps = useBlockProps( { className } );

		return (
			renderThumbnails && (
				<div { ...blockProps }>
					<InspectorControls>
						<PanelBody>
							<ProductGalleryThumbnailsBlockSettings
								attributes={ attributes }
								setAttributes={ setAttributes }
							/>
						</PanelBody>
					</InspectorControls>
					<div
						ref={ scrollableRef }
						className="wc-block-product-gallery-thumbnails__scrollable"
					>
						{ productThumbnails.map( ( { src, alt }, index ) => {
							return (
								<div
									className="wc-block-product-gallery-thumbnails__thumbnail"
									key={ index }
								>
									<img
										className="wc-block-product-gallery-thumbnails__thumbnail__image"
										src={ src }
										alt={ alt }
									/>
								</div>
							);
						} ) }
					</div>
				</div>
			)
		);
	}
);
