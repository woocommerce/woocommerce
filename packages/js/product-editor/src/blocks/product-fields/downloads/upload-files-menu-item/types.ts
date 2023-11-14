/**
 * External dependencies
 */
import { MediaItem } from '@wordpress/media-utils';

export type UploadFilesMenuItemProps = {
	allowedTypes?: string[];
	maxUploadFileSize?: number;
	onUploadSuccess( files: MediaItem[] ): void;
	onUploadError( error: unknown ): void;
};
