/**
 * External dependencies
 */
import { MediaUpload } from '@wordpress/media-utils';

export type ImageGalleryChild = JSX.Element;

export type MediaUploadComponentType = < T extends boolean = false >(
	props: MediaUpload.Props< T >
) => JSX.Element;
