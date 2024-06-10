/**
 * External dependencies
 */
import {
	EditorSettings,
	EditorBlockListSettings,
} from '@wordpress/block-editor';
import { Template } from '@wordpress/blocks';

/**
 * Internal dependencies
 */
import { ProductTemplate } from '../../types';

export type LayoutTemplate = {
	id: string;
	title: string;
	description: string;
	area: string;
	blockTemplates: Template[];
};

export type ProductEditorSettings = Partial<
	EditorSettings & EditorBlockListSettings
> & {
	productTemplates: ProductTemplate[];
	productTemplate?: ProductTemplate;
};

export type EditorProps = {
	productId: number;
	postType?: string;
};
