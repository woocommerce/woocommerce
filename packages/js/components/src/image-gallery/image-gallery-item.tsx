/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { createElement } from '@wordpress/element';

/**
 * Internal dependencies
 */
import Pill from '../pill';

export type ImageGalleryItemProps = {
	alt: string;
	isCover?: boolean;
	src: string;
} & React.HTMLAttributes< HTMLDivElement >;

export const ImageGalleryItem: React.FC< ImageGalleryItemProps > = ( {
	alt,
	isCover = false,
	src,
}: ImageGalleryItemProps ) => {
	return (
		<div className="woocommerce-image-gallery__item">
			{ isCover && <Pill>{ __( 'Cover', 'woocommerce' ) }</Pill> }
			<img alt={ alt } src={ src } />
		</div>
	);
};
