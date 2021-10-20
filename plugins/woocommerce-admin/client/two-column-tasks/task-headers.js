/**
 * Internal dependencies
 */
import TaxHeader from './headers/tax';
import MarketingHeader from './headers/marketing';
import AppearanceHeader from './headers/appearance';
import ShippingHeader from './headers/shipping';
import ProductsHeader from './headers/products';
import PaymentsHeader from './headers/payments';

export default {
	tax: TaxHeader,
	shipping: ShippingHeader,
	marketing: MarketingHeader,
	appearance: AppearanceHeader,
	payments: PaymentsHeader,
	products: ProductsHeader,
};
