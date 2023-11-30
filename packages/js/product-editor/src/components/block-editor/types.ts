/**
 * External dependencies
 */
import {
	EditorBlockListSettings,
	EditorSettings,
} from '@wordpress/block-editor';
import { Template } from '@wordpress/blocks';

/**
 * Internal dependencies
 */
import { ProductEditorContext } from '../../types';

export type BlockEditorSettings = Partial<
	EditorSettings & EditorBlockListSettings
> & {
	templates?: Record< string, Template[] >;
};

export type BlockEditorProps = {
	context: Partial< ProductEditorContext >;
	postType: string;
	productId: number;
	productType: string;
	settings: BlockEditorSettings | undefined;
};
