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
			label={ __( 'Order by', 'woo-gutenberg-products-block' ) }
			onChange={ onChange }
			options={ [
				{ key: 'most-recent', label: __( 'Most recent', 'woo-gutenberg-products-block' ) },
				{ key: 'highest-rating', label: __( 'Highest rating', 'woo-gutenberg-products-block' ) },
				{ key: 'lowest-rating', label: __( 'Lowest rating', 'woo-gutenberg-products-block' ) },
			] }
			readOnly={ readOnly }
			screenReaderLabel={ __( 'Order reviews by', 'woo-gutenberg-products-block' ) }
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
