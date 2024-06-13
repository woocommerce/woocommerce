/**
 * External dependencies
 */
import { ProductDownload } from '@woocommerce/data';

export type EditDownloadsModalProps = {
	downloadableItem: ProductDownload;
	maxUploadFileSize?: number;
	onCancel: () => void;
	onRemove: () => void;
	onSave: () => void;
	onChange: ( name: string ) => void;
};
