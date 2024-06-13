/**
 * External dependencies
 */
import { MediaItem } from '@wordpress/media-utils';
import { MediaUploaderErrorCallback } from '@woocommerce/components';

export type DownloadsMenuProps = {
	allowedTypes?: string[];
	maxUploadFileSize?: number;
	onUploadSuccess( files: MediaItem[] ): void;
	onUploadError: MediaUploaderErrorCallback;
};
