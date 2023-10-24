/**
 * External dependencies
 */
import { ProductAttribute } from '@woocommerce/data';

export type VariationsFilterProps = {
	initialValues: ProductAttribute[ 'options' ];
	attribute: ProductAttribute;
	onFilter( values: ProductAttribute[ 'options' ] ): void;
};
