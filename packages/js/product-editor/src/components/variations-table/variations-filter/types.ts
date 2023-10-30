/**
 * External dependencies
 */
import { ProductAttribute, ProductAttributeTerm } from '@woocommerce/data';

export type VariationsFilterProps = {
	initialValues: ProductAttributeTerm[ 'slug' ][];
	attribute: ProductAttribute;
	onFilter( values: ProductAttributeTerm[ 'slug' ][] ): void;
};
