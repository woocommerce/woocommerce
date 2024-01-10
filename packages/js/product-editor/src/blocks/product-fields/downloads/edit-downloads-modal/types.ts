/**
 * External dependencies
 */
import { ProductDownload } from '@woocommerce/data';
import { InputChangeCallback } from '@wordpress/components/build-types/input-control/types';
import { MediaItem } from '@wordpress/media-utils';

export type EditDownloadsModalProps = {
	downloableItem: ProductDownload;
	maxUploadFileSize?: number;
	onCancel: () => void;
	onRemove: () => void;
	onSave: () => void;
	onChange: InputChangeCallback;
	onUploadSuccess( files: MediaItem[] ): void;
	onUploadError( error: unknown ): void;
};
