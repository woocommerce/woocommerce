/**
 * External dependencies
 */
import { MediaItem } from '@wordpress/media-utils';

export type MediaLibraryMenuItemProps = {
	allowedTypes?: string[];
	onUploadSuccess( files: MediaItem[] ): void;
};
