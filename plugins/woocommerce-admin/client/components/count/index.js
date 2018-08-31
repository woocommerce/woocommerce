/** @format */
/**
 * External dependencies
 */
import { __, sprintf } from '@wordpress/i18n';
import PropTypes from 'prop-types';
/**
 * Internal dependencies
 */
import './style.scss';

/**
 * Display a number with a styled border.
 *
 * @return { object } -
 */
const Count = ( { count, label } ) => {
	if ( ! label ) {
		label = sprintf( __( 'Total %d', 'wc-admin' ), count );
	}

	return (
		<span className="woocommerce-count" aria-label={ label }>
			{ count }
		</span>
	);
};

Count.propTypes = {
	/**
	 * Value of the number to be displayed.
	 */
	count: PropTypes.number.isRequired,
	/**
	 * A translated label with the number in context, used for screen readers.
	 */
	label: PropTypes.string,
};

Count.defaultProps = {
	label: '',
};

export default Count;
