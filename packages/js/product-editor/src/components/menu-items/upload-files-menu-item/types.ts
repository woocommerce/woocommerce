/**
 * External dependencies
 */
import {
	FormFileUpload,
	MenuItem as DropdownMenuItem,
} from '@wordpress/components';
import {
	MediaItem,
	UploadMediaErrorCode,
	UploadMediaOptions,
} from '@wordpress/media-utils';

type ErrorType = {
	code: UploadMediaErrorCode;
	message: string;
	file: File;
};

export type UploadFilesMenuItemErrorCallback = ( error: ErrorType ) => void;

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
		onUploadError: UploadFilesMenuItemErrorCallback;
	};
