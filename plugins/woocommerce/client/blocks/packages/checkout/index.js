export * from './components';
export * from './utils';
export * from './slot';
export * from './filter-registry';
export * from './blocks-registry';
// It is very important to export this directly from the build module to avoid introducing side-effects
// from importing the index of the @wordpress/components package.
export { Provider as SlotFillProvider } from 'wordpress-components-slotfill/build-module/slot-fill';

// Exporting components from the base components package
// These are needed for the OrderShippingPackagesSlot in compatibility mode
// They need to be accesible under window.wc.blocksCheckout
// To do: discuss a better way to do this
export { default as ShippingRatesControlPackage } from '../../assets/js/base/components/cart-checkout/shipping-rates-control-package';
export { default as LocalPickupSelect } from '../../assets/js/base/components/cart-checkout/local-pickup-select';
export {
	renderShippingRatesControlOption,
	renderPickupLocation,
} from '../../assets/js/base/components/cart-checkout/utilities';
export { default as NoticeBanner } from '../../assets/js/base/components/notice-banner';
