/**
 * External dependencies
 */
import { UploadMediaErrorCode } from '@wordpress/media-utils';

export type ErrorType = {
	code: UploadMediaErrorCode;
	message: string;
	file: File;
};

// eslint-disable-next-line @typescript-eslint/no-explicit-any
export type File = { id: number } & { [ k: string ]: any };
