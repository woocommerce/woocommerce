/**
 * External dependencies
 */
import PropTypes from 'prop-types';
import { useSelectShippingRate } from '@woocommerce/base-hooks';

/**
 * Internal dependencies
 */
import Package from './package';
import './style.scss';

const Packages = ( {
	className,
	collapsible = false,
	noResultsMessage,
	renderOption,
	shippingRates = [],
} ) => {
	const { selectShippingRate, selectedShippingRates } = useSelectShippingRate(
		shippingRates
	);
	/* eslint-disable camelcase */
	return (
		<div className="wc-block-components-shipping-rates-control">
			{ shippingRates.map( ( { package_id, ...shippingRate } ) => (
				<Package
					key={ package_id }
					className={ className }
					collapsible={ collapsible }
					noResultsMessage={ noResultsMessage }
					onChange={ ( newShippingRate ) => {
						selectShippingRate( newShippingRate, package_id );
					} }
					renderOption={ renderOption }
					selected={ selectedShippingRates[ package_id ] }
					shippingRate={ shippingRate }
					showItems={ shippingRates.length > 1 }
					title={
						shippingRates.length > 1 ? shippingRate.name : null
					}
				/>
			) ) }
		</div>
	);
	/* eslint-enable */
};

Packages.propTypes = {
	renderOption: PropTypes.func.isRequired,
	className: PropTypes.string,
	collapsible: PropTypes.bool,
	noResultsMessage: PropTypes.string,
	shippingRates: PropTypes.arrayOf(
		PropTypes.shape( {
			items: PropTypes.arrayOf(
				PropTypes.shape( {
					key: PropTypes.string,
					name: PropTypes.string,
					quantity: PropTypes.number,
				} )
			).isRequired,
			package_id: PropTypes.number,
			name: PropTypes.string,
			destination: PropTypes.object,
			shipping_rates: PropTypes.arrayOf( PropTypes.object ),
		} ).isRequired
	).isRequired,
};

export default Packages;
