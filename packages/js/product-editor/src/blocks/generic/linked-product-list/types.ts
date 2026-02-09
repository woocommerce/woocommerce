/**
 * External dependencies
 */
import { BlockAttributes } from '@wordpress/blocks';

export type LinkedProductListBlockEmptyState = {
	image: string | 'CashRegister' | 'ShoppingBags';
	tip: string;
	isDismissible: boolean;
};

export interface LinkedProductListBlockAttributes extends BlockAttributes {
	property: string;
	emptyState: LinkedProductListBlockEmptyState;
}
