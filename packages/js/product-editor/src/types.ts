/**
 * External dependencies
 */
import { BlockEditProps } from '@wordpress/blocks';

export interface ProductEditorContext {
	postId: number;
	postType: string;
	selectedTab: string | null;
}

// eslint-disable-next-line @typescript-eslint/no-explicit-any
export interface ProductEditorBlockEditProps< T extends Record< string, any > >
	extends BlockEditProps< T > {
	readonly context: ProductEditorContext;
}

export interface Metadata< T > {
	id?: number;
	key: string;
	value?: T;
}
