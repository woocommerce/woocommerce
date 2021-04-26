/**
 * External dependencies
 */
import PropTypes from 'prop-types';
import { __, sprintf } from '@wordpress/i18n';
import { getSetting } from '@woocommerce/settings';
import { decodeEntities } from '@wordpress/html-entities';

/**
 * Shows a formatted shipping location.
 *
 * @param {Object} props Incoming props for the component.
 * @param {Object} props.address Incoming address information.
 */
const ShippingLocation = ( { address } ) => {
	// we bail early if we don't have an address.
	if ( Object.values( address ).length === 0 ) {
		return null;
	}
	const shippingCountries = getSetting( 'shippingCountries', {} );
	const shippingStates = getSetting( 'shippingStates', {} );
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

ShippingLocation.propTypes = {
	address: PropTypes.shape( {
		city: PropTypes.string,
		state: PropTypes.string,
		postcode: PropTypes.string,
		country: PropTypes.string,
	} ),
};

export default ShippingLocation;
