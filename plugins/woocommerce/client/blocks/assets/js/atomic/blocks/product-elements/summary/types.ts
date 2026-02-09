/**
 * External dependencies
 */
import type { BlockEditProps } from '@wordpress/blocks';
import type { ProductQueryContext as Context } from '@woocommerce/blocks/product-query/types';
import type { CSSProperties } from '@wordpress/element';
import type { ProductEntityResponse } from '@woocommerce/entities';

export interface Attributes {
	productId: number;
	isDescendentOfQueryLoop: boolean;
	isDescendentOfSingleProductTemplate: boolean;
	isDescendentOfSingleProductBlock: boolean;
	isDescendantOfAllProducts: boolean;
	showDescriptionIfEmpty: boolean;
	showLink: boolean;
	summaryLength: number;
	linkText: string;
}
export type SetAttributes = Pick<
	BlockEditProps< Attributes >,
	'setAttributes'
>;

export type EditProps = BlockEditProps< Attributes > & {
	context: Context & { postId?: number };
};

export type ControlProps< T extends keyof Attributes > = Pick< Attributes, T > &
	SetAttributes;

export type BlockProps = Attributes & {
	style?: CSSProperties;
	className?: string;
	product?: ProductEntityResponse | null | undefined;
	isAdmin?: boolean;
};
