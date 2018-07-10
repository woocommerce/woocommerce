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
	count: PropTypes.number.isRequired,
	label: PropTypes.string,
};

Count.defaultProps = {
	label: '',
};

export default Count;
