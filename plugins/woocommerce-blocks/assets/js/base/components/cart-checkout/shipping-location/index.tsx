/**
 * External dependencies
 */
import { __, sprintf } from '@wordpress/i18n';

interface ShippingLocationProps {
	formattedLocation: string | null;
}

// Shows a formatted shipping location.
const ShippingLocation = ( {
	formattedLocation,
}: ShippingLocationProps ): JSX.Element | null => {
	if ( ! formattedLocation ) {
		return null;
	}

	return (
		<span className="wc-block-components-shipping-address">
			{ sprintf(
				/* translators: %s location. */
				__( 'Delivers to %s', 'woocommerce' ),
				formattedLocation
			) + ' ' }
		</span>
	);
};

export default ShippingLocation;
