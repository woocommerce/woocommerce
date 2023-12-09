/**
 * External dependencies
 */
import { InspectorControls } from '@wordpress/block-editor';
import { PanelBody, ToggleControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import type { ProductGallerySettingsProps } from '../types';
import { ProductGalleryThumbnailsBlockSettings } from '../inner-blocks/product-gallery-thumbnails/block-settings';
import { ProductGalleryPagerBlockSettings } from '../inner-blocks/product-gallery-pager/settings';
import { ProductGalleryNextPreviousBlockSettings } from '../inner-blocks/product-gallery-large-image-next-previous/settings';

export const ProductGalleryBlockSettings = ( {
	attributes,
	setAttributes,
	context,
}: ProductGallerySettingsProps ) => {
	const { cropImages, hoverZoom, fullScreenOnClick } = attributes;
	const {
		productGalleryClientId,
		pagerDisplayMode,
		nextPreviousButtonsPosition,
		thumbnailsNumberOfThumbnails,
		thumbnailsPosition,
	} = context;
	return (
		<InspectorControls>
			<PanelBody
				title={ __(
					'Gallery Navigation',
					'woo-gutenberg-products-block'
				) }
			>
				<ProductGalleryPagerBlockSettings
					context={ { productGalleryClientId, pagerDisplayMode } }
				/>
				<ProductGalleryNextPreviousBlockSettings
					context={ {
						productGalleryClientId,
						nextPreviousButtonsPosition,
					} }
				/>
				<ProductGalleryThumbnailsBlockSettings
					context={ {
						productGalleryClientId,
						thumbnailsNumberOfThumbnails,
						thumbnailsPosition,
					} }
				/>
			</PanelBody>
			<PanelBody
				title={ __( 'Media Settings', 'woo-gutenberg-products-block' ) }
			>
				<ToggleControl
					label={ __(
						'Crop images to fit',
						'woo-gutenberg-products-block'
					) }
					help={ __(
						'Images will be cropped to fit within a square space.',
						'woo-gutenberg-products-block'
					) }
					checked={ cropImages }
					onChange={ () =>
						setAttributes( {
							cropImages: ! cropImages,
						} )
					}
					className="wc-block-product-gallery__crop-images"
				/>
				<ToggleControl
					label={ __(
						'Zoom while hovering',
						'woo-gutenberg-products-block'
					) }
					help={ __(
						'While hovering the large image will zoom in by 30%.',
						'woo-gutenberg-products-block'
					) }
					checked={ hoverZoom }
					onChange={ () =>
						setAttributes( {
							hoverZoom: ! hoverZoom,
						} )
					}
				/>
				<ToggleControl
					label={ __(
						'Full-screen when clicked',
						'woo-gutenberg-products-block'
					) }
					help={ __(
						'Clicking on the large image will open a full-screen gallery experience.',
						'woo-gutenberg-products-block'
					) }
					checked={ fullScreenOnClick }
					onChange={ () =>
						setAttributes( {
							fullScreenOnClick: ! fullScreenOnClick,
						} )
					}
				/>
			</PanelBody>
		</InspectorControls>
	);
};
