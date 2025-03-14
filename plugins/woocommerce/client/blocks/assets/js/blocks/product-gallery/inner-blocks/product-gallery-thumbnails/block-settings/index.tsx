/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import {
	// eslint-disable-next-line @typescript-eslint/ban-ts-comment
	// @ts-ignore - Ignoring because `__experimentalUnitControl` is not yet in the type definitions.
	// eslint-disable-next-line @wordpress/no-unsafe-wp-apis, @woocommerce/dependency-group
	__experimentalUnitControl as UnitControl,
} from '@wordpress/components';

/**
 * Internal dependencies
 */
import type { ProductGalleryThumbnailsSettingsProps } from '../types';

export const ProductGalleryThumbnailsBlockSettings = ( {
	attributes,
	setAttributes,
}: ProductGalleryThumbnailsSettingsProps ) => {
	const { thumbnailSize } = attributes;

	return (
		<UnitControl
			label={ __( 'Thumbnail Size', 'woocommerce' ) }
			value={ thumbnailSize }
			onChange={ ( value: string | undefined ) =>
				setAttributes( {
					thumbnailSize: value || '20%',
				} )
			}
			units={ [ { value: '%', label: '%' } ] }
			min={ 10 }
			max={ 50 }
			step={ 1 }
			size="default"
			__next36pxDefaultSize
			help={ __(
				'Choose the size of each thumbnail in respect to the product image. If thumbnails container size gets bigger than the product image, thumbnails will turn to slider.',
				'woocommerce'
			) }
		/>
	);
};
