/**
 * External dependencies
 */
import { Product } from '@woocommerce/data';

export interface EvaluationContext {
	postType: string;
	editedProduct: Product;
}

export interface BlockTemplateAttributes {
	[ key: string ]: unknown;
	_templateBlockId: string;
	_templateBlockOrder: number;
}

export interface BlockTemplate {
	0: string;
	1: BlockTemplateAttributes;
	2?: BlockTemplate[];
}

export type BlockTemplateArray = BlockTemplate[];
