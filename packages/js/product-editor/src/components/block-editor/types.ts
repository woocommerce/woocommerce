/**
 * External dependencies
 */

/**
 * Internal dependencies
 */
import { ProductEditorContext } from '../../types';

export type BlockEditorProps = {
	context: Partial< ProductEditorContext >;
	postType: string;
	productId?: number;
	setIsEditorLoading: ( isEditorLoading: boolean ) => void;
};
