/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import PropTypes from 'prop-types';

/**
 * Internal dependencies
 */
import OrderSelect from '../order-select';
import './style.scss';

const ReviewOrderSelect = ( { defaultValue, onChange, readOnly, value } ) => {
	return (
		<OrderSelect
			className="wc-block-review-order-select"
			defaultValue={ defaultValue }
			label={ __( 'Order by', 'woocommerce' ) }
			onChange={ onChange }
			options={ [
				{ key: 'most-recent', label: __( 'Most recent', 'woocommerce' ) },
				{ key: 'highest-rating', label: __( 'Highest rating', 'woocommerce' ) },
				{ key: 'lowest-rating', label: __( 'Lowest rating', 'woocommerce' ) },
			] }
			readOnly={ readOnly }
			screenReaderLabel={ __( 'Order reviews by', 'woocommerce' ) }
			value={ value }
		/>
	);
};

ReviewOrderSelect.propTypes = {
	defaultValue: PropTypes.oneOf( [ 'most-recent', 'highest-rating', 'lowest-rating' ] ),
	onChange: PropTypes.func,
	readOnly: PropTypes.bool,
	value: PropTypes.oneOf( [ 'most-recent', 'highest-rating', 'lowest-rating' ] ),
};

export default ReviewOrderSelect;
