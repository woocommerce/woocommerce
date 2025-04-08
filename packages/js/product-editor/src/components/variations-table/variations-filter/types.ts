/**
 * External dependencies
 */
import {
	ProductProductAttribute,
	ProductAttributeTerm,
} from '@woocommerce/data';

export type VariationsFilterProps = {
	initialValues: ProductAttributeTerm[ 'slug' ][];
	attribute: ProductProductAttribute;
	onFilter( values: ProductAttributeTerm[ 'slug' ][] ): void;
};
