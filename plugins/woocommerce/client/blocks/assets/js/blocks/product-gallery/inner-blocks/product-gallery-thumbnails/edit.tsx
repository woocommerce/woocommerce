/**
 * External dependencies
 */
import { InspectorControls, useBlockProps } from '@wordpress/block-editor';
import { PanelBody } from '@wordpress/components';
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
	const blockProps = useBlockProps( {
		className: `wc-block-product-gallery-thumbnails wc-block-product-gallery-thumbnails--number-of-thumbnails-${ attributes.numberOfThumbnails }`,
	} );

	return (
		<div { ...blockProps }>
			<InspectorControls>
				<PanelBody>
					<ProductGalleryThumbnailsBlockSettings
						attributes={ attributes }
						setAttributes={ setAttributes }
					/>
				</PanelBody>
			</InspectorControls>
			{ [ ...Array( attributes.numberOfThumbnails ).keys() ].map(
				( index ) => {
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
				}
			) }
		</div>
	);
};
