/**
 * External dependencies
 */
import PropTypes from 'prop-types';
import { useShippingRates } from '@woocommerce/base-hooks';
import { useEffect } from '@wordpress/element';

/**
 * Internal dependencies
 */
import Package from './package';
import './style.scss';

const ShippingRatesControl = ( {
	address,
	className,
	noResultsMessage,
	onChange,
	renderOption,
	selected = [],
} ) => {
	const { shippingRates, shippingRatesLoading } = useShippingRates( address );

	// Select first item when shipping rates are loaded.
	useEffect(
		() => {
			if ( shippingRates.length === 0 ) {
				return;
			}

			const isSelectedValid =
				selected.length === shippingRates.length &&
				selected.every( ( selectedId, i ) => {
					const rates = shippingRates[ i ].shipping_rates;
					return rates.some(
						( { rate_id: rateId } ) => rateId === selectedId
					);
				} );

			if ( isSelectedValid ) {
				return;
			}

			const newShippingRates = shippingRates.map( ( shippingRate ) => {
				if ( shippingRate.shipping_rates.length > 0 ) {
					return shippingRate.shipping_rates[ 0 ].rate_id;
				}
				return null;
			} );

			if ( newShippingRates.length > 0 ) {
				onChange( newShippingRates );
			}
		},
		// We only want to run this when `shippingRates` changes,
		// so there is no need to add `selected` to the effect dependencies.
		[ shippingRates ]
	);

	if ( shippingRatesLoading ) {
		// @todo Add some indication that shipping rates are loading.
		// see: https://github.com/woocommerce/woocommerce-gutenberg-products-block/issues/1555
		return null;
	}

	return shippingRates.map( ( shippingRate, i ) => (
		<Package
			key={ shippingRate.items.join() }
			className={ className }
			noResultsMessage={ noResultsMessage }
			onChange={ ( newShippingRate ) => {
				const newSelected = [ ...selected ];
				newSelected[ i ] = newShippingRate;
				onChange( newSelected );
			} }
			renderOption={ renderOption }
			selected={ selected[ i ] }
			shippingRate={ shippingRate }
			showItems={ shippingRates.length > 1 }
		/>
	) );
};

ShippingRatesControl.propTypes = {
	address: PropTypes.shape( {
		address_1: PropTypes.string,
		address_2: PropTypes.string,
		city: PropTypes.string,
		state: PropTypes.string,
		postcode: PropTypes.string,
		country: PropTypes.string,
	} ),
	noResultsMessage: PropTypes.string.isRequired,
	onChange: PropTypes.func.isRequired,
	renderOption: PropTypes.func.isRequired,
	className: PropTypes.string,
	selected: PropTypes.arrayOf( PropTypes.string ),
};

export default ShippingRatesControl;
