/**
 * External dependencies
 */
import { ProductCatalogVisibility } from '@woocommerce/data';

export type CatalogVisibilityProps = {
	catalogVisibility: string;
	onCheckboxChange: ( value: ProductCatalogVisibility ) => void;
};
