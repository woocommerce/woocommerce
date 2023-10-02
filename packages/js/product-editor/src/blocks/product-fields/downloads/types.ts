/**
 * External dependencies
 */
import { ProductDownload } from '@woocommerce/data';
import { BlockAttributes } from '@wordpress/blocks';

export interface UploadsBlockAttributes extends BlockAttributes {}

export type DownloadableFileItem = {
	key: string;
	download: ProductDownload;
	uploading?: boolean;
};
