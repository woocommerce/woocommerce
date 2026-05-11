/**
 * Error type for media upload failures.
 *
 * At runtime, WordPress's `uploadMedia` passes `UploadError` instances
 * (extending `Error`) with `code`, `message`, and `file` properties.
 */
export type ErrorType = {
	code: string;
	message: string;
	file: File;
};

/**
 * Props for the MediaUpload component.
 *
 * The native `MediaUpload` at wp-6.8 is typed as `Component<any>`.
 * This interface preserves the typed prop surface from the old
 * `@types/wordpress__block-editor` `MediaUpload.Props<T>`.
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
/* eslint-disable @typescript-eslint/no-explicit-any */
export type MediaUploadComponentType = React.ComponentType<
	MediaUploadProps< any >
>;
/* eslint-enable @typescript-eslint/no-explicit-any */
