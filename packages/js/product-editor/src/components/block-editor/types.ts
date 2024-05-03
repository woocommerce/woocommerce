/**
 * External dependencies
 */

/**
 * Internal dependencies
 */
import { ProductEditorContext } from '../../types';
import { ProductEditorSettings } from '../editor';

export type BlockEditorProps = {
	context: Partial< ProductEditorContext >;
	postType: string;
	productId: number;
	settings?: ProductEditorSettings;
};
