/**
 * External dependencies
 */
import { __, sprintf } from '@wordpress/i18n';
import PropTypes from 'prop-types';
import deprecated from '@wordpress/deprecated';

/**
 * Display a number with a styled border.
 *
 * @param {Object} props
 * @param {number} props.count
 * @param {string} props.label
 * @return {Object} -
 */
const Count = ( { count, label } ) => {
	deprecated( 'Count', {
		version: '8.0.0',
		alternative: '@woocommerce/components Badge',
		plugin: 'WooCommerce',
		hint: 'Use `import { Badge } from "@woocommerce/components"`',
	} );

	if ( ! label ) {
		label = sprintf( __( 'Total %d', 'woocommerce-admin' ), count );
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
