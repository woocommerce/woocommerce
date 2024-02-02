/**
 * External dependencies
 */
import { Product } from '@woocommerce/data';
import {
	EditorSettings,
	EditorBlockListSettings,
} from '@wordpress/block-editor';

/**
 * Internal dependencies
 */
import { LayoutTemplate } from '../../types';

export type ProductEditorSettings = Partial<
	EditorSettings & EditorBlockListSettings
> & {
	productFormTemplate?: LayoutTemplate;
};

export type EditorProps = {
	product: Pick< Product, 'id' | 'type' >;
	productType?: string;
	settings?: ProductEditorSettings;
};
