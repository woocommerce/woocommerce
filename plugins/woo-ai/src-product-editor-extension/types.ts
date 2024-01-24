/**
 * External dependencies
 */
import {
	// @ts-expect-error no exported member.
	ComponentType,
} from '@wordpress/element';
// eslint-disable-next-line @typescript-eslint/ban-ts-comment
// @ts-ignore No types for this exist yet.
// eslint-disable-next-line @woocommerce/dependency-group
import { BlockAttributes, BlockEditProps } from '@wordpress/blocks';

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
	readonly isSelected: boolean;
	readonly attributes: T;
}

export interface ProductEditorBlockAttributes extends BlockAttributes {
	_templateBlockId?: string;
	property?: string;
}

export type ProductTitleBlockEditProps =
	ProductEditorBlockEditProps< ProductEditorBlockAttributes >;

export type ProductTitleBlockEditComponent =
	ComponentType< ProductTitleBlockEditProps >;
