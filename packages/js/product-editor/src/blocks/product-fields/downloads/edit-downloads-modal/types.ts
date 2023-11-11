/**
 * External dependencies
 */
import { ProductDownload } from '@woocommerce/data';
import { MediaItem } from '@wordpress/media-utils';

export type EditDownloadsModalProps = {
	downloableItem: ProductDownload;
	maxUploadFileSize?: number;
	onCancel: () => void;
	onRemove: () => void;
	onSave: () => void;
	onChange: ( name: string ) => void;
	onUploadSuccess( files: MediaItem[] ): void;
	onUploadError( error: unknown ): void;
};
