/**
 * External dependencies
 */
import { ProductDownload } from '@woocommerce/data';

export type EditDownloadsModalProps = {
	downloableItem: ProductDownload;
	onCancel: () => void;
	onRemove: () => void;
	onSave: () => void;
	onChange: ( name: string ) => void;
};
