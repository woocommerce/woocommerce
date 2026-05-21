/**
 * External dependencies
 */
import type { Attachment } from '@wordpress/media-utils';
import { MediaUploaderErrorCallback } from '@woocommerce/components';

/**
 * Internal dependencies
 */
import { InsertUrlLinkErrorCallback } from '../insert-url-menu-item';

export type DownloadsMenuProps = {
	allowedTypes?: string[];
	maxUploadFileSize?: number;
	onUploadSuccess( files: Attachment[] ): void;
	onUploadError: MediaUploaderErrorCallback;
	onLinkError: InsertUrlLinkErrorCallback;
};
