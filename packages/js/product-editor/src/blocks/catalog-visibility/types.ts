/**
 * External dependencies
 */
import { BlockAttributes } from '@wordpress/blocks';

export type ProductCatalogVisibility =
	| 'visible'
	| 'catalog'
	| 'search'
	| 'hidden';

export interface CatalogVisibilityBlockAttributes extends BlockAttributes {
	label: string;
	visibilty: ProductCatalogVisibility;
}
