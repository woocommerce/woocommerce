/**
 * External dependencies
 */
import {
	FormFileUpload,
	MenuItem as DropdownMenuItem,
} from '@wordpress/components';
import { MediaItem, UploadMediaOptions } from '@wordpress/media-utils';
import { MediaUploaderErrorCallback } from '@woocommerce/components';

export type UploadFilesMenuItemProps = Omit<
	FormFileUpload.Props,
	'children' | 'render' | 'onChange'
> &
	Pick< DropdownMenuItem.Props, 'icon' | 'iconPosition' | 'text' | 'info' > &
	Partial<
		Pick<
			UploadMediaOptions,
			| 'additionalData'
			| 'allowedTypes'
			| 'maxUploadFileSize'
			| 'wpAllowedMimeTypes'
		>
	> & {
		onUploadProgress?( files: MediaItem[] ): void;
		onUploadSuccess( files: MediaItem[] ): void;
		onUploadError: MediaUploaderErrorCallback;
	};
