/**
 * External dependencies
 */
import { ProductDownload } from '@woocommerce/data';

export type EditDownloadsModalProps = {
	downloableItem: ProductDownload;
	maxUploadFileSize?: number;
	onCancel: () => void;
	onRemove: () => void;
	onSave: () => void;
	onChange: ( name: string ) => void;
};
