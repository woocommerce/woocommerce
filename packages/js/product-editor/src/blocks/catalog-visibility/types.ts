/**
 * External dependencies
 */
import { Product } from '@woocommerce/data';
import { BlockAttributes } from '@wordpress/blocks';

export interface CatalogVisibilityBlockAttributes extends BlockAttributes {
	label: string;
	visibilty: Product[ 'catalog_visibility' ];
}
