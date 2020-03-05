/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import PropTypes from 'prop-types';
import { usePrevious } from '@woocommerce/base-hooks';

/**
 * Internal dependencies
 */
import Packages from './packages';
import LoadingMask from '../loading-mask';
import './style.scss';

const ShippingRatesControl = ( {
	shippingRates,
	shippingRatesLoading,
	className,
	noResultsMessage,
	renderOption,
} ) => {
	const previousShippingRates = usePrevious(
		shippingRates,
		( newRates ) => newRates.length > 0
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
				renderOption={ renderOption }
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
	renderOption: PropTypes.func.isRequired,
	className: PropTypes.string,
};

export default ShippingRatesControl;
export { Packages };
