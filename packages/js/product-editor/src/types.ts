/**
 * External dependencies
 */
import { BlockEditProps } from '@wordpress/blocks';
import { Product } from '@woocommerce/data';

export interface ProductEditorContext {
	editedProduct: Product;
	postId: number;
	postType: string;
	selectedTab: string | null;
}

// eslint-disable-next-line @typescript-eslint/no-explicit-any
export interface ProductEditorBlockEditProps< T extends Record< string, any > >
	extends BlockEditProps< T > {
	readonly context: ProductEditorContext;
}
