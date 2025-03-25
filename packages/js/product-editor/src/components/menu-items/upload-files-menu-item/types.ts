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
	React.ComponentProps< typeof FormFileUpload >,
	'children' | 'render' | 'onChange'
> &
	React.ComponentProps< typeof DropdownMenuItem > &
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
		text?: string;
	};
