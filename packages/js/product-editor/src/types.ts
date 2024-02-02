/**
 * External dependencies
 */
import { BlockAttributes, BlockEditProps } from '@wordpress/blocks';
import { Product } from '@woocommerce/data';

// TOOD: Move this type to @woocommerce/block-templates
export type LayoutTemplate = {
	id: string;
	title: string;
	description?: string | null;
	area?: string | null;
	blockTemplates?: unknown[] | null;
	icon?: string | null;
	order?: number | null;
	productData?: Partial< Product >;
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
