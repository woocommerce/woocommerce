/**
 * External dependencies
 */
import { MediaItem } from '@wordpress/media-utils';

export type DownloadsMenuProps = {
	allowedTypes?: string[];
	maxUploadFileSize?: number;
	onUploadSuccess( files: MediaItem[] ): void;
	onUploadError( error: unknown ): void;
};
