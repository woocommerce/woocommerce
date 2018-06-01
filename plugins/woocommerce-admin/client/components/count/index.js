/** @format */
/**
 * External dependencies
 */
import PropTypes from 'prop-types';
/**
 * Internal dependencies
 */
import './style.scss';

const Count = ( { count } ) => {
	return <span className="woocommerce-count">{ count }</span>;
};

Count.propTypes = {
	count: PropTypes.number.isRequired,
};

export default Count;
