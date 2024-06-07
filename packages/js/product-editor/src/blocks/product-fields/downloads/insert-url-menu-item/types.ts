/**
 * External dependencies
 */
import { MediaItem } from '@wordpress/media-utils';

export type InsertUrlMenuItemProps = {
	onUploadSuccess( files: MediaItem[] ): void;
	onUploadError( error: unknown ): void;
};
