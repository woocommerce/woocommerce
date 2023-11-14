/**
 * External dependencies
 */
import { InspectorControls, useBlockProps } from '@wordpress/block-editor';
import { Disabled, PanelBody } from '@wordpress/components';
import type { BlockEditProps } from '@wordpress/blocks';
import { WC_BLOCKS_IMAGE_URL } from '@woocommerce/block-settings';
import classNames from 'classnames';

/**
 * Internal dependencies
 */
import './editor.scss';
import { ProductGalleryThumbnailsBlockSettings } from './block-settings';
import type {
	ProductGalleryThumbnailsBlockAttributes,
	ProductGalleryContext,
} from '../../types';
import { ThumbnailsPosition } from './constants';

interface EditProps
	extends BlockEditProps< ProductGalleryThumbnailsBlockAttributes > {
	context: ProductGalleryContext;
}

export const Edit = ( { attributes, setAttributes, context }: EditProps ) => {
	const blockProps = useBlockProps( {
		className: 'wc-block-product-gallery-thumbnails',
	} );

	const Placeholder = () => {
		return context.thumbnailsPosition !== ThumbnailsPosition.OFF ? (
			<div
				className={ classNames(
					'wc-block-editor-product-gallery-thumbnails',
					`wc-block-editor-product-gallery-thumbnails--${ context.thumbnailsPosition }`
				) }
			>
				{ [
					...Array( context.thumbnailsNumberOfThumbnails ).keys(),
				].map( ( index ) => {
					return (
						<img
							key={ index }
							src={ `${ WC_BLOCKS_IMAGE_URL }block-placeholders/product-image-gallery.svg` }
							alt="Placeholder"
						/>
					);
				} ) }
			</div>
		) : null;
	};

	return (
		<>
			<div { ...blockProps }>
				<InspectorControls>
					<PanelBody>
						<ProductGalleryThumbnailsBlockSettings
							attributes={ attributes }
							setAttributes={ setAttributes }
							context={ context }
						/>
					</PanelBody>
				</InspectorControls>
				<Disabled>
					<Placeholder />
				</Disabled>
			</div>
		</>
	);
};
