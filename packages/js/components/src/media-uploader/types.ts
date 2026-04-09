/**
 * External dependencies
 */
import type { Attachment } from '@wordpress/media-utils';

/**
 * A media item returned from the WordPress media library.
 *
 * Matches the old `MediaItem` from `@types/wordpress__media-utils`.
 * Uses the native `Attachment` type which provides the same renamed
 * fields (alt, caption, title, url) as the DT version.
 */
export type MediaItem = Attachment;

/**
 * Error codes that `uploadMedia` can produce.
 *
 * Matches the old `UploadMediaErrorCode` from `@types/wordpress__media-utils`.
 */
export type UploadMediaErrorCode =
	| 'MIME_TYPE_NOT_ALLOWED_FOR_USER'
	| 'MIME_TYPE_NOT_SUPPORTED'
	| 'SIZE_ABOVE_LIMIT'
	| 'EMPTY_FILE'
	| 'GENERAL';

export type ErrorType = {
	code: UploadMediaErrorCode;
	message: string;
	file: File;
};

/**
 * Options for the `uploadMedia` function.
 *
 * Matches the old `UploadMediaOptions` from `@types/wordpress__media-utils`.
 */
export interface UploadMediaOptions {
	additionalData?: Record< string, unknown >;
	allowedTypes?: string[];
	filesList: ArrayLike< File >;
	maxUploadFileSize?: number;
	onError?: ( error: ErrorType ) => void;
	onFileChange?: ( files: MediaItem[] ) => void;
	wpAllowedMimeTypes?: Record< string, string >;
}

/**
 * Props for the MediaUpload component.
 *
 * Matches the old `MediaUpload.Props<T>` from `@types/wordpress__block-editor`.
 */
export interface MediaUploadProps< T extends boolean = false > {
	addToGallery?: boolean;
	allowedTypes?: string[];
	gallery?: boolean;
	modalClass?: string;
	multiple?: T | string;
	value?: T extends true ? number[] : number;
	onSelect(
		value: T extends true
			? // eslint-disable-next-line @typescript-eslint/no-explicit-any
			  Array< { id: number } & { [ k: string ]: any } >
			: // eslint-disable-next-line @typescript-eslint/no-explicit-any
			  { id: number } & { [ k: string ]: any }
	): void;
	render( props: { open(): void } ): JSX.Element;
	title?: string;
}

/**
 * The component type for the MediaUpload component.
 *
 * Accepts both class components (like the native MediaUpload) and
 * function components that match the MediaUploadProps shape.
 */
// eslint-disable-next-line @typescript-eslint/no-explicit-any
export type MediaUploadComponentType = React.ComponentType<
	MediaUploadProps< any >
>;
