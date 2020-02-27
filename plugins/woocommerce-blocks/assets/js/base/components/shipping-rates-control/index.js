/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import PropTypes from 'prop-types';
import { usePrevious, useShippingRates } from '@woocommerce/base-hooks';
import { useEffect } from '@wordpress/element';

/**
 * Internal dependencies
 */
import Packages from './packages';
import LoadingMask from '../loading-mask';
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
	const previousShippingRates = usePrevious(
		shippingRates,
		( newRates ) => newRates.length > 0
	);

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

	return (
		<LoadingMask
			isLoading={ shippingRatesLoading }
			screenReaderLabel={ __(
				'Loading shipping rates…',
				'woo-gutenberg-products-block'
			) }
			showSpinner={ true }
		>
			<Packages
				className={ className }
				noResultsMessage={ noResultsMessage }
				onChange={ onChange }
				renderOption={ renderOption }
				selected={ selected }
				shippingRates={ previousShippingRates || shippingRates }
			/>
		</LoadingMask>
	);
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
export { Packages };
