/**
 * Internal dependencies
 */
import { registerProductField } from '../registration';
import { RegularPriceBlockEdit } from './field';

export function initRegularPriceField() {
	return registerProductField( 'woocommerce/product-regular-price-field', {
		id: 'regular_price',
		label: 'Regular price',
		type: 'integer',
		Edit: RegularPriceBlockEdit,
	} );
}
