/**
 * External dependencies
 */
import { BlockAttributes } from '@wordpress/blocks';

export interface CatalogVisibilityBlockAttributes extends BlockAttributes {
	label: string;
}

export type ProductCatalogVisibility =
	| 'visible'
	| 'catalog'
	| 'search'
	| 'hidden';
