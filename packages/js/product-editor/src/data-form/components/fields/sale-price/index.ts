/**
 * Internal dependencies
 */
import { registerProductField } from '../registration';
import { SalePriceBlockEdit } from './field';

export function initSalePriceField() {
	return registerProductField( 'woocommerce/product-sale-price-field', {
		id: 'sale_price',
		label: 'Sale price',
		type: 'integer',
		Edit: SalePriceBlockEdit,
	} );
}
