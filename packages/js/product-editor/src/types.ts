/**
 * External dependencies
 */
import { BlockAttributes, BlockEditProps } from '@wordpress/blocks';
import { Product } from '@woocommerce/data';

export type ProductTemplate = {
	id: string;
	title: string;
	description: string | null;
	icon: string | null;
	order: number;
	layoutTemplateId: string;
	isSelectableByUser: boolean;
	productData: Partial< Product >;
};

export interface ProductEditorContext {
	postId: number;
	postType: string;
	selectedTab: string | null;
	isInSelectedTab?: boolean;
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

export type ProductFormPostProps = {
	id: number;
	date: string;
	date_gmt: string;
	guid: {
		rendered: string;
		raw: string;
	};
	modified: string;
	modified_gmt: string;
	password: string;
	slug: string;
	status: 'publish' | 'future' | 'draft' | 'pending' | 'private';
	type: 'product_form';
	link: string;
	title: {
		raw: string;
		rendered: string;
	};
	content: {
		raw: string;
		rendered: string;
		protected: false;
		block_version: number;
	};
	excerpt: {
		raw: string;
		rendered: string;
		protected: boolean;
	};
	featured_media: number;
	comment_status: 'open' | 'closed';
	ping_status: 'closed' | 'open';
	template: '';
	meta: [];
	permalink_template: string;
	generated_slug: string;
	class_list: string[];
};
