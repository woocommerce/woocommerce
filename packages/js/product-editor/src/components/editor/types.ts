/**
 * External dependencies
 */
import { Product } from '@woocommerce/data';
import {
	EditorSettings,
	EditorBlockListSettings,
} from '@wordpress/block-editor';
import { Template } from '@wordpress/blocks';

export type LayoutTemplate = {
	id: string;
	title: string;
	description: string;
	area: string;
	blockTemplates: Template[];
};

export type ProductTemplate = {
	id: string;
	title: string;
	description: string | null;
	icon: string | null;
	order: number;
	layoutTemplateId: string;
	productData: Partial< Product >;
};

export type ProductEditorSettings = Partial<
	EditorSettings & EditorBlockListSettings
> & {
	layoutTemplates: LayoutTemplate[];
	productTemplates: ProductTemplate[];
	productTemplate?: ProductTemplate;
};

export type EditorProps = {
	product: Pick< Product, 'id' | 'type' >;
	productType?: string;
	settings?: ProductEditorSettings;
};
