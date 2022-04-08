/**
 * External dependencies
 */
import { __, sprintf } from '@wordpress/i18n';
import { EnteredAddress, getSetting } from '@woocommerce/settings';
import { decodeEntities } from '@wordpress/html-entities';

interface ShippingLocationProps {
	address: EnteredAddress;
}

/**
 * Shows a formatted shipping location.
 *
 * @param {Object} props         Incoming props for the component.
 * @param {Object} props.address Incoming address information.
 */
const ShippingLocation = ( {
	address,
}: ShippingLocationProps ): JSX.Element | null => {
	// we bail early if we don't have an address.
	if ( Object.values( address ).length === 0 ) {
		return null;
	}
	const shippingCountries = getSetting( 'shippingCountries', {} ) as Record<
		string,
		string
	>;
	const shippingStates = getSetting( 'shippingStates', {} ) as Record<
		string,
		Record< string, string >
	>;
	const formattedCountry =
		typeof shippingCountries[ address.country ] === 'string'
			? decodeEntities( shippingCountries[ address.country ] )
			: '';

	const formattedState =
		typeof shippingStates[ address.country ] === 'object' &&
		typeof shippingStates[ address.country ][ address.state ] === 'string'
			? decodeEntities(
					shippingStates[ address.country ][ address.state ]
			  )
			: address.state;

	const addressParts = [];

	addressParts.push( address.postcode.toUpperCase() );
	addressParts.push( address.city );
	addressParts.push( formattedState );
	addressParts.push( formattedCountry );

	const formattedLocation = addressParts.filter( Boolean ).join( ', ' );

	if ( ! formattedLocation ) {
		return null;
	}

	return (
		<span className="wc-block-components-shipping-address">
			{ sprintf(
				/* translators: %s location. */
				__( 'Shipping to %s', 'woo-gutenberg-products-block' ),
				formattedLocation
			) + ' ' }
		</span>
	);
};

export default ShippingLocation;
