/**
 * External dependencies
 */
import { BlockAttributes, BlockEditProps } from '@wordpress/blocks';

// Type guard function to check if children is a function, while still type narrowing correctly.
export function isCallable(
	children: unknown
): children is ( props: unknown ) => React.ReactNode {
	return typeof children === 'function';
}

export interface ProductEditorContext {
	postId: number;
	postType: string;
	selectedTab: string | null;
}

// eslint-disable-next-line @typescript-eslint/no-explicit-any
export interface ProductEditorBlockEditProps< T extends Record< string, any > >
	extends BlockEditProps< T > {
	readonly context: ProductEditorContext;
	readonly name: string;
}

export interface ProductEditorBlockAttributes extends BlockAttributes {
	_templateBlockId?: string;
}

export interface Metadata< T > {
	id?: number;
	key: string;
	value?: T;
}

export interface Taxonomy {
	id: number;
	name: string;
	parent: number;
	meta?: Record< string, string >;
}

export interface TaxonomyMetadata {
	hierarchical: boolean;
}
