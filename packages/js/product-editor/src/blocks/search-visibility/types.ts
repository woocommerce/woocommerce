/**
 * External dependencies
 */
import { BlockAttributes } from '@wordpress/blocks';

export interface SearchVisibilityBlockAttributes extends BlockAttributes {
	label: string;
}

export type ProductCatalogVisibility =
	| 'visible'
	| 'catalog'
	| 'search'
	| 'hidden';
