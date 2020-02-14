/**
 * External dependencies
 */
import PropTypes from 'prop-types';
import { Fragment } from '@wordpress/element';

/**
 * Internal dependencies
 */
import PackageOptions from './package-options';
import './style.scss';

const Package = ( {
	className,
	noResultsMessage,
	onChange,
	renderOption,
	selected,
	shippingRate,
	showItems,
} ) => {
	return (
		<Fragment>
			<PackageOptions
				className={ className }
				noResultsMessage={ noResultsMessage }
				onChange={ onChange }
				options={ shippingRate.shipping_rates }
				renderOption={ renderOption }
				selected={ selected }
			/>
			{ showItems && (
				<span>
					{ /* @todo Show product names,
						see: https://github.com/woocommerce/woocommerce-gutenberg-products-block/issues/1554 */ }
					{ shippingRate.items.join( ', ' ) }
				</span>
			) }
		</Fragment>
	);
};

Package.propTypes = {
	noResultsMessage: PropTypes.string.isRequired,
	onChange: PropTypes.func.isRequired,
	renderOption: PropTypes.func.isRequired,
	shippingRate: PropTypes.shape( {
		shipping_rates: PropTypes.arrayOf( PropTypes.object ).isRequired,
		items: PropTypes.arrayOf( PropTypes.string ).isRequired,
	} ).isRequired,
	className: PropTypes.string,
	selected: PropTypes.string,
	showItems: PropTypes.bool,
};

export default Package;
