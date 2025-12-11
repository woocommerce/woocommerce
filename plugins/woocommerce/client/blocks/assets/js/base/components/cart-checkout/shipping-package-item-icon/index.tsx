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
 * Formats and returns an image element.
 *
 * @param {Object} props       Incoming props for the component.
 * @param {Object} props.image Image properties.
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
