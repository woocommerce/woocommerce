/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { RangeControl } from '@wordpress/components';

/**
 * Internal dependencies
 */
import type { ProductGalleryThumbnailsSettingsProps } from '../types';

export const ProductGalleryThumbnailsBlockSettings = ( {
	attributes,
	setAttributes,
}: ProductGalleryThumbnailsSettingsProps ) => {
	const maxNumberOfThumbnails = 8;
	const minNumberOfThumbnails = 3;
	const { numberOfThumbnails } = attributes;

	return (
		<RangeControl
			label={ __( 'Number of Thumbnails', 'woocommerce' ) }
			value={ numberOfThumbnails }
			onChange={ ( value: number ) =>
				setAttributes( {
					numberOfThumbnails: Math.round( value ),
				} )
			}
			help={ __(
				'Choose how many thumbnails (3-8) will display. If more images exist, a “View all” button will display.',
				'woocommerce'
			) }
			max={ maxNumberOfThumbnails }
			min={ minNumberOfThumbnails }
			step={ 1 }
		/>
	);
};
