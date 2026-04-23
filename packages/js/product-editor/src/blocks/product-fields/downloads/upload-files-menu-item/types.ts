/**
 * External dependencies
 */
import type { Attachment } from '@wordpress/media-utils';
import { MediaUploaderErrorCallback } from '@woocommerce/components';

export type UploadFilesMenuItemProps = {
	allowedTypes?: string[];
	maxUploadFileSize?: number;
	onUploadSuccess( files: Attachment[] ): void;
	onUploadError: MediaUploaderErrorCallback;
};
