/**
 * External dependencies
 */
import type { CartItem } from '@woocommerce/types';

/**
 * Internal dependencies
 */
import type { PackageItem } from '../shipping-rates-control-package/types';
import ProductImage from '../product-image';

interface ShippingPackageItemIconProps {
	packageItem: PackageItem;
	cartItems: CartItem[];
}
/**
 * Renders a product image for a package item.
 *
 * @param {Object} props             Incoming props for the component.
 * @param {Object} props.packageItem The package item.
 * @param {Object} props.cartItems   The cartItems to get the image from via the packageItem key.
 * @return {JSX.Element} React node.
 */

const ShippingPackageItemIcon = ( {
	packageItem,
	cartItems = [],
}: ShippingPackageItemIconProps ): JSX.Element => {
	const cartItem = cartItems?.find(
		( item ) => item.key === packageItem.key
	);
	const images = cartItem?.images || [];

	return (
		<ProductImage
			image={ images.length ? images[ 0 ] : {} }
			fallbackAlt={ cartItem?.name || '' }
		/>
	);
};

export default ShippingPackageItemIcon;
