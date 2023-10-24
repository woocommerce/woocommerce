/**
 * External dependencies
 */
import { ProductAttribute } from '@woocommerce/data';

export type VariationsFilterProps = {
	attribute: ProductAttribute;
	onFilter( values: ProductAttribute[ 'options' ] ): void;
};
