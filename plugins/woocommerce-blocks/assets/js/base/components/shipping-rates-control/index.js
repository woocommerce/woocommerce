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
	collapsibleWhenMultiple = false,
	noResultsMessage,
	renderOption,
} ) => {
	const previousShippingRates = usePrevious(
		shippingRates,
		( newRates ) => newRates.length > 0
	);

	const shippingRatesToDisplay = previousShippingRates || shippingRates;

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
				collapsible={
					shippingRatesToDisplay.length > 1 && collapsibleWhenMultiple
				}
				noResultsMessage={ noResultsMessage }
				renderOption={ renderOption }
				shippingRates={ shippingRatesToDisplay }
			/>
		</LoadingMask>
	);
};

ShippingRatesControl.propTypes = {
	noResultsMessage: PropTypes.string.isRequired,
	renderOption: PropTypes.func.isRequired,
	className: PropTypes.string,
	collapsibleWhenMultiple: PropTypes.bool,
	shippingRates: PropTypes.array,
	shippingRatesLoading: PropTypes.bool,
};

export default ShippingRatesControl;
export { Packages };
