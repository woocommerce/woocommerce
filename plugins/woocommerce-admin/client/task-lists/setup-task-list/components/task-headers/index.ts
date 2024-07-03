/**
 * Internal dependencies
 */
import StoreDetailsHeader from './store-details';
import CustomizeStoreHeader from './customize-store';
import TaxHeader from './tax';
import MarketingHeader from './marketing';
import AppearanceHeader from './appearance';
import ShippingHeader from './shipping';
import ProductsHeader from './products';
import PurchaseHeader from './purchase';
import PaymentsHeader from './payments';
import WoocommercePaymentsHeader from './woocommerce-payments';
import LaunchYourStoreHeader from './launch-your-store';

export const taskHeaders: Record< string, React.ElementType > = {
	store_details: StoreDetailsHeader,
	'customize-store': CustomizeStoreHeader,
	tax: TaxHeader,
	shipping: ShippingHeader,
	marketing: MarketingHeader,
	appearance: AppearanceHeader,
	payments: PaymentsHeader,
	products: ProductsHeader,
	purchase: PurchaseHeader,
	'woocommerce-payments': WoocommercePaymentsHeader,
	'launch-your-store': LaunchYourStoreHeader,
};
