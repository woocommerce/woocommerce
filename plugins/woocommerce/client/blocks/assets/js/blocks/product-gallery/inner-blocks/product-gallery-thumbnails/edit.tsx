/**
 * External dependencies
 */
import { InspectorControls, useBlockProps } from '@wordpress/block-editor';
import { PanelBody, Disabled } from '@wordpress/components';
import { WC_BLOCKS_IMAGE_URL } from '@woocommerce/block-settings';
import type { BlockEditProps } from '@wordpress/blocks';

/**
 * Internal dependencies
 */
import { ProductGalleryThumbnailsBlockSettings } from './block-settings';
import type { ProductGalleryThumbnailsBlockAttributes } from './types';

export const Edit = ( {
	attributes,
	setAttributes,
}: BlockEditProps< ProductGalleryThumbnailsBlockAttributes > ) => {
	const { thumbnailSize } = attributes;
	const minSize = 10;
	const maxSize = 50;
	const defSize = 20;
	const thumbnailSizeValue =
		Math.min(
			Math.max( Number( thumbnailSize.replace( '%', '' ) ), minSize ),
			maxSize
		) || defSize;
	const blockProps = useBlockProps( {
		className: `wc-block-product-gallery-thumbnails wc-block-product-gallery-thumbnails--thumbnails-size-${ thumbnailSizeValue }`,
	} );

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
			<div { ...blockProps }>
				{ [ ...Array( 6 ).keys() ].map( ( index ) => {
					return (
						<div
							className="wc-block-product-gallery-thumbnails__thumbnail"
							key={ index }
						>
							<img
								className="wc-block-product-gallery-thumbnails__image"
								src={ `${ WC_BLOCKS_IMAGE_URL }block-placeholders/product-image-gallery.svg` }
								alt=""
							/>
						</div>
					);
				} ) }
			</div>
		</>
	);
};
