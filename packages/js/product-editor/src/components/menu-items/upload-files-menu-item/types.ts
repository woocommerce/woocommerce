/**
 * External dependencies
 */
import {
	FormFileUpload,
	MenuItem as DropdownMenuItem,
} from '@wordpress/components';
import type { Attachment } from '@wordpress/media-utils';
import { MediaUploaderErrorCallback } from '@woocommerce/components';

/**
 * Minimal subset of uploadMedia options used by this component.
 * Defined locally because UploadMediaOptions is not exported
 * from the native @wordpress/media-utils package.
 */
interface UploadMediaOptionsSubset {
	additionalData?: Record< string, unknown >;
	allowedTypes?: string[];
	maxUploadFileSize?: number;
	wpAllowedMimeTypes?: Record< string, string > | null;
}

export type UploadFilesMenuItemProps = Omit<
	React.ComponentProps< typeof FormFileUpload >,
	'children' | 'render' | 'onChange'
> &
	React.ComponentProps< typeof DropdownMenuItem > &
	Partial<
		Pick<
			UploadMediaOptionsSubset,
			| 'additionalData'
			| 'allowedTypes'
			| 'maxUploadFileSize'
			| 'wpAllowedMimeTypes'
		>
	> & {
		onUploadProgress?( files: Attachment[] ): void;
		onUploadSuccess( files: Attachment[] ): void;
		onUploadError: MediaUploaderErrorCallback;
		text?: string;
	};
