/**
 * External dependencies
 */
import {
	FormFileUpload,
	MenuItem as DropdownMenuItem,
} from '@wordpress/components';
import { MediaItem, UploadMediaOptions } from '@wordpress/media-utils';

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
		onUploadError( error: unknown ): void;
	};
